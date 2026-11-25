<template>
  <CabeceraPanel antetitulo="Contactos" titulo="Formularios" destacado="recibidos" subtitulo="Todo lo que llega desde los formularios de la web, clasificado por formulario y página.">
    <RouterLink v-if="sesion.esAdmin" :to="{ name: 'panel-integraciones' }" class="gw-accion-secundaria">
      <LinkIcon class="h-4 w-4" aria-hidden="true" /> Integraciones
    </RouterLink>
    <button type="button" class="gw-accion-principal" :disabled="exportando" @click="exportar">
      <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" /> {{ exportando ? 'Preparando…' : 'Exportar CSV' }}
    </button>
  </CabeceraPanel>

  <!-- Avisos: el correo de info@ y dónde se validan las captaciones -->
  <div v-if="infoBandeja" class="mb-4 grid gap-3 md:grid-cols-2">
    <div class="gw-tarjeta flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
      <EnvelopeIcon class="hidden h-6 w-6 shrink-0 text-administraciones-dark sm:block" aria-hidden="true" />
      <p class="min-w-0 flex-1 text-sm text-azul-noche">
        Cada formulario llega también por correo a <strong class="break-all">{{ infoBandeja.buzon.email }}</strong>. Esta bandeja guarda una copia para ordenarlos y archivarlos.
      </p>
      <a :href="infoBandeja.buzon.url" target="_blank" rel="noopener" class="gw-accion-secundaria w-fit shrink-0">
        Abrir bandeja de correo <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" />
      </a>
    </div>
    <div class="gw-tarjeta flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
      <ShieldCheckIcon class="hidden h-6 w-6 shrink-0 text-administraciones-dark sm:block" aria-hidden="true" />
      <p class="min-w-0 flex-1 text-sm text-azul-noche">
        <template v-if="infoBandeja.captacion.activa">
          Las <strong>empresas y centros</strong> que piden registrarse, información o una demo pasan también a <strong>{{ infoBandeja.consola.nombre }}</strong>,
          donde el superadmin los valida antes de darlos de alta (igual que lo que capta el equipo comercial en la app comercial). Aquí solo se atiende el contacto.
        </template>
        <template v-else>
          Las empresas y centros captados se validan en <strong>{{ infoBandeja.consola.nombre }}</strong>, pero la conexión está desactivada: por ahora solo llegan aquí y al correo.
        </template>
      </p>
      <RouterLink v-if="!infoBandeja.captacion.activa && sesion.esAdmin" :to="{ name: 'panel-integraciones' }" class="gw-accion-secundaria w-fit shrink-0">Activarla</RouterLink>
      <a v-else-if="infoBandeja.consola.url" :href="infoBandeja.consola.url" target="_blank" rel="noopener" class="gw-accion-secundaria w-fit shrink-0">
        Ir a {{ infoBandeja.consola.nombre }} <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" />
      </a>
    </div>
  </div>

  <!-- Clasificación por formulario -->
  <div class="gw-carril mb-4" role="tablist" aria-label="Formulario">
    <button type="button" role="tab" class="gw-pestana" :class="{ 'gw-pestana--activa': !filtros.formulario }" :aria-selected="!filtros.formulario" @click="cambiar('formulario', '')">
      Todos
    </button>
    <button v-for="f in resumen.por_formulario" :key="f.clave" type="button" role="tab" class="gw-pestana"
            :class="{ 'gw-pestana--activa': filtros.formulario === f.clave }" :aria-selected="filtros.formulario === f.clave" @click="cambiar('formulario', f.clave)">
      {{ f.nombre }} <span class="gw-contador">{{ f.total }}</span>
    </button>
  </div>

  <!-- Filtros -->
  <div class="gw-tarjeta mb-4 grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-[2fr_1fr_1fr_1fr_auto] xl:items-end">
    <div class="sm:col-span-2 xl:col-span-1">
      <label for="gw-buscar-form" class="gw-etiqueta">Buscar</label>
      <div class="relative">
        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true" />
        <input id="gw-buscar-form" v-model="filtros.buscar" type="search" class="gw-campo pl-9" placeholder="Nombre, apellidos o email" @input="buscarConRetardo" />
      </div>
    </div>
    <div>
      <label for="gw-estado-form" class="gw-etiqueta">Estado</label>
      <select id="gw-estado-form" v-model="filtros.estado" class="gw-campo" @change="cargar(1)">
        <option value="">Bandeja (sin archivados)</option>
        <option value="nuevo">Nuevos ({{ resumen.por_estado.nuevo ?? 0 }})</option>
        <option value="leido">Leídos ({{ resumen.por_estado.leido ?? 0 }})</option>
        <option value="archivado">Archivados ({{ resumen.por_estado.archivado ?? 0 }})</option>
      </select>
    </div>
    <div>
      <label for="gw-accion-form" class="gw-etiqueta">Qué piden</label>
      <select id="gw-accion-form" v-model="filtros.accion" class="gw-campo" @change="cargar(1)">
        <option value="">Cualquier cosa</option>
        <option value="registro">Registrarse</option>
        <option value="info">Información</option>
        <option value="demo">Una demo</option>
      </select>
    </div>
    <div>
      <label for="gw-pagina-form" class="gw-etiqueta">Página de origen</label>
      <select id="gw-pagina-form" v-model="filtros.pagina_origen" class="gw-campo" @change="cargar(1)">
        <option value="">Todas</option>
        <option v-for="p in resumen.paginas_origen" :key="p" :value="p">{{ p }}</option>
      </select>
    </div>
    <button v-if="hayFiltros" type="button" class="gw-accion-secundaria" @click="limpiar">Limpiar filtros</button>
  </div>

  <div v-if="error" class="gw-tarjeta p-6 text-center">
    <p class="text-sm text-gray-700">{{ error }}</p>
    <button type="button" class="gw-accion-secundaria mt-4" @click="cargar(1)">Reintentar</button>
  </div>

  <div v-else class="gw-tarjeta overflow-hidden">
    <ul v-if="cargando" class="divide-y divide-gray-100"><li v-for="n in 6" :key="n" class="p-4"><span class="block h-10 animate-pulse rounded bg-gray-100" /></li></ul>
    <div v-else-if="!envios.length" class="p-10 text-center">
      <InboxIcon class="mx-auto h-10 w-10 text-gray-300" aria-hidden="true" />
      <p class="mt-3 text-sm text-gray-700">{{ hayFiltros || filtros.formulario ? 'No hay envíos con estos filtros.' : 'La bandeja está vacía.' }}</p>
      <button v-if="hayFiltros || filtros.formulario" type="button" class="gw-accion-secundaria mt-4" @click="limpiar(true)">Ver todos</button>
    </div>
    <ul v-else class="divide-y divide-gray-100">
      <li v-for="f in envios" :key="f.id">
        <button type="button" class="flex w-full items-start gap-3 p-4 text-left hover:bg-gray-50 sm:items-center" @click="abrir(f.id)">
          <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full sm:mt-0" :class="f.estado === 'nuevo' ? 'bg-alumnos' : 'bg-transparent'"
                :title="f.estado === 'nuevo' ? 'Nuevo' : ''" />
          <span class="sr-only">{{ f.estado === 'nuevo' ? 'Nuevo:' : '' }}</span>
          <span class="min-w-0 flex-1">
            <span class="block truncate text-sm text-azul-noche" :class="f.estado === 'nuevo' ? 'font-bold' : 'font-medium'">
              {{ f.nombre }} {{ f.apellidos }} <span class="font-normal text-texto-secundario">· {{ f.email }}</span>
            </span>
            <span class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-texto-secundario">
              <span class="gw-chip" :class="claseFormulario(f.formulario)">{{ f.formulario_nombre }}</span>
              <span v-if="f.accion_nombre" class="gw-chip bg-gray-100 text-gray-700">{{ f.accion_nombre }}</span>
              <span v-if="f.pagina_origen">desde {{ f.pagina_origen }}</span>
              <span v-if="f.estado === 'archivado'" class="gw-chip bg-gray-100 text-gray-700">Archivado</span>
              <span v-if="vaACaptacion(f)" class="gw-chip bg-emerald-50 text-emerald-800">Se valida en {{ infoBandeja.consola.nombre }}</span>
            </span>
          </span>
          <span class="shrink-0 text-xs text-texto-secundario">{{ haceCuanto(f.created_at) }}</span>
        </button>
      </li>
    </ul>
  </div>
  <Paginacion :meta="meta" @ir="cargar" />

  <!-- Detalle del envío -->
  <VentanaModal :abierto="!!detalle" :titulo="detalle ? `${detalle.nombre} ${detalle.apellidos ?? ''}` : ''"
                :subtitulo="detalle ? `${detalle.formulario_nombre} · ${fechaHora(detalle.created_at)}` : ''" @cerrar="cerrarDetalle">
    <template v-if="detalle">
      <p v-if="vaACaptacion(detalle)" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        Esta solicitud también ha llegado a <strong>{{ infoBandeja.consola.nombre }}</strong> como captación: el alta la valida allí el superadmin.
        Desde aquí puedes responder al contacto.
      </p>
      <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
        <div v-for="c in camposDetalle" :key="c.etiqueta" :class="{ 'sm:col-span-2': c.largo }">
          <dt class="text-xs font-bold uppercase tracking-wider text-texto-secundario">{{ c.etiqueta }}</dt>
          <dd class="mt-0.5 whitespace-pre-line break-words text-sm text-azul-noche">
            <a v-if="c.enlace" :href="c.enlace" class="gw-enlace">{{ c.valor }}</a>
            <template v-else>{{ c.valor }}</template>
          </dd>
        </div>
      </dl>
      <section v-if="detalle.respuestas?.length" class="mt-6 border-t border-gray-100 pt-4" aria-label="Respuestas enviadas">
        <h3 class="text-xs font-bold uppercase tracking-wider text-texto-secundario">Respuestas por correo</h3>
        <ul class="mt-2 grid gap-2">
          <li v-for="r in detalle.respuestas" :key="r.id" class="flex flex-wrap items-baseline gap-x-2 text-sm text-azul-noche">
            <PaperAirplaneIcon class="h-4 w-4 shrink-0 self-center text-texto-secundario" aria-hidden="true" />
            <span class="min-w-0 break-words font-semibold">{{ r.asunto }}</span>
            <span class="text-texto-secundario">{{ fechaHora(r.fecha) }}<template v-if="r.por"> · {{ r.por }}</template></span>
            <span v-if="r.estado !== 'enviado'" class="gw-chip" :class="r.estado === 'error' ? 'bg-red-50 text-red-700' : 'bg-sky-50 text-sky-800'">
              {{ r.estado === 'error' ? 'No se envió' : 'Enviándose' }}
            </span>
          </li>
        </ul>
      </section>
    </template>
    <template #pie>
      <button type="button" class="gw-accion-peligro mr-auto" @click="eliminar">Eliminar</button>
      <button v-if="detalle?.estado !== 'nuevo'" type="button" class="gw-accion-secundaria" @click="cambiarEstado('nuevo')">Marcar como no leído</button>
      <button type="button" class="gw-accion-secundaria" @click="cambiarEstado(detalle?.estado === 'archivado' ? 'leido' : 'archivado')">
        {{ detalle?.estado === 'archivado' ? 'Sacar del archivo' : 'Archivar' }}
      </button>
      <!-- El correo del panel es solo del admin; el resto responde con su programa de correo -->
      <button v-if="detalle && sesion.esAdmin" type="button" class="gw-accion-principal" @click="responderPorCorreo">
        <EnvelopeIcon class="h-4 w-4" aria-hidden="true" /> Responder por correo
      </button>
      <a v-else-if="detalle" :href="`mailto:${detalle.email}`" class="gw-accion-principal">
        <EnvelopeIcon class="h-4 w-4" aria-hidden="true" /> Responder
      </a>
    </template>
  </VentanaModal>

  <RedactarCorreo :abierto="!!respondiendo" :contacto="respondiendo" @cerrar="respondiendo = null" @enviado="alResponder" />
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowDownTrayIcon, ArrowTopRightOnSquareIcon, EnvelopeIcon, InboxIcon, LinkIcon, MagnifyingGlassIcon, PaperAirplaneIcon, ShieldCheckIcon,
} from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import Paginacion from '../componentes/Paginacion.vue'
import VentanaModal from '../componentes/VentanaModal.vue'
import RedactarCorreo from '../componentes/RedactarCorreo.vue'
import { formulariosService } from '@/services/admin/formulariosService'
import { mensajeError } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'
import { usePanelSesion } from '@/stores/panelSesion'
import { COLORES_FORMULARIO, fechaHora, haceCuanto } from '../utilidades'

