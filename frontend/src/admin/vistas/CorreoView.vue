<template>
  <CabeceraPanel antetitulo="Contactos" titulo="Correo" :destacado="resumen.buzon?.email ?? 'info@'" subtitulo="Lo que llega al buzón, sin abrir el webmail. Se actualiza solo cada 2 minutos.">
    <button type="button" class="gw-accion-secundaria" :disabled="cargando" @click="cargar(meta?.current_page ?? 1)">
      <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': cargando }" aria-hidden="true" /> Actualizar
    </button>
    <a v-if="resumen.retencion?.webmail" :href="resumen.retencion.webmail" target="_blank" rel="noopener" class="gw-accion-secundaria">
      Webmail <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" />
    </a>
    <button type="button" class="gw-accion-principal" :disabled="resumen.buzon && !resumen.buzon.configurado" @click="redactar('nuevo')">
      <PencilSquareIcon class="h-4 w-4" aria-hidden="true" /> Redactar
    </button>
  </CabeceraPanel>

  <RedactarCorreo :abierto="!!redaccion" :modo="redaccion?.modo ?? 'nuevo'" :original="redaccion?.original ?? null"
                  :remitente="resumen.buzon?.email ?? 'info@dualab.es'" @cerrar="redaccion = null" @enviado="alEnviar" />

  <!-- Avisos: buzón sin conectar, error de sincronización y retención (en móvil se ocultan mientras se lee) -->
  <div :class="{ 'hidden md:block': detalle }">
  <!-- Bandeja de salida: lo que aún no ha salido o ha fallado -->
  <div v-if="enviandose.length" class="mb-4 flex items-center gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900" role="status">
    <PaperAirplaneIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
    {{ enviandose.length === 1 ? 'Hay 1 correo enviándose.' : `Hay ${enviandose.length} correos enviándose.` }} Sale en uno o dos minutos.
  </div>
  <div v-for="e in fallidos" :key="e.id" class="mb-4 flex flex-col gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 sm:flex-row sm:items-center" role="alert">
    <p class="min-w-0 flex-1 break-words">
      <strong>No se ha enviado «{{ e.asunto }}»</strong> a {{ e.para.join(', ') }}. {{ e.error }}
    </p>
    <div class="flex shrink-0 flex-wrap gap-2">
      <button type="button" class="gw-accion-peligro" @click="descartarEnvio(e)">Descartar</button>
      <button type="button" class="gw-accion-secundaria" @click="reintentarEnvio(e)">Reintentar</button>
    </div>
  </div>
  <div v-if="resumen.buzon && !resumen.buzon.configurado" class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
    El buzón aún no está conectado: falta la contraseña de <strong class="break-all">{{ resumen.buzon.email }}</strong> en la configuración del servidor (<code>CORREO_INFO_PASSWORD</code>).
  </div>
  <div v-else-if="resumen.sincronizado?.error" class="mb-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
    {{ resumen.sincronizado.error }}
    <span v-if="resumen.sincronizado.en">Última copia correcta: {{ haceCuanto(resumen.sincronizado.en) }}.</span>
  </div>
  <div v-if="resumen.sincronizado?.omitidos" class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
    {{ resumen.sincronizado.omitidos === 1 ? 'Hay 1 correo que no se ha podido copiar' : `Hay ${resumen.sincronizado.omitidos} correos que no se han podido copiar` }}
    aquí: ábrelos en el <a :href="resumen.retencion?.webmail" target="_blank" rel="noopener" class="gw-enlace">webmail</a>.
  </div>
  <div v-if="resumen.retencion" class="gw-tarjeta mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
    <ClockIcon class="hidden h-6 w-6 shrink-0 text-administraciones-dark sm:block" aria-hidden="true" />
    <p class="min-w-0 flex-1 text-sm text-azul-noche">
      Aquí ves los correos de los últimos <strong>{{ resumen.retencion.meses }} meses</strong> (desde el {{ fecha(resumen.retencion.desde) }}).
      Revisa si hay correos más antiguos en <a :href="resumen.retencion.webmail" target="_blank" rel="noopener" class="gw-enlace break-all">{{ resumen.retencion.webmail }}</a>.
      <span v-if="resumen.sincronizado?.en" class="text-texto-secundario">· Actualizado {{ haceCuanto(resumen.sincronizado.en) }}</span>
    </p>
  </div>
  </div>

  <!-- Pestañas y búsqueda a todo el ancho: en la columna de la lista (tablet) no caben -->
  <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center" :class="{ 'hidden md:flex': detalle }">
    <div class="gw-carril shrink-0" role="tablist" aria-label="Carpeta">
      <button v-for="p in PESTANAS" :key="p.nombre" type="button" role="tab" class="gw-pestana"
              :class="{ 'gw-pestana--activa': activa(p) }" :aria-selected="activa(p)" @click="elegirPestana(p)">
        {{ p.nombre }} <span v-if="p.contador" class="gw-contador">{{ resumen.por_estado?.[p.contador] ?? 0 }}</span>
      </button>
    </div>
    <div class="gw-tarjeta flex min-w-0 flex-1 flex-col gap-3 p-3 sm:flex-row sm:items-center">
      <div class="relative min-w-0 flex-1">
        <label for="gw-buscar-correo" class="sr-only">Buscar</label>
        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true" />
        <input id="gw-buscar-correo" v-model="filtros.buscar" type="search" class="gw-campo pl-9" placeholder="Asunto o remitente" @input="buscarConRetardo" />
      </div>
      <label class="flex min-h-9 shrink-0 cursor-pointer items-center gap-2 text-sm text-azul-noche">
        <input v-model="filtros.adjuntos" type="checkbox" class="h-4 w-4 rounded border-gray-300" @change="cargar(1)" /> Con adjuntos
      </label>
    </div>
  </div>

  <!-- Dos columnas desde md (tablet, sin menú lateral) y lista más ancha cuando sobra sitio -->
  <div class="grid gap-4 md:grid-cols-[minmax(0,16rem)_minmax(0,1fr)] xl:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] 2xl:grid-cols-[minmax(0,26rem)_minmax(0,1fr)]">
    <!-- Bandeja (en móvil se oculta mientras se lee un correo) -->
    <section class="min-w-0" :class="{ 'hidden md:block': detalle }" aria-label="Bandeja">
      <div v-if="error" class="gw-tarjeta p-6 text-center">
        <p class="text-sm text-gray-700">{{ error }}</p>
        <button type="button" class="gw-accion-secundaria mt-4" @click="cargar(1)">Reintentar</button>
      </div>
      <div v-else class="gw-tarjeta overflow-hidden">
        <ul v-if="cargando && !correos.length" class="divide-y divide-gray-100"><li v-for="n in 6" :key="n" class="p-4"><span class="block h-10 animate-pulse rounded bg-gray-100" /></li></ul>
        <div v-else-if="!correos.length" class="p-10 text-center">
          <InboxIcon class="mx-auto h-10 w-10 text-gray-300" aria-hidden="true" />
          <p class="mt-3 text-sm text-gray-700">{{ hayFiltros ? 'No hay correos con estos filtros.' : 'No hay correos en la bandeja.' }}</p>
          <button v-if="hayFiltros" type="button" class="gw-accion-secundaria mt-4" @click="limpiar">Ver todos</button>
        </div>
        <ul v-else class="divide-y divide-gray-100">
          <li v-for="c in correos" :key="c.id">
            <button type="button" class="flex w-full items-start gap-3 p-4 text-left hover:bg-gray-50"
                    :class="{ 'bg-administraciones-dark/5': detalle?.id === c.id }" :aria-current="detalle?.id === c.id ? 'true' : undefined" @click="abrir(c.id)">
              <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full" :class="c.estado === 'nuevo' ? 'bg-alumnos' : 'bg-transparent'" />
              <span class="sr-only">{{ c.estado === 'nuevo' ? 'No leído:' : '' }}</span>
              <span class="min-w-0 flex-1">
                <span class="flex items-baseline gap-2">
                  <span class="min-w-0 flex-1 truncate text-sm text-azul-noche" :class="c.estado === 'nuevo' ? 'font-bold' : 'font-medium'">
                    <template v-if="c.carpeta === 'enviados'">Para: {{ c.para.map(d => d.nombre || d.email).join(', ') || '—' }}</template>
                    <template v-else>{{ c.de_nombre || c.de_email }}</template>
                  </span>
                  <span v-if="c.carpeta !== 'enviados' && conocido(c)" class="gw-chip shrink-0" :class="COLORES_FORMULARIO[conocido(c).tipo] ?? 'bg-gray-100 text-gray-700'">
                    {{ conocido(c).tipo_nombre }}
                  </span>
                  <span class="shrink-0 text-xs text-texto-secundario">{{ haceCuanto(c.fecha) }}</span>
                </span>
                <span class="mt-0.5 flex items-center gap-1.5">
                  <span class="min-w-0 flex-1 truncate text-sm text-azul-noche" :class="{ 'font-semibold': c.estado === 'nuevo' }">{{ c.asunto || '(Sin asunto)' }}</span>
                  <PaperClipIcon v-if="c.tiene_adjuntos" class="h-4 w-4 shrink-0 text-texto-secundario" aria-label="Con adjuntos" />
                </span>
                <span v-if="c.extracto" class="mt-0.5 line-clamp-2 break-words text-xs text-texto-secundario">{{ c.extracto }}</span>
              </span>
            </button>
          </li>
        </ul>
      </div>
      <Paginacion :meta="meta" @ir="cargar" />
    </section>

    <!-- Lectura: en tablet y escritorio se queda fija al bajar por la lista (debajo de la barra superior) -->
    <section class="min-w-0 md:sticky md:top-20 md:max-h-[calc(100vh-6rem)] md:self-start md:overflow-y-auto" :class="{ 'hidden md:block': !detalle }" aria-label="Correo abierto">
      <div v-if="!detalle" class="gw-tarjeta flex min-h-[16rem] flex-col items-center justify-center p-10 text-center">
        <EnvelopeOpenIcon class="h-10 w-10 text-gray-300" aria-hidden="true" />
        <p class="mt-3 text-sm text-gray-700">Elige un correo para leerlo.</p>
      </div>
      <article v-else class="gw-tarjeta p-4 sm:p-6">
        <button type="button" class="gw-accion-secundaria mb-4 md:hidden" @click="cerrar">
          <ArrowLeftIcon class="h-4 w-4" aria-hidden="true" /> Bandeja
        </button>
        <h2 class="break-words text-lg font-bold text-azul-noche sm:text-xl">{{ detalle.asunto || '(Sin asunto)' }}</h2>
        <dl class="mt-3 grid gap-1 text-sm">
          <div class="flex flex-wrap gap-x-2">
            <dt class="text-texto-secundario">De</dt>
            <!-- Siempre la dirección real, no solo el nombre: ayuda a detectar suplantaciones -->
            <dd class="min-w-0 break-words text-azul-noche"><strong v-if="detalle.de_nombre">{{ detalle.de_nombre }}</strong> &lt;{{ detalle.de_email }}&gt;</dd>
          </div>
          <div v-if="detalle.para?.length" class="flex flex-wrap gap-x-2">
            <dt class="text-texto-secundario">Para</dt>
            <dd class="min-w-0 break-words text-azul-noche">{{ lista(detalle.para) }}</dd>
          </div>
          <div v-if="detalle.cc?.length" class="flex flex-wrap gap-x-2">
            <dt class="text-texto-secundario">CC</dt>
            <dd class="min-w-0 break-words text-azul-noche">{{ lista(detalle.cc) }}</dd>
          </div>
          <div v-if="detalle.contacto" class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <dt class="text-texto-secundario">Formulario</dt>
            <dd class="flex flex-wrap items-center gap-2 text-azul-noche">
              <span class="gw-chip" :class="COLORES_FORMULARIO[detalle.contacto.tipo] ?? 'bg-gray-100 text-gray-700'">{{ detalle.contacto.tipo_nombre }}</span>
              <RouterLink :to="{ name: 'panel-formularios', query: { ver: detalle.contacto.id } }" class="gw-enlace">
                Nos escribió por la web el {{ fecha(detalle.contacto.fecha) }}
              </RouterLink>
            </dd>
          </div>
          <div class="flex flex-wrap gap-x-2">
            <dt class="text-texto-secundario">Fecha</dt>
            <dd class="text-azul-noche">{{ fechaHora(detalle.fecha) }}</dd>
          </div>
        </dl>

        <div class="mt-4 flex flex-wrap gap-2">
          <button type="button" class="gw-accion-principal" @click="redactar('responder', detalle)">
            <ArrowUturnLeftIcon class="h-4 w-4" aria-hidden="true" /> Responder
          </button>
          <button v-if="variosDestinatarios" type="button" class="gw-accion-secundaria" @click="redactar('responder_todos', detalle)">
            <ArrowUturnLeftIcon class="h-4 w-4" aria-hidden="true" /> Responder a todos
          </button>
          <button type="button" class="gw-accion-secundaria" @click="redactar('reenviar', detalle)">
            <ArrowUturnRightIcon class="h-4 w-4" aria-hidden="true" /> Reenviar
          </button>
          <template v-if="detalle.carpeta !== 'enviados'">
            <button type="button" class="gw-accion-secundaria" @click="cambiarEstado('nuevo')">Marcar como no leído</button>
            <button type="button" class="gw-accion-secundaria" @click="cambiarEstado(detalle.estado === 'archivado' ? 'leido' : 'archivado')">
              {{ detalle.estado === 'archivado' ? 'Sacar del archivo' : 'Archivar' }}
            </button>
          </template>
        </div>

        <ul v-if="adjuntosVisibles.length" class="mt-4 flex flex-wrap gap-2" aria-label="Adjuntos">
          <li v-for="a in adjuntosVisibles" :key="a.id" class="min-w-0 max-w-full">
            <button v-if="a.disponible" type="button" class="gw-chip inline-flex min-h-9 max-w-full items-center gap-1.5 bg-gray-100 px-3 py-2 text-gray-800 hover:bg-gray-200" @click="descargar(a)">
              <PaperClipIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              <span class="truncate">{{ a.nombre }}</span> <span class="shrink-0 text-gray-500">{{ tamano(a.tamano) }}</span>
            </button>
            <span v-else class="gw-chip inline-flex max-w-full items-center gap-1.5 bg-gray-50 px-3 py-2 text-gray-500" title="Demasiado grande para copiarlo: ábrelo en el webmail">
              <PaperClipIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              <span class="truncate">{{ a.nombre }}</span> <span class="shrink-0">({{ tamano(a.tamano) }}, solo en el webmail)</span>
            </span>
          </li>
        </ul>

        <hr class="my-4 border-gray-100" />
        <CuerpoCorreo :html="detalle.html" :texto="detalle.texto" />
      </article>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeftIcon, ArrowPathIcon, ArrowTopRightOnSquareIcon, ArrowUturnLeftIcon, ArrowUturnRightIcon, ClockIcon,
  EnvelopeOpenIcon, InboxIcon, MagnifyingGlassIcon, PaperAirplaneIcon, PaperClipIcon, PencilSquareIcon,
} from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import CuerpoCorreo from '../componentes/CuerpoCorreo.vue'
import Paginacion from '../componentes/Paginacion.vue'
import RedactarCorreo from '../componentes/RedactarCorreo.vue'
import { correoService } from '@/services/admin/correoService'
import { mensajeError } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'
import { COLORES_FORMULARIO, fecha, fechaHora, haceCuanto, tamano } from '../utilidades'

