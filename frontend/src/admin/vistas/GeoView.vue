<template>
  <CabeceraPanel antetitulo="Visibilidad" titulo="GEO (IA)" subtitulo="Lo que necesitan ChatGPT, Claude, Perplexity o Gemini para conocer DuaLab, citarla y recomendarla." />

  <div v-if="cargando" class="gw-tarjeta h-64 animate-pulse" aria-busy="true" />
  <div v-else class="grid gap-6 xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
    <form class="space-y-6" @submit.prevent="guardar">
      <section class="gw-tarjeta space-y-5 p-5 sm:p-6">
        <div>
          <h2 class="font-heading text-base font-bold text-azul-noche">Datos de la organización</h2>
          <p class="mt-1 text-sm text-texto-secundario">Van en todas las páginas como datos estructurados: así Google y las IA saben quién es DuaLab, dónde está y cómo contactar.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label for="gw-org-nombre" class="gw-etiqueta">Nombre de la organización</label>
            <input id="gw-org-nombre" v-model="form.org_nombre" type="text" maxlength="120" class="gw-campo" placeholder="DuaLab" />
            <p class="gw-ayuda">Sin nombre no se publican estos datos.</p>
          </div>
          <div>
            <label for="gw-org-email" class="gw-etiqueta">Correo de contacto</label>
            <input id="gw-org-email" v-model.trim="form.org_email" type="email" maxlength="120" class="gw-campo" placeholder="info@dualab.es" />
            <p v-if="errores['ajustes.org_email']" class="gw-error">{{ errores['ajustes.org_email'] }}</p>
          </div>
          <div>
            <label for="gw-org-tel" class="gw-etiqueta">Teléfono</label>
            <input id="gw-org-tel" v-model.trim="form.org_telefono" type="tel" maxlength="30" class="gw-campo" />
            <p v-if="errores['ajustes.org_telefono']" class="gw-error">Solo números, espacios y + ( ) -.</p>
          </div>
          <div class="sm:col-span-2">
            <label for="gw-org-dir" class="gw-etiqueta">Dirección</label>
            <input id="gw-org-dir" v-model="form.org_direccion" type="text" maxlength="160" class="gw-campo" placeholder="Calle y número" />
          </div>
          <div>
            <label for="gw-org-cp" class="gw-etiqueta">Código postal</label>
            <input id="gw-org-cp" v-model.trim="form.org_codigo_postal" type="text" maxlength="10" class="gw-campo" />
          </div>
          <div>
            <label for="gw-org-loc" class="gw-etiqueta">Localidad</label>
            <input id="gw-org-loc" v-model="form.org_localidad" type="text" maxlength="80" class="gw-campo" />
          </div>
          <div class="sm:col-span-2">
            <label for="gw-org-prov" class="gw-etiqueta">Provincia</label>
            <input id="gw-org-prov" v-model="form.org_provincia" type="text" maxlength="80" class="gw-campo" />
          </div>
        </div>
        <div>
          <p class="gw-etiqueta">Logo</p>
          <CampoImagen v-model="form.org_logo" titulo-selector="Logo de la organización" />
          <p class="gw-ayuda">Cuadrado o apaisado, mínimo 112 px. Si lo dejas vacío se usa el logo de Apariencia.</p>
        </div>
        <fieldset>
          <legend class="gw-etiqueta">Perfiles en redes sociales</legend>
          <ul class="space-y-2">
            <li v-for="(red, i) in form.org_redes" :key="i" class="flex items-center gap-2">
              <input v-model.trim="form.org_redes[i]" type="url" maxlength="255" class="gw-campo" placeholder="https://www.linkedin.com/company/…" :aria-label="`Perfil ${i + 1}`" />
              <button type="button" class="gw-accion-icono shrink-0" :aria-label="`Quitar perfil ${i + 1}`" title="Quitar" @click="form.org_redes.splice(i, 1)"><XMarkIcon class="h-5 w-5" aria-hidden="true" /></button>
            </li>
          </ul>
          <p v-if="erroresRedes" class="gw-error">{{ erroresRedes }}</p>
          <button v-if="form.org_redes.length < 8" type="button" class="gw-accion-secundaria mt-2" @click="form.org_redes.push('')">
            <PlusIcon class="h-4 w-4" aria-hidden="true" /> Añadir perfil
          </button>
        </fieldset>
      </section>

      <section class="gw-tarjeta space-y-5 p-5 sm:p-6">
        <div>
          <h2 class="font-heading text-base font-bold text-azul-noche">Presentación para las IA</h2>
          <p class="mt-1 text-sm text-texto-secundario">Va al principio de <span class="font-semibold">llms.txt</span>, el resumen de la web que leen las IA. Debajo se añaden solas las páginas con su descripción y las últimas noticias.</p>
        </div>
        <div>
          <label for="gw-llms" class="gw-etiqueta">Qué es DuaLab</label>
          <textarea id="gw-llms" v-model="form.llms_resumen" rows="7" maxlength="3000" class="gw-campo" placeholder="Qué es DuaLab, a quién se dirige (centros de FP, empresas, alumnado, administraciones), qué ofrece y en qué se diferencia." />
          <p class="gw-ayuda">Texto llano y frases concretas: es lo que una IA repetirá al hablar de DuaLab. {{ (form.llms_resumen || '').length }}/3000</p>
        </div>
      </section>

      <section v-if="sesion.esAdmin" class="gw-tarjeta space-y-4 p-5 sm:p-6">
        <div>
          <h2 class="font-heading text-base font-bold text-azul-noche">Qué IA pueden leer la web</h2>
          <p class="mt-1 text-sm text-texto-secundario">Apagar una la bloquea en robots.txt y dejará de citar DuaLab. No afecta a Google ni a Bing.</p>
        </div>
        <ul class="grid gap-2 sm:grid-cols-2">
          <li v-for="(nombre, bot) in BOTS_IA" :key="bot">
            <label class="flex h-full items-start gap-3 rounded-xl border p-3" :class="form.ia_bloqueados.includes(bot) ? 'border-amber-300 bg-amber-50' : 'border-gray-200'">
              <input type="checkbox" role="switch" class="gw-interruptor mt-0.5" :checked="!form.ia_bloqueados.includes(bot)" @change="alternarBot(bot, $event.target.checked)" />
              <span class="min-w-0">
                <span class="block break-words text-sm font-semibold text-azul-noche">{{ nombre }}</span>
                <span class="gw-ayuda block break-all">{{ bot }}</span>
              </span>
            </label>
          </li>
        </ul>
      </section>

      <button type="submit" class="gw-accion-principal" :disabled="guardando">{{ guardando ? 'Guardando…' : 'Guardar' }}</button>
    </form>

    <div class="space-y-6">
      <section class="gw-tarjeta p-5">
        <h2 class="font-heading text-base font-bold text-azul-noche">Qué falta para las IA</h2>
        <p class="mt-1 text-sm text-texto-secundario">{{ hechos }} de {{ estado.length }} listos.</p>
        <ul class="mt-3 space-y-3">
          <li v-for="e in estado" :key="e.texto" class="flex items-start gap-2 text-sm">
            <CheckCircleIcon v-if="e.ok" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" aria-hidden="true" />
            <ExclamationCircleIcon v-else class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" aria-hidden="true" />
            <span class="min-w-0">
              <span class="block text-azul-noche">{{ e.texto }}</span>
              <span v-if="!e.ok && e.ayuda" class="gw-ayuda block">{{ e.ayuda }}</span>
              <RouterLink v-if="!e.ok && e.to" :to="e.to" class="gw-enlace">{{ e.enlace }}</RouterLink>
            </span>
          </li>
        </ul>
      </section>
      <section class="gw-tarjeta p-5">
        <h2 class="font-heading text-base font-bold text-azul-noche">Lo que leen las IA</h2>
        <p class="mt-1 text-sm text-texto-secundario">Se genera solo con lo que hay en el panel.</p>
        <ul class="mt-3 space-y-3">
          <li v-for="a in archivos" :key="a.url">
            <p class="text-sm font-semibold text-azul-noche">{{ a.titulo }}</p>
            <div class="mt-1 flex gap-2">
              <input :value="a.url" type="text" readonly class="gw-campo bg-gray-50" :aria-label="`Dirección de ${a.titulo}`" @focus="$event.target.select()" />
              <a :href="a.url" target="_blank" rel="noopener" class="gw-accion-icono shrink-0" :aria-label="`Abrir ${a.titulo}`" title="Abrir"><ArrowTopRightOnSquareIcon class="h-5 w-5" aria-hidden="true" /></a>
            </div>
            <p class="gw-ayuda">{{ a.ayuda }}</p>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ArrowTopRightOnSquareIcon, CheckCircleIcon, ExclamationCircleIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import CampoImagen from '../componentes/CampoImagen.vue'