const route = useRoute()
const router = useRouter()
const avisos = useAvisos()
const sesion = usePanelSesion()

const filtros = reactive({
  formulario: route.query.formulario ?? '', estado: route.query.estado ?? '', accion: route.query.accion ?? '',
  pagina_origen: route.query.pagina_origen ?? '', buscar: route.query.buscar ?? '',
})
const envios = ref([])
const meta = ref(null)
const resumen = ref({ por_estado: {}, por_formulario: [], paginas_origen: [] })
const infoBandeja = ref(null)
// Empresas y centros con formulario completo (con acción) llegan a la consola como captación
const vaACaptacion = f => !!(infoBandeja.value?.captacion.activa && f.accion && infoBandeja.value.captacion.tipos.includes(f.tipo))
const cargando = ref(true)
const error = ref('')
const detalle = ref(null)
const exportando = ref(false)

const hayFiltros = computed(() => !!(filtros.estado || filtros.accion || filtros.pagina_origen || filtros.buscar))
const params = () => Object.fromEntries(Object.entries(filtros).filter(([, v]) => v))

async function cargar(pagina = 1) {
  cargando.value = true
  error.value = ''
  router.replace({ query: { ...params(), ...(route.query.ver ? { ver: route.query.ver } : {}) } })
  try {
    const r = await formulariosService.listar({ ...params(), page: pagina })
    envios.value = r.data
    meta.value = r.meta
    resumen.value = r.resumen
    infoBandeja.value = r.avisos
  } catch (e) {
    error.value = mensajeError(e, 'No se han podido cargar los formularios.')
  } finally {
    cargando.value = false
  }
}

