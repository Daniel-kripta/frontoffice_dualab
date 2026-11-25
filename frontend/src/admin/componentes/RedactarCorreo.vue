<template>
  <VentanaModal :abierto="abierto" :titulo="TITULOS[contacto ? 'formulario' : modo]" :subtitulo="`Se enviará desde ${remitente}`" ancho="lg" @cerrar="intentarCerrar">
    <form :id="idForm" class="grid gap-4" novalidate @submit.prevent="enviar">
      <div>
        <div class="flex flex-wrap items-end justify-between gap-2">
          <label :for="`${idForm}-para`" class="gw-etiqueta">Para</label>
          <button v-if="!verCopias" type="button" class="gw-enlace mb-1 text-sm" @click="verCopias = true">Añadir CC / CCO</button>
        </div>
        <input :id="`${idForm}-para`" v-model="form.para" type="text" inputmode="email" autocomplete="off" class="gw-campo"
               placeholder="correo@ejemplo.com, otro@ejemplo.com" :aria-invalid="!!errorDe('para')" />
        <p v-if="errorDe('para')" class="gw-error">{{ errorDe('para') }}</p>
        <p v-else class="gw-ayuda">Separa varias direcciones con comas.</p>
      </div>
      <div v-if="verCopias" class="grid gap-4 md:grid-cols-2">
        <div>
          <label :for="`${idForm}-cc`" class="gw-etiqueta">CC</label>
          <input :id="`${idForm}-cc`" v-model="form.cc" type="text" inputmode="email" autocomplete="off" class="gw-campo" :aria-invalid="!!errorDe('cc')" />
          <p v-if="errorDe('cc')" class="gw-error">{{ errorDe('cc') }}</p>
        </div>
        <div>
          <label :for="`${idForm}-cco`" class="gw-etiqueta">CCO <span class="font-normal text-texto-secundario">(copia oculta)</span></label>
          <input :id="`${idForm}-cco`" v-model="form.cco" type="text" inputmode="email" autocomplete="off" class="gw-campo" :aria-invalid="!!errorDe('cco')" />
          <p v-if="errorDe('cco')" class="gw-error">{{ errorDe('cco') }}</p>
        </div>
      </div>
      <div>
        <label :for="`${idForm}-asunto`" class="gw-etiqueta">Asunto</label>
        <input :id="`${idForm}-asunto`" v-model="form.asunto" type="text" maxlength="255" class="gw-campo" :aria-invalid="!!errorDe('asunto')" />
        <p v-if="errorDe('asunto')" class="gw-error">{{ errorDe('asunto') }}</p>
      </div>
      <div>
        <label :for="`${idForm}-texto`" class="gw-etiqueta">Mensaje</label>
        <textarea :id="`${idForm}-texto`" ref="campoTexto" v-model="form.texto" rows="12" maxlength="50000"
                  class="gw-campo min-h-[12rem] resize-y font-sans leading-relaxed" :aria-invalid="!!errorDe('texto')" />
        <p v-if="errorDe('texto')" class="gw-error">{{ errorDe('texto') }}</p>
        <p v-else class="gw-ayuda">La firma de {{ remitente }} se añade al final al enviar.</p>
      </div>

      <!-- Adjuntos del correo original (al reenviar) -->
      <fieldset v-if="adjuntosOriginal.length">
        <legend class="gw-etiqueta">Adjuntos del correo original</legend>
        <ul class="grid gap-1">
          <li v-for="a in adjuntosOriginal" :key="a.id">
            <label class="flex min-h-9 cursor-pointer items-center gap-2 text-sm text-azul-noche">
              <input v-model="form.reenviar_adjuntos" type="checkbox" :value="a.id" class="h-4 w-4 shrink-0 rounded border-gray-300" />
              <span class="min-w-0 truncate">{{ a.nombre }}</span> <span class="shrink-0 text-texto-secundario">{{ tamano(a.tamano) }}</span>
            </label>
          </li>
        </ul>
      </fieldset>

      <div>
        <span class="gw-etiqueta">Adjuntar archivos</span>
        <ul v-if="form.adjuntos.length" class="mb-2 flex flex-wrap gap-2">
          <li v-for="(f, i) in form.adjuntos" :key="`${f.name}-${i}`" class="min-w-0 max-w-full">
            <span class="gw-chip inline-flex min-h-9 max-w-full items-center gap-1.5 bg-gray-100 py-1 pl-3 pr-1 text-gray-800">
              <PaperClipIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              <span class="truncate">{{ f.name }}</span> <span class="shrink-0 text-gray-500">{{ tamano(f.size) }}</span>
              <button type="button" class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full hover:bg-gray-200" :aria-label="`Quitar ${f.name}`" @click="form.adjuntos.splice(i, 1)">
                <XMarkIcon class="h-4 w-4" aria-hidden="true" />
              </button>
            </span>
          </li>
        </ul>
        <label class="gw-accion-secundaria w-fit cursor-pointer">
          <PaperClipIcon class="h-4 w-4" aria-hidden="true" /> Elegir archivos
          <input type="file" multiple class="sr-only" :accept="ACEPTA" @change="anadirArchivos" />
        </label>
        <p v-if="errorAdjuntos" class="gw-error">{{ errorAdjuntos }}</p>
        <p v-else class="gw-ayuda">PDF, Office, imágenes, texto o ZIP. Hasta 10 archivos, 10 MB cada uno y 20 MB en total.</p>
      </div>
    </form>

    <template #pie>
      <button type="button" class="gw-accion-secundaria" @click="intentarCerrar">Cancelar</button>
      <button type="submit" :form="idForm" class="gw-accion-principal" :disabled="enviando">
        <PaperAirplaneIcon class="h-4 w-4" aria-hidden="true" /> {{ enviando ? 'Enviando…' : 'Enviar' }}
      </button>
    </template>
  </VentanaModal>