// Pestañas: carpeta del buzón + estado (los estados solo existen en lo recibido)
const PESTANAS = [
  { nombre: 'Bandeja', carpeta: 'recibidos', estado: '' },
  { nombre: 'No leídos', carpeta: 'recibidos', estado: 'nuevo', contador: 'nuevo' },
  { nombre: 'Archivados', carpeta: 'recibidos', estado: 'archivado' },
  { nombre: 'Enviados', carpeta: 'enviados', estado: '' },
]

const route = useRoute()
const router = useRouter()
const avisos = useAvisos()

const filtros = reactive({
  carpeta: route.query.carpeta === 'enviados' ? 'enviados' : '', estado: route.query.estado ?? '',
  buscar: route.query.buscar ?? '', adjuntos: route.query.adjuntos === '1',
})
const correos = ref([])
const meta = ref(null)
const resumen = ref({})
const salida = ref([])
// Remitentes que ya escribieron por un formulario: email (minúsculas) => { tipo, tipo_nombre }
const remitentes = ref({})
const conocido = c => remitentes.value[c.de_email?.toLowerCase()]
const cargando = ref(true)
const error = ref('')
const detalle = ref(null)
const redaccion = ref(null) // { modo, original }

const hayFiltros = computed(() => !!(filtros.buscar || filtros.adjuntos))
const params = () => Object.fromEntries(Object.entries({ ...filtros, adjuntos: filtros.adjuntos ? 1 : '' }).filter(([, v]) => v))
const activa = p => (filtros.carpeta || 'recibidos') === p.carpeta && filtros.estado === p.estado
// Las imágenes en línea (logos de firma) ya se ven en el cuerpo si se permiten; no se listan
const adjuntosVisibles = computed(() => (detalle.value?.adjuntos ?? []).filter(a => !a.en_linea))
const lista = dirs => dirs.map(d => (d.nombre ? `${d.nombre} <${d.email}>` : d.email)).join(', ')
const variosDestinatarios = computed(() => ((detalle.value?.para?.length ?? 0) + (detalle.value?.cc?.length ?? 0)) > 1)
const enviandose = computed(() => salida.value.filter(e => e.estado === 'pendiente'))
const fallidos = computed(() => salida.value.filter(e => e.estado === 'error'))