function cambiar(campo, valor) { filtros[campo] = valor; cargar(1) }
function limpiar(todo = false) {
  Object.assign(filtros, { estado: '', accion: '', pagina_origen: '', buscar: '' })
  if (todo === true) filtros.formulario = ''
  cargar(1)
}
let retardo
function buscarConRetardo() { clearTimeout(retardo); retardo = setTimeout(() => cargar(1), 350) }

async function abrir(id) {
  try {
    detalle.value = await formulariosService.ver(id) // abrirlo lo marca como leído
    const fila = envios.value.find(f => f.id === id)
    if (fila) fila.estado = detalle.value.estado
    router.replace({ query: { ...route.query, ver: id } })
  } catch (e) { avisos.error(mensajeError(e)) }
}
function cerrarDetalle() {
  detalle.value = null
  const { ver, ...resto } = route.query
  router.replace({ query: resto })
}

async function cambiarEstado(estado) {
  try {
    const actualizado = await formulariosService.cambiarEstado(detalle.value.id, estado)
    avisos.ok({ nuevo: 'Marcado como no leído.', leido: 'Sacado del archivo.', archivado: 'Archivado.' }[estado])
    detalle.value = { ...detalle.value, ...actualizado }
    cerrarDetalle()
    cargar(meta.value?.current_page ?? 1)
  } catch (e) { avisos.error(mensajeError(e)) }
}