</template>

<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue'
import { PaperAirplaneIcon, PaperClipIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import VentanaModal from './VentanaModal.vue'
import { correoService } from '@/services/admin/correoService'
import { erroresCampos, mensajeError } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'
import { fechaHora, tamano } from '../utilidades'

const props = defineProps({
  abierto: { type: Boolean, default: false },
  // nuevo | responder | responder_todos | reenviar
  modo: { type: String, default: 'nuevo' },
  original: { type: Object, default: null },
  // Respuesta a un formulario de la web: { id, email, nombre, asunto }
  contacto: { type: Object, default: null },
  remitente: { type: String, default: 'info@dualab.es' },
})
const emit = defineEmits(['cerrar', 'enviado'])

const TITULOS = { formulario: 'Responder al formulario', nuevo: 'Nuevo correo', responder: 'Responder', responder_todos: 'Responder a todos', reenviar: 'Reenviar' }
const EXTENSIONES = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'txt', 'csv', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'zip']
const ACEPTA = EXTENSIONES.map(e => `.${e}`).join(',')
const MAX_MB = 10
const MAX_TOTAL_MB = 20

const avisos = useAvisos()
const idForm = `gw-redactar-${Math.random().toString(36).slice(2, 9)}`
const campoTexto = ref(null)
const form = reactive({ para: '', cc: '', cco: '', asunto: '', texto: '', adjuntos: [], reenviar_adjuntos: [] })
const inicial = ref('')
const verCopias = ref(false)
const enviando = ref(false)
const errores = ref({})
const errorAdjuntos = ref('')

const adjuntosOriginal = computed(() => (props.modo === 'reenviar'
  ? (props.original?.adjuntos ?? []).filter(a => a.disponible && !a.en_linea) : []))
// Errores de para.0, cc.3… se muestran en su campo
const errorDe = campo => errores.value[campo] ?? Object.entries(errores.value).find(([k]) => k.startsWith(`${campo}.`))?.[1]

const lista = texto => texto.split(/[,;\s]+/).map(s => s.trim()).filter(Boolean)
const quien = d => (d.nombre ? `${d.nombre} <${d.email}>` : d.email)
const conPrefijo = (prefijo, asunto) => (new RegExp(`^${prefijo}:`, 'i').test(asunto ?? '') ? asunto : `${prefijo}: ${asunto ?? ''}`.trim())
// Cita recortada: el mensaje entero no puede pasar de 50 000 caracteres
const citable = o => (o.texto_citable ?? '').slice(0, 20000)