async function cargar(pagina = 1) {
  cargando.value = true
  error.value = ''
  router.replace({ query: { ...params(), ...(route.query.ver ? { ver: route.query.ver } : {}) } })
  try {
    const r = await correoService.listar({ ...params(), page: pagina })
    correos.value = r.data
    meta.value = r.meta
    resumen.value = r.resumen
    salida.value = r.salida ?? []
    remitentes.value = r.remitentes ?? {}
  } catch (e) {
    error.value = mensajeError(e, 'No se ha podido cargar el correo.')
  } finally {
    cargando.value = false
  }
}

function elegirPestana(p) {
  filtros.carpeta = p.carpeta === 'enviados' ? 'enviados' : ''
  filtros.estado = p.estado
  cerrar()
  cargar(1)
}
function limpiar() { Object.assign(filtros, { buscar: '', adjuntos: false }); cargar(1) }
let retardo
function buscarConRetardo() { clearTimeout(retardo); retardo = setTimeout(() => cargar(1), 350) }

async function abrir(id) {
  try {
    detalle.value = await correoService.ver(id) // abrirlo lo marca como leído (solo en el panel)
    const fila = correos.value.find(c => c.id === id)
    if (fila) fila.estado = detalle.value.estado
    router.replace({ query: { ...route.query, ver: id } })
    // En móvil la lista se oculta: sin esto, el correo se abriría a la altura a la que estaba la lista
    if (window.matchMedia('(max-width: 767px)').matches) window.scrollTo({ top: 0 })
  } catch (e) { avisos.error(mensajeError(e)) }
}
function cerrar() {
  detalle.value = null
  const { ver, ...resto } = route.query
  router.replace({ query: resto })
}