import { BOTS_IA } from '../botsIa'
import { ajustesService } from '@/services/admin/ajustesService'
import { paginasService } from '@/services/admin/paginasService'
import { mensajeError, erroresCampos } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'
import { usePanelSesion } from '@/stores/panelSesion'

const avisos = useAvisos()
const sesion = usePanelSesion()

// Ajustes del grupo seo que se editan aquí (los generales, en SEO)
const VACIO = {
  org_nombre: '', org_logo: '', org_email: '', org_telefono: '', org_direccion: '', org_codigo_postal: '',
  org_localidad: '', org_provincia: '', org_redes: [], llms_resumen: '', ia_bloqueados: [],
}
// Solo lo cambia el admin: el editor no lo envía (el backend lo rechazaría)
const SOLO_ADMIN = ['ia_bloqueados']

const form = reactive(structuredClone(VACIO))
const generales = ref({}) // descripción e indexar, de SEO: solo para el estado
const paginas = ref([])
const cargando = ref(true)
const guardando = ref(false)
const errores = ref({})

const archivos = [
  { titulo: 'llms.txt', url: `${window.location.origin}/llms.txt`, ayuda: 'Resumen de DuaLab y de la web para las IA.' },
  { titulo: 'robots.txt', url: `${window.location.origin}/robots.txt`, ayuda: 'Qué buscadores e IA pueden leer la web.' },
]
const erroresRedes = computed(() => Object.entries(errores.value).find(([k]) => k.startsWith('ajustes.org_redes'))?.[1])

