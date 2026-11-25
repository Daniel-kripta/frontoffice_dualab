<!-- «Cómo nos ven Google y la IA»: lo que recibe un rastreador al pedir la página (lo guardado,
     no lo que se está escribiendo). Lo genera el backend igual que para Google (HtmlPublicoService). -->
<template>
  <button type="button" class="gw-accion-secundaria" @click="abrir">
    <EyeIcon class="h-4 w-4" aria-hidden="true" /> <span class="hidden sm:inline">Cómo nos ven Google y la IA</span><span class="sm:hidden">Cómo nos ven</span>
  </button>

  <VentanaModal :abierto="abierto" titulo="Cómo nos ven Google y la IA" :subtitulo="`Lo que reciben al pedir ${ruta} (lo guardado)`" ancho="lg" @cerrar="abierto = false">
    <div v-if="cargando" class="space-y-3" aria-busy="true">
      <div v-for="n in 3" :key="n" class="h-16 animate-pulse rounded-xl bg-gray-100" />
    </div>
    <p v-else-if="error" class="text-sm text-gray-700">{{ error }}</p>
    <p v-else-if="datos?.redirige_a" class="text-sm text-gray-700">Esta página no está publicada: quien la pida va a {{ datos.redirige_a }}.</p>

    <div v-else-if="datos" class="space-y-6">
      <ul class="grid gap-2 sm:grid-cols-2">
        <li v-for="c in comprobaciones" :key="c.texto" class="flex items-start gap-2 rounded-xl border p-3 text-sm" :class="c.ok ? 'border-gray-200' : 'border-amber-300 bg-amber-50'">
          <CheckCircleIcon v-if="c.ok" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" aria-hidden="true" />
          <ExclamationTriangleIcon v-else class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" aria-hidden="true" />
          <span class="min-w-0">
            <span class="block font-semibold text-azul-noche">{{ c.texto }}</span>
            <span class="gw-ayuda block break-words">{{ c.detalle }}</span>
          </span>
        </li>
      </ul>

      <section>
        <h3 class="gw-etiqueta">En Google</h3>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
          <p class="truncate text-xs text-[#4d5156]">{{ datos.canonical }}</p>
          <p class="mt-1 break-words text-lg leading-snug text-[#1a0dab]">{{ datos.titulo }}</p>
          <p class="mt-1 break-words text-sm text-[#4d5156]">{{ datos.descripcion || 'Sin descripción: Google elegirá un fragmento del texto.' }}</p>
        </div>
      </section>

      <section v-if="datos.encabezados.length">
        <h3 class="gw-etiqueta">Estructura de la página</h3>
        <ul class="space-y-1 text-sm">
          <li v-for="(h, i) in datos.encabezados" :key="i" class="break-words" :class="{ 'pl-4': h.nivel === 2, 'pl-8 text-texto-secundario': h.nivel === 3 }">
            <span class="mr-1 rounded-full bg-gray-100 px-1.5 text-[11px] font-semibold text-gray-600">H{{ h.nivel }}</span>{{ h.texto }}
          </li>
        </ul>
      </section>

      <section>
        <h3 class="gw-etiqueta">Texto que leen ({{ datos.palabras }} palabras)</h3>
        <p class="max-h-64 overflow-y-auto whitespace-pre-line break-words rounded-xl bg-gray-50 p-4 text-sm text-gray-700">{{ datos.texto || '—' }}</p>
      </section>
    </div>
  </VentanaModal>
</template>

<script setup>
import { computed, ref } from 'vue'
import { CheckCircleIcon, ExclamationTriangleIcon, EyeIcon } from '@heroicons/vue/24/outline'
import VentanaModal from './VentanaModal.vue'
import { visibilidadService } from '@/services/admin/visibilidadService'
import { mensajeError } from '@/services/admin/panelApi'

const props = defineProps({
  ruta: { type: String, required: true },
  idioma: { type: String, default: null },
})

const abierto = ref(false)
const cargando = ref(false)
const error = ref('')
const datos = ref(null)

const TIPOS = { Organization: 'organización', WebSite: 'sitio web', FAQPage: 'preguntas frecuentes', NewsArticle: 'noticia' }

const comprobaciones = computed(() => {
  const d = datos.value
  const h1 = d.encabezados.filter(h => h.nivel === 1).length
  return [
    { ok: d.estado === 200 && !d.noindex, texto: d.noindex ? 'Oculta a los buscadores' : 'Pueden leerla', detalle: d.noindex ? 'Tiene «noindex»: no saldrá en Google ni en las IA.' : 'Se puede rastrear e indexar.' },
    { ok: d.palabras >= 300, texto: `${d.palabras} palabras de texto`, detalle: d.palabras >= 300 ? 'Suficiente para que entiendan de qué trata.' : 'Poco texto: Google y las IA tendrán poco que leer (ideal, 300 o más).' },
    { ok: h1 === 1, texto: h1 === 1 ? 'Un título principal (H1)' : `${h1} títulos principales (H1)`, detalle: 'Cada página debe tener uno solo.' },
    { ok: !!d.descripcion && d.descripcion.length >= 70, texto: d.descripcion ? 'Tiene descripción' : 'Sin descripción', detalle: d.descripcion ? `${d.descripcion.length} caracteres.` : 'Escríbela en la pestaña SEO.' },
    { ok: d.datos.includes('Organization'), texto: 'Datos de la organización', detalle: d.datos.length ? `Datos estructurados: ${d.datos.map(t => TIPOS[t] ?? t).join(', ')}.` : 'Rellénalos en Visibilidad → GEO (IA).' },
    { ok: !!d.imagen, texto: d.imagen ? 'Imagen para compartir' : 'Sin imagen para compartir', detalle: d.imagen ? 'Se verá al compartirla en redes y chats.' : 'Ponla en SEO → Avanzado o en Visibilidad → SEO.' },
  ]
})

async function abrir() {
  abierto.value = true
  cargando.value = true
  error.value = ''
  try {
    datos.value = await visibilidadService.comoNosVen(props.ruta, props.idioma)
  } catch (e) {
    error.value = mensajeError(e)
  } finally {
    cargando.value = false
  }
}
</script>