async function cambiarEstado(estado) {
  try {
    await correoService.cambiarEstado(detalle.value.id, estado)
    avisos.ok({ nuevo: 'Marcado como no leído.', leido: 'Sacado del archivo.', archivado: 'Archivado.' }[estado])
    cerrar()
    cargar(meta.value?.current_page ?? 1)
  } catch (e) { avisos.error(mensajeError(e)) }
}

async function descargar(adjunto) {
  try {
    await correoService.descargarAdjunto(detalle.value.id, adjunto)
  } catch (e) { avisos.error(mensajeError(e, 'No se ha podido descargar el adjunto.')) }
}

// ── Envío ──
function redactar(modo, original = null) { redaccion.value = { modo, original } }
function alEnviar() {
  redaccion.value = null
  cargar(meta.value?.current_page ?? 1)
  programarRefresco(20_000) // el envío sale por la cola en uno o dos minutos
}
async function reintentarEnvio(e) {
  try {
    await correoService.reintentar(e.id)
    avisos.ok('Se vuelve a intentar el envío.')
    cargar(meta.value?.current_page ?? 1)
  } catch (err) { avisos.error(mensajeError(err)) }
}
async function descartarEnvio(e) {
  if (!window.confirm(`¿Descartar «${e.asunto}»? No se enviará.`)) return
  try {
    await correoService.descartar(e.id)
    salida.value = salida.value.filter(s => s.id !== e.id)
  } catch (err) { avisos.error(mensajeError(err)) }
}

// La copia se actualiza cada 2 minutos en el servidor: la bandeja se refresca al mismo ritmo,
// y más a menudo mientras hay correos saliendo
let refresco
function programarRefresco(ms) {
  clearTimeout(refresco)
  refresco = setTimeout(async () => {
    if (!document.hidden && !redaccion.value) await cargar(meta.value?.current_page ?? 1)
    programarRefresco(enviandose.value.length ? 20_000 : 120_000)
  }, ms)
}
onMounted(() => {
  cargar(1)
  if (route.query.ver) abrir(Number(route.query.ver))
  programarRefresco(120_000)
})
onBeforeUnmount(() => clearTimeout(refresco))
</script>