// Responder por correo desde el panel (solo admin): se cierra la ficha y se abre el editor
const respondiendo = ref(null)
function responderPorCorreo() {
  const d = detalle.value
  respondiendo.value = { id: d.id, email: d.email, nombre: d.nombre, asunto: 'Tu solicitud en DuaLab' }
  cerrarDetalle()
}
function alResponder() {
  const id = respondiendo.value?.id
  respondiendo.value = null
  cargar(meta.value?.current_page ?? 1)
  if (id) abrir(id) // la ficha ya muestra la respuesta
}

async function eliminar() {
  if (!window.confirm('¿Eliminar este envío para siempre? Si solo quieres quitarlo de la bandeja, archívalo.')) return
  try {
    await formulariosService.eliminar(detalle.value.id)
    avisos.ok('Envío eliminado.')
    cerrarDetalle()
    cargar(meta.value?.current_page ?? 1)
  } catch (e) { avisos.error(mensajeError(e)) }
}

async function exportar() {
  exportando.value = true
  try {
    await formulariosService.exportar(params())
  } catch (e) {
    avisos.error(mensajeError(e, 'No se ha podido exportar.'))
  } finally {
    exportando.value = false
  }
}

const claseFormulario = f => COLORES_FORMULARIO[f] ?? 'bg-gray-100 text-gray-700'

