<template>
  <CabeceraPanel antetitulo="Páginas" :titulo="pagina?.titulo ?? 'Página'" :volver="{ name: 'panel-paginas' }" volver-label="Todas las páginas"
                 :subtitulo="pagina?.ruta ? `Se ve en ${pagina.ruta}` : 'Bloque que aparece en varias páginas de la web'">
    <a v-if="enlace" :href="enlace" target="_blank" rel="noopener" class="gw-accion-secundaria">
      <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" /> Ver en la web
    </a>
    <button type="button" class="gw-accion-principal" :disabled="guardando || !hayCambios" @click="guardar">
      {{ guardando ? 'Guardando…' : 'Guardar cambios' }}
    </button>
  </CabeceraPanel>

  <div v-if="error" class="gw-tarjeta p-6 text-center">
    <p class="text-sm text-gray-700">{{ error }}</p>
    <button type="button" class="gw-accion-secundaria mt-4" @click="cargar">Reintentar</button>
  </div>

  <div v-else-if="!pagina" class="space-y-4" aria-busy="true">
    <div v-for="n in 3" :key="n" class="gw-tarjeta h-40 animate-pulse" />
  </div>

  <template v-else>
    <!-- Idioma que se edita (solo si hay más de uno) y pestañas -->
    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
      <div class="gw-carril" role="tablist" aria-label="Partes de la página">
        <button v-for="t in pestanas" :key="t.clave" type="button" role="tab" class="gw-pestana" :class="{ 'gw-pestana--activa': pestana === t.clave }"
                :aria-selected="pestana === t.clave" @click="pestana = t.clave">
          {{ t.nombre }}
        </button>
      </div>
      <div v-if="idiomas.length > 1" class="flex flex-wrap items-center gap-2">
        <span class="text-sm font-semibold text-azul-noche">Idioma:</span>
        <div class="gw-carril" role="group" aria-label="Idioma que estás editando">
          <button v-for="i in idiomas" :key="i.codigo" type="button" class="gw-pestana" :class="{ 'gw-pestana--activa': idioma === i.codigo }"
                  :aria-pressed="idioma === i.codigo" @click="cambiarIdioma(i.codigo)">
            {{ i.codigo.toUpperCase() }}
          </button>
        </div>
      </div>
    </div>

    <p v-if="idioma !== idiomaBase" class="mb-5 rounded-xl border border-centros/20 bg-centros/5 px-4 py-3 text-sm text-azul-noche">
      Estás editando la versión en <strong>{{ nombreIdioma(idioma) }}</strong>. Los campos muestran el texto en español como punto de partida:
      tradúcelos y guarda. Para que la web la muestre, actívala en la pestaña «Publicación».
    </p>

    <!-- CONTENIDO -->
    <div v-show="pestana === 'contenido'" class="space-y-4">
      <p v-if="!pagina.secciones.length" class="gw-tarjeta p-6 text-sm text-texto-secundario">
        Esta página no tiene textos editables desde el panel: su contenido sale de los datos de la plataforma (formularios, retos…). Puedes editar su SEO.
      </p>
      <details v-for="(s, i) in pagina.secciones" :key="s.clave" class="gw-tarjeta group" :open="i === 0">
        <summary class="flex cursor-pointer list-none items-center gap-3 p-5">
          <span class="min-w-0 flex-1">
            <span class="block font-heading text-base font-bold text-azul-noche">{{ s.titulo }}</span>
            <span class="text-xs text-texto-secundario">{{ s.campos.length }} campos<template v-if="editadosEn(s)"> · {{ editadosEn(s) }} editados</template></span>
          </span>
          <ChevronDownIcon class="h-5 w-5 shrink-0 text-gray-400 transition-transform group-open:rotate-180" aria-hidden="true" />
        </summary>
        <div class="grid gap-5 border-t border-gray-100 p-5 md:grid-cols-2">
          <CampoContenido v-for="c in s.campos" :key="c.clave" v-model="valores[c.clave]" :campo="c" :rutas="rutas"
                          :error="errores[`valores.${c.clave}`] || errores[`valores.${c.clave}.url`] || errores[`valores.${c.clave}.texto`]"
                          :class="{ 'md:col-span-2': c.tipo !== 'texto' }" />
        </div>
      </details>
    </div>

    <!-- SEO -->
    <div v-show="pestana === 'seo'" class="gw-tarjeta max-w-3xl p-5 sm:p-6">
      <CajaSeo v-model="seo" :titulo-por-defecto="`${pagina.titulo} | DuaLab`" :url-visible="urlVisible" :slug="pagina.ruta ?? ''"
               :texto="textoPlano" :errores="erroresSeo" />
    </div>

    <!-- PUBLICACIÓN -->
    <div v-show="pestana === 'publicacion'" class="gw-tarjeta max-w-3xl space-y-6 p-5 sm:p-6">
      <label v-if="pagina.ruta && pagina.clave !== 'inicio'" class="flex items-start gap-3">
        <input v-model="publicada" type="checkbox" role="switch" class="gw-interruptor mt-0.5" />
        <span>
          <span class="block text-sm font-semibold text-azul-noche">Página publicada</span>
          <span class="gw-ayuda block">Si la ocultas, quien entre en {{ pagina.ruta }} irá a la página de inicio y no saldrá en el sitemap.</span>
        </span>
      </label>
      <div v-if="idiomas.length > 1">
        <p class="gw-etiqueta">Versiones en otros idiomas visibles en la web</p>
        <p class="gw-ayuda mb-3">El idioma principal siempre se muestra. Marca los demás cuando estén traducidos.</p>
        <div class="flex flex-wrap gap-3">
          <label v-for="i in idiomas.filter(x => x.codigo !== idiomaBase)" :key="i.codigo" class="flex items-center gap-2 rounded-full border border-gray-200 px-4 py-2 text-sm">
            <input v-model="idiomasActivos" type="checkbox" :value="i.codigo" class="h-4 w-4 accent-administraciones-dark" />
            {{ i.nombre }}<span v-if="!i.activo" class="text-xs text-texto-secundario">(idioma aún no activo en la web)</span>
          </label>
        </div>
      </div>
      <p v-if="!pagina.ruta && idiomas.length <= 1" class="text-sm text-texto-secundario">Este bloque se publica junto con las páginas que lo contienen.</p>
      <p v-else-if="pagina.clave === 'inicio' && idiomas.length <= 1" class="text-sm text-texto-secundario">La página de inicio siempre está publicada.</p>
    </div>

    <!-- HISTORIAL: quién ha cambiado la página (se recarga cada vez que se abre) -->
    <div v-if="pestana === 'historial'" class="gw-tarjeta max-w-3xl p-5 sm:p-6">
      <h2 class="mb-4 font-heading text-base font-bold text-azul-noche">Quién ha cambiado esta página</h2>
      <HistorialActividad tipo="pagina" :referencia="pagina.clave" />
    </div>

    <!-- Barra de guardado fija en móvil -->
    <div v-if="hayCambios" class="sticky bottom-3 z-20 mt-6 flex items-center justify-between gap-3 rounded-full border border-gray-200 bg-white/95 py-2 pl-5 pr-2 shadow-lg backdrop-blur">
      <span class="min-w-0 truncate text-sm font-semibold text-azul-noche">Tienes cambios sin guardar</span>
      <button type="button" class="gw-accion-principal" :disabled="guardando" @click="guardar">{{ guardando ? 'Guardando…' : 'Guardar' }}</button>
    </div>
  </template>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowTopRightOnSquareIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import CampoContenido from '../componentes/CampoContenido.vue'