const estado = computed(() => {
  const publicas = paginas.value.filter(p => p.ruta && !p.ruta.includes(':'))
  const conDescripcion = publicas.filter(p => p.seo_completo).length
  const seo = { name: 'panel-seo' }
  return [
    { ok: generales.value.indexar !== false, texto: 'La web se puede rastrear', ayuda: 'Está desactivado en SEO: nadie puede leerla.', to: seo, enlace: 'Ir a SEO' },
    { ok: !form.ia_bloqueados.length, texto: form.ia_bloqueados.length ? `${form.ia_bloqueados.length} IA bloqueadas` : 'Todas las IA pueden leer la web', ayuda: 'Las IA bloqueadas no citarán DuaLab.' },
    { ok: !!form.org_nombre, texto: 'Nombre de la organización', ayuda: 'Sin él no se publican los datos de la organización.' },
    { ok: !!(form.org_email || form.org_telefono), texto: 'Correo o teléfono de contacto' },
    { ok: !!form.org_localidad, texto: 'Localidad', ayuda: 'Ayuda a que la IA sitúe a DuaLab (p. ej. «en Canarias»).' },
    { ok: form.org_redes.some(Boolean), texto: 'Al menos un perfil en redes', ayuda: 'Confirma a las IA que DuaLab es una organización real.' },
    { ok: !!generales.value.descripcion, texto: 'Descripción de DuaLab', ayuda: 'Es la «Descripción por defecto» de SEO.', to: seo, enlace: 'Ir a SEO' },
    { ok: (form.llms_resumen || '').length >= 200, texto: 'Presentación para las IA', ayuda: 'Al menos un par de párrafos.' },
    { ok: publicas.length > 0 && conDescripcion === publicas.length, texto: `Páginas con título y descripción: ${conDescripcion} de ${publicas.length}`, ayuda: 'llms.txt usa la descripción de cada página.', to: seo, enlace: 'Ver cuáles faltan' },
  ]
})
const hechos = computed(() => estado.value.filter(e => e.ok).length)

function aplicar(d) {
  for (const [clave, defecto] of Object.entries(VACIO)) {
    form[clave] = Array.isArray(defecto) ? [...(d[clave] ?? [])] : (d[clave] ?? defecto)
  }
  generales.value = { descripcion: d.descripcion, indexar: d.indexar }
}

function alternarBot(bot, permitido) {
  form.ia_bloqueados = permitido ? form.ia_bloqueados.filter(b => b !== bot) : [...form.ia_bloqueados, bot]
}

onMounted(async () => {
  try {
    const [ajustes, lista] = await Promise.all([ajustesService.ver('seo'), paginasService.listar()])
    aplicar(ajustes)
    paginas.value = lista
  } catch (e) { avisos.error(mensajeError(e)) }
  finally { cargando.value = false }
})

async function guardar() {
  guardando.value = true
  errores.value = {}
  try {
    const datos = Object.fromEntries(Object.entries(form)
      .filter(([clave]) => sesion.esAdmin || !SOLO_ADMIN.includes(clave))
      .map(([clave, valor]) => [clave, Array.isArray(valor) ? valor.filter(Boolean) : (valor === '' ? null : valor)]))
    aplicar(await ajustesService.guardar('seo', datos))
    avisos.ok('Ajustes de IA guardados.')
  } catch (e) {
    errores.value = erroresCampos(e)
    avisos.error(mensajeError(e))
  } finally {
    guardando.value = false
  }
}
</script>