// Campos propios de cada formulario (columna `datos`) con nombre legible
const ETIQUETAS = {
  centro_nombre: 'Centro educativo', empresa_nombre: 'Empresa', entidad_nombre: 'Entidad', cif: 'CIF', sector: 'Sector',
  tamano: 'Tamaño', colabora: '¿Colabora ya con centros?', direccion: 'Dirección', municipio: 'Municipio', provincia: 'Provincia',
  familias: 'Familias profesionales', familia: 'Familia profesional', intereses: 'Intereses', mensaje: 'Mensaje', comentarios: 'Comentarios',
  fecha_nacimiento: 'Fecha de nacimiento', centro_educativo: 'Centro educativo', ciclo: 'Ciclo', curso: 'Curso',
  tipo_entidad: 'Tipo de entidad', ambito: 'Ámbito',
}
const humanizar = k => ETIQUETAS[k] ?? k.replace(/_/g, ' ').replace(/^./, c => c.toUpperCase())

const camposDetalle = computed(() => {
  const d = detalle.value
  if (!d) return []
  const base = [
    { etiqueta: 'Email', valor: d.email, enlace: `mailto:${d.email}` },
    d.telefono && { etiqueta: 'Teléfono', valor: d.telefono, enlace: `tel:${d.telefono.replace(/\s/g, '')}` },
    d.cargo && { etiqueta: 'Cargo', valor: d.cargo },
    { etiqueta: 'Formulario', valor: d.formulario_nombre },
    d.accion_nombre && { etiqueta: 'Qué pide', valor: d.accion_nombre },
    d.pagina_origen && { etiqueta: 'Enviado desde', valor: d.pagina_origen },
  ].filter(Boolean)
  const extra = Object.entries(d.datos ?? {}).filter(([, v]) => v !== null && v !== '' && !(Array.isArray(v) && !v.length))
    .map(([k, v]) => ({ etiqueta: humanizar(k), valor: Array.isArray(v) ? v.join(', ') : String(v), largo: ['mensaje', 'comentarios', 'familias', 'intereses'].includes(k) }))
  return [...base, ...extra]
})

onMounted(() => {
  cargar(1)
  if (route.query.ver) abrir(Number(route.query.ver))
})
</script>