import CajaSeo from '../componentes/CajaSeo.vue'
import HistorialActividad from '../componentes/HistorialActividad.vue'
import { paginasService } from '@/services/admin/paginasService'
import { idiomasService } from '@/services/admin/idiomasService'
import { mensajeError, erroresCampos } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'
import { useCambiosSinGuardar } from '../composables/useCambiosSinGuardar'
import { URL_WEB } from '../utilidades'

const route = useRoute()
const avisos = useAvisos()

const pagina = ref(null)
const valores = ref({})
const seo = ref({})
const publicada = ref(true)
const idiomasActivos = ref([])
const idiomas = ref([])
const idioma = ref('es')
const idiomaBase = ref('es')
const pestana = ref('contenido')
const error = ref('')
const errores = ref({})
const guardando = ref(false)
const instantanea = ref('')

const pestanas = [
  { clave: 'contenido', nombre: 'Contenido' },
  { clave: 'seo', nombre: 'SEO' },
  { clave: 'publicacion', nombre: 'Publicación' },
  { clave: 'historial', nombre: 'Historial' },
]

// Destinos sugeridos para los botones: las páginas de la web
const rutas = [
  { valor: '/', nombre: 'Inicio' }, { valor: '/centros', nombre: 'Centros' }, { valor: '/empresas-asociadas', nombre: 'Empresas' },
  { valor: '/para-alumnado', nombre: 'Alumnado' }, { valor: '/entidades', nombre: 'Entidades' }, { valor: '/familias', nombre: 'Familias profesionales' },
  { valor: '/noticias', nombre: 'Noticias' }, { valor: '/contacto', nombre: 'Contacto' },
  { valor: '/contacto?tipo=centro', nombre: 'Contacto (centro)' }, { valor: '/contacto?tipo=empresa', nombre: 'Contacto (empresa)' },
  { valor: '/contacto?tipo=administracion', nombre: 'Contacto (administración)' },
]