function rellenar() {
  const o = props.original
  const propio = props.remitente.toLowerCase()
  Object.assign(form, { para: '', cc: '', cco: '', asunto: '', texto: '', adjuntos: [], reenviar_adjuntos: [] })
  errores.value = {}
  errorAdjuntos.value = ''
  verCopias.value = false

  if (props.contacto) {
    form.para = props.contacto.email ?? ''
    form.asunto = props.contacto.asunto ?? ''
    form.texto = props.contacto.nombre ? `Hola, ${props.contacto.nombre}:\n\n` : ''
  } else if (o && (props.modo === 'responder' || props.modo === 'responder_todos')) {
    // Si el remitente pidió respuesta a otra dirección (Reply-To), se responde ahí
    const destino = o.carpeta === 'enviados' ? o.para?.[0]?.email : (o.responder_a?.[0]?.email ?? o.de_email)
    form.para = destino ?? ''
    if (props.modo === 'responder_todos') {
      const resto = [...(o.para ?? []), ...(o.cc ?? [])].map(d => d.email)
        .filter(e => e && e.toLowerCase() !== propio && e.toLowerCase() !== destino?.toLowerCase())
      form.cc = [...new Set(resto)].join(', ')
      verCopias.value = !!form.cc
    }
    form.asunto = conPrefijo('Re', o.asunto)
    const cita = citable(o).split('\n').map(l => `> ${l}`).join('\n')
    form.texto = `\n\nEl ${fechaHora(o.fecha)}, ${quien({ nombre: o.de_nombre, email: o.de_email })} escribió:\n${cita}`
  } else if (o && props.modo === 'reenviar') {
    form.asunto = conPrefijo('Fwd', o.asunto)
    form.texto = `\n\n---------- Mensaje reenviado ----------\nDe: ${quien({ nombre: o.de_nombre, email: o.de_email })}\n`
      + `Fecha: ${fechaHora(o.fecha)}\nAsunto: ${o.asunto ?? ''}\nPara: ${(o.para ?? []).map(quien).join(', ')}\n\n${citable(o)}`
    form.reenviar_adjuntos = adjuntosOriginal.value.map(a => a.id)
  }
  inicial.value = JSON.stringify(form)
}

watch(() => props.abierto, async (abierto) => {
  if (!abierto) return
  rellenar()
  await nextTick()
  // Al responder, el cursor va al principio del mensaje (encima de la cita)
  if (props.contacto && campoTexto.value) {
    campoTexto.value.focus()
    campoTexto.value.setSelectionRange(form.texto.length, form.texto.length)
  } else if (props.modo !== 'nuevo' && campoTexto.value) {
    campoTexto.value.focus()
    campoTexto.value.setSelectionRange(0, 0)
    campoTexto.value.scrollTop = 0
  }
})

function anadirArchivos(e) {
  errorAdjuntos.value = ''
  for (const f of e.target.files) {
    const ext = f.name.split('.').pop()?.toLowerCase()
    if (!EXTENSIONES.includes(ext)) { errorAdjuntos.value = `«${f.name}» no se puede adjuntar (tipo de archivo no permitido).`; continue }
    if (f.size > MAX_MB * 1024 * 1024) { errorAdjuntos.value = `«${f.name}» ocupa más de ${MAX_MB} MB.`; continue }
    if (form.adjuntos.length >= 10) { errorAdjuntos.value = 'Como mucho 10 archivos por correo.'; break }
    form.adjuntos.push(f)
  }
  const total = form.adjuntos.reduce((s, f) => s + f.size, 0)
  if (total > MAX_TOTAL_MB * 1024 * 1024) errorAdjuntos.value = `Los adjuntos suman más de ${MAX_TOTAL_MB} MB: quita alguno.`
  e.target.value = ''
}

function intentarCerrar() {
  if (enviando.value) return
  if (JSON.stringify(form) !== inicial.value && !window.confirm('¿Descartar este correo? Se perderá lo que has escrito.')) return
  emit('cerrar')
}

async function enviar() {
  errores.value = {}
  if (errorAdjuntos.value) return
  const datos = new FormData()
  datos.append('tipo', props.modo === 'responder_todos' ? 'responder' : props.modo)
  if (props.original && props.modo !== 'nuevo') datos.append('correo_id', props.original.id)
  if (props.contacto) datos.append('contacto_id', props.contacto.id)
  for (const campo of ['para', 'cc', 'cco']) lista(form[campo]).forEach(e => datos.append(`${campo}[]`, e))
  datos.append('asunto', form.asunto)
  datos.append('texto', form.texto)
  form.adjuntos.forEach(f => datos.append('adjuntos[]', f))
  form.reenviar_adjuntos.forEach(id => datos.append('reenviar_adjuntos[]', id))

  enviando.value = true
  try {
    await correoService.enviar(datos)
    avisos.ok('Enviando… Aparecerá en Enviados en un par de minutos.')
    emit('enviado')
  } catch (e) {
    errores.value = erroresCampos(e)
    const deAdjuntos = Object.entries(errores.value).find(([k]) => k.startsWith('adjuntos') || k.startsWith('reenviar_adjuntos'))
    if (deAdjuntos) errorAdjuntos.value = deAdjuntos[1]
    if (!Object.keys(errores.value).length) avisos.error(mensajeError(e, 'No se ha podido enviar.'))
  } finally {
    enviando.value = false
  }
}
</script>