const estado = () => JSON.stringify([valores.value, seo.value, publicada.value, idiomasActivos.value])
const hayCambios = computed(() => !!pagina.value && estado() !== instantanea.value)
useCambiosSinGuardar(() => hayCambios.value)

const enlace = computed(() => (pagina.value?.ruta && !pagina.value.ruta.includes(':') ? pagina.value.ruta : null))
const urlVisible = computed(() => `${URL_WEB.replace(/^https?:\/\//, '')}${enlace.value ?? ''}`)
const nombreIdioma = c => idiomas.value.find(i => i.codigo === c)?.nombre ?? c
const editadosEn = s => s.campos.filter(c => pagina.value.valores[c.clave] !== undefined).length
const textoPlano = computed(() => Object.values(valores.value).map(v => (typeof v === 'string' ? v : Array.isArray(v) ? '' : v?.texto ?? '')).join(' '))
const erroresSeo = computed(() => Object.fromEntries(Object.entries(errores.value).filter(([k]) => k.startsWith('seo.')).map(([k, v]) => [k.slice(4), v])))

function aplicar(data) {
  pagina.value = data
  // Cada campo muestra su valor guardado o, si no se ha cambiado, el texto original
  valores.value = Object.fromEntries(data.secciones.flatMap(s => s.campos).map(c => {
    const v = data.valores[c.clave]
    if (c.tipo === 'boton') return [c.clave, { texto: v?.texto ?? c.defecto?.texto ?? '', url: v?.url ?? c.defecto?.url ?? '' }]
    if (c.tipo === 'imagen') return [c.clave, v ?? '']
    if (c.tipo === 'galeria') return [c.clave, v ?? []]
    return [c.clave, v ?? c.defecto ?? '']
  }))
  seo.value = { ...data.seo }
  publicada.value = data.publicada
  idiomasActivos.value = [...(data.idiomas_activos ?? [])]
  idioma.value = data.idioma
  errores.value = {}
  instantanea.value = estado()
}

async function cargar() {
  error.value = ''
  try {
    const [data, listaIdiomas] = await Promise.all([
      paginasService.ver(route.params.clave, idioma.value),
      idiomas.value.length ? Promise.resolve(null) : idiomasService.listar(),
    ])
    if (listaIdiomas) {
      idiomas.value = listaIdiomas.data
      idiomaBase.value = listaIdiomas.data.find(i => i.por_defecto)?.codigo ?? 'es'
    }
    aplicar(data)
  } catch (e) {
    error.value = mensajeError(e, 'No se ha podido cargar la página.')
  }
}
onMounted(cargar)

async function cambiarIdioma(codigo) {
  if (codigo === idioma.value) return
  if (hayCambios.value && !window.confirm('Tienes cambios sin guardar en este idioma. ¿Cambiar de idioma sin guardar?')) return
  idioma.value = codigo
  pagina.value = null
  await cargar()
}

// { 'hero.titulo': 'x' } → { hero: { titulo: 'x' } } (formato que valida el backend)
function anidar(planos) {
  return Object.entries(planos).reduce((acc, [clave, v]) => {
    const [seccion, campo] = clave.split('.')
    ;(acc[seccion] ??= {})[campo] = v
    return acc
  }, {})
}

async function guardar() {
  guardando.value = true
  errores.value = {}
  try {
    const data = await paginasService.guardar(pagina.value.clave, {
      idioma: idioma.value,
      publicada: publicada.value,
      idiomas_activos: idiomasActivos.value,
      valores: anidar(valores.value),
      seo: seo.value,
    })
    aplicar(data)
    avisos.ok('Cambios guardados. Ya se ven en la web.')
  } catch (e) {
    errores.value = erroresCampos(e)
    if (Object.keys(errores.value).some(k => k.startsWith('seo.'))) pestana.value = 'seo'
    avisos.error(mensajeError(e, 'No se han podido guardar los cambios.'))
  } finally {
    guardando.value = false
  }
}
</script>
