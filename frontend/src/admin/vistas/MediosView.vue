<template>
  <CabeceraPanel antetitulo="Contenido" titulo="Biblioteca de" destacado="medios" subtitulo="Imágenes, vídeos y documentos que se usan en la web.">
    <label class="gw-accion-principal cursor-pointer">
      <ArrowUpTrayIcon class="h-4 w-4" aria-hidden="true" /> Subir archivos
      <input type="file" class="sr-only" :accept="ACEPTA" multiple @change="alElegir" />
    </label>
  </CabeceraPanel>

  <!-- Espacio usado: el límite lo pone el disco del hosting (CMS_MEDIOS_CUOTA_MB) -->
  <div v-if="uso" class="gw-tarjeta mb-4 p-4">
    <div class="flex flex-wrap items-baseline justify-between gap-2 text-sm">
      <span class="font-semibold text-azul-noche">Espacio usado</span>
      <span class="text-texto-secundario">{{ tamano(uso.usado) }} de {{ tamano(uso.cuota) }} ({{ porcentajeUso }} %)</span>
    </div>
    <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100" role="progressbar" :aria-valuenow="porcentajeUso" aria-valuemin="0" aria-valuemax="100" aria-label="Espacio usado de la biblioteca">
      <div class="h-full rounded-full" :class="porcentajeUso >= 90 ? 'bg-red-600' : porcentajeUso >= 75 ? 'bg-amber-500' : 'bg-administraciones-dark'" :style="{ width: `${Math.min(porcentajeUso, 100)}%` }" />
    </div>
    <p class="gw-ayuda">
      {{ uso.almacenamiento === 'cloudinary' ? 'Los archivos se guardan en Cloudinary (no ocupan el servidor de la web).' : 'Los archivos se guardan en el servidor de la web.' }}
      Las fotos de más de 2400 px se reducen solas al subirlas{{ uso.almacenamiento === 'cloudinary' ? ' y la web las entrega en el formato y tamaño justos para cada navegador' : '' }}.<template v-if="porcentajeUso >= 75"> Queda poco espacio: borra los archivos que ya no se usen.</template></p>
  </div>

  <!-- Almacenamiento (solo admin): conexión con Cloudinary y consumo de la cuenta -->
  <details v-if="sesion.esAdmin" class="gw-tarjeta group mb-4" @toggle="alAbrirAlmacen">
    <summary class="flex cursor-pointer list-none flex-wrap items-center gap-2 p-4 text-sm">
      <ServerStackIcon class="h-5 w-5 shrink-0 text-texto-secundario" aria-hidden="true" />
      <span class="font-semibold text-azul-noche">Almacenamiento y conexión</span>
      <span v-if="almacen" class="gw-chip" :class="estadoConexion.clase">{{ estadoConexion.texto }}</span>
      <span v-if="almacen?.consumo" class="text-texto-secundario">· {{ almacen.consumo.creditos_usados }} de {{ almacen.consumo.creditos_limite }} créditos</span>
      <ChevronDownIcon class="ml-auto h-4 w-4 shrink-0 text-texto-secundario transition-transform group-open:rotate-180" aria-hidden="true" />
    </summary>
    <div class="border-t border-gray-100 p-4 sm:p-5">
      <div v-if="cargandoAlmacen && !almacen" class="h-24 animate-pulse rounded-xl bg-gray-100" aria-busy="true" />
      <template v-else-if="almacen">
        <p v-if="!almacen.conexion.configurado" class="text-sm text-texto-secundario">
          Cloudinary no está configurado: los archivos se guardan en el servidor de la web.
          Para usarlo, añade las variables <code>CLOUDINARY_*</code> en el <code>.env</code> del servidor.
        </p>
        <template v-else>
          <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
            <div class="flex min-w-0 gap-2"><dt class="shrink-0 text-texto-secundario">Servicio:</dt><dd class="min-w-0 truncate text-azul-noche">Cloudinary<template v-if="almacen.consumo"> · plan {{ almacen.consumo.plan }}</template></dd></div>
            <div class="flex min-w-0 gap-2"><dt class="shrink-0 text-texto-secundario">Cuenta:</dt><dd class="min-w-0 truncate text-azul-noche">{{ almacen.conexion.cloud_name }}</dd></div>
            <div class="flex min-w-0 gap-2"><dt class="shrink-0 text-texto-secundario">Carpeta de la web:</dt><dd class="min-w-0 truncate text-azul-noche">{{ almacen.conexion.carpeta }}</dd></div>
            <div v-if="almacen.consumo" class="flex min-w-0 gap-2"><dt class="shrink-0 text-texto-secundario">Consultado:</dt><dd class="min-w-0 truncate text-azul-noche">{{ fechaHora(almacen.consumo.consultado_en) }}</dd></div>
          </dl>

          <template v-if="almacen.consumo">
            <div class="mt-5 flex flex-wrap items-baseline justify-between gap-2 text-sm">
              <span class="font-semibold text-azul-noche">Créditos del mes</span>
              <span class="text-texto-secundario">{{ almacen.consumo.creditos_usados }} de {{ almacen.consumo.creditos_limite }} ({{ porcentajeCreditos }} %)</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100" role="progressbar" :aria-valuenow="porcentajeCreditos" aria-valuemin="0" aria-valuemax="100" aria-label="Créditos de Cloudinary usados">
              <div class="h-full rounded-full" :class="porcentajeCreditos >= 90 ? 'bg-red-600' : porcentajeCreditos >= 75 ? 'bg-amber-500' : 'bg-administraciones-dark'" :style="{ width: `${Math.max(Math.min(porcentajeCreditos, 100), 1)}%` }" />
            </div>
            <ul class="mt-4 grid grid-cols-2 gap-3 text-sm lg:grid-cols-4">
              <li v-for="d in datosConsumo" :key="d.nombre" class="rounded-xl bg-gray-50 p-3">
                <span class="block text-xs text-texto-secundario">{{ d.nombre }}</span>
                <span class="block font-semibold text-azul-noche">{{ d.valor }}</span>
              </li>
            </ul>
            <p class="gw-ayuda mt-3">
              1 crédito = 1 GB guardado, 1 GB servido a los visitantes o 1.000 transformaciones.
              <strong>La cuenta es la misma que la de la app DuaLab</strong>: estas cifras suman las dos.
              Límite por archivo en este plan: imágenes {{ tamano(almacen.consumo.max_imagen_bytes) }}, vídeos {{ tamano(almacen.consumo.max_video_bytes) }}.
              <template v-if="porcentajeCreditos >= 75"> Os acercáis al límite: borrad archivos que no se usen o valorad guardar los de la web en el servidor.</template>
            </p>
          </template>
          <p v-else class="mt-4 text-sm text-red-700">No se ha podido conectar con Cloudinary. Revisa las claves del <code>.env</code> del servidor y vuelve a comprobarlo.</p>

          <div class="mt-4 flex flex-wrap items-center gap-3">
            <button type="button" class="gw-accion-secundaria" :disabled="cargandoAlmacen" @click="comprobarAlmacen">
              <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': cargandoAlmacen }" aria-hidden="true" /> Comprobar conexión
            </button>
            <p class="gw-ayuda !mt-0 min-w-0 flex-1">Las claves de acceso están en el servidor (<code>.env</code>): no se muestran ni se cambian desde el panel.</p>
          </div>
        </template>
      </template>
    </div>
  </details>

  <!-- Zona para arrastrar y soltar -->
  <div class="mb-4 rounded-2xl border-2 border-dashed p-5 text-center transition-colors"
       :class="arrastrando ? 'border-administraciones-dark bg-administraciones-dark/5' : 'border-gray-300 bg-white'"
       @dragover.prevent="arrastrando = true" @dragleave.prevent="arrastrando = false" @drop.prevent="alSoltar">
    <p class="text-sm text-texto-secundario">
      <strong class="text-azul-noche">Arrastra aquí los archivos</strong> o usa «Subir archivos».
      Imágenes JPG, PNG, WebP o GIF (8 MB) · vídeo MP4/WebM (60 MB) · PDF y Office (20 MB).
    </p>
  </div>
  <ListaSubidas class="mb-4" :subidas="subidas" @descartar="descartar" />

  <!-- Filtros -->
  <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center">
    <div class="gw-carril" role="tablist" aria-label="Tipo de archivo">
      <button v-for="t in tipos" :key="t.clave" type="button" role="tab" class="gw-pestana" :class="{ 'gw-pestana--activa': tipo === t.clave }"
              :aria-selected="tipo === t.clave" @click="tipo = t.clave; cargar(1)">{{ t.nombre }}</button>
    </div>
    <div class="relative md:ml-auto md:w-80">
      <label for="gw-buscar-medios" class="sr-only">Buscar archivos</label>
      <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true" />
      <input id="gw-buscar-medios" v-model="buscar" type="search" class="gw-campo pl-9" placeholder="Buscar por nombre o texto alternativo" @input="buscarConRetardo" />
    </div>
  </div>

  <div v-if="error" class="gw-tarjeta p-6 text-center">
    <p class="text-sm text-gray-700">{{ error }}</p>
    <button type="button" class="gw-accion-secundaria mt-4" @click="cargar(1)">Reintentar</button>
  </div>
  <div v-else-if="cargando && !medios.length" class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8" aria-busy="true">
    <span v-for="n in 16" :key="n" class="aspect-square animate-pulse rounded-xl bg-gray-200" />
  </div>
  <div v-else-if="!medios.length" class="gw-tarjeta p-10 text-center">
    <PhotoIcon class="mx-auto h-10 w-10 text-gray-300" aria-hidden="true" />
    <p class="mt-3 text-sm text-gray-700">{{ buscar || tipo ? 'No hay archivos con este filtro.' : 'La biblioteca está vacía. Sube las primeras fotos de la web.' }}</p>
  </div>
  <ul v-else class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8">
    <li v-for="m in medios" :key="m.id">
      <button type="button" class="block w-full rounded-xl text-left ring-offset-2 transition hover:ring-2 hover:ring-administraciones-dark/40" @click="abierto = { ...m }">
        <MiniaturaMedio :medio="m" />
        <span class="mt-1 block truncate text-xs text-texto-secundario">{{ m.titulo || m.nombre_original }}</span>
        <span v-if="m.tipo === 'imagen' && !m.alt" class="mt-0.5 block text-xs font-semibold text-amber-700">Falta texto alternativo</span>
      </button>
    </li>
  </ul>
  <div v-if="meta && meta.current_page < meta.last_page" class="mt-6 text-center">
    <button type="button" class="gw-accion-secundaria" :disabled="cargando" @click="cargar(meta.current_page + 1)">{{ cargando ? 'Cargando…' : 'Cargar más' }}</button>
  </div>

  <!-- Detalle -->
  <VentanaModal :abierto="!!abierto" titulo="Detalles del archivo" ancho="lg" @cerrar="abierto = null">
    <div v-if="abierto" class="grid gap-6 md:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
      <div class="flex items-center justify-center overflow-hidden rounded-2xl bg-gray-100">
        <img v-if="abierto.tipo === 'imagen'" :src="abierto.url" :alt="abierto.alt || ''" class="max-h-[60vh] w-full object-contain" />
        <video v-else-if="abierto.tipo === 'video'" :src="abierto.url" controls class="max-h-[60vh] w-full" />
        <a v-else :href="abierto.url" target="_blank" rel="noopener" class="flex flex-col items-center gap-2 p-10 text-center">
          <DocumentTextIcon class="h-14 w-14 text-texto-secundario" aria-hidden="true" />
          <span class="gw-enlace break-all">Abrir {{ abierto.nombre_original }}</span>
        </a>
      </div>
      <div class="min-w-0 space-y-4">
        <dl class="space-y-1 text-sm">
          <div class="flex gap-2"><dt class="text-texto-secundario">Archivo:</dt><dd class="min-w-0 break-all text-azul-noche">{{ abierto.nombre_original }}</dd></div>
          <div class="flex gap-2"><dt class="text-texto-secundario">Tamaño:</dt><dd class="text-azul-noche">{{ tamano(abierto.tamano) }}<template v-if="abierto.ancho"> · {{ abierto.ancho }} × {{ abierto.alto }} px</template></dd></div>
          <div class="flex gap-2"><dt class="text-texto-secundario">Subido:</dt><dd class="text-azul-noche">{{ fechaHora(abierto.created_at) }}<template v-if="abierto.autor"> · {{ abierto.autor }}</template></dd></div>
        </dl>
        <div v-if="abierto.tipo === 'imagen'">
          <label for="gw-medio-alt" class="gw-etiqueta">Texto alternativo</label>
          <textarea id="gw-medio-alt" v-model="abierto.alt" rows="2" maxlength="255" class="gw-campo" placeholder="Describe la imagen para quien no puede verla" />
          <p class="gw-ayuda">Lo leen los lectores de pantalla y Google. Describe lo que se ve.</p>
        </div>
        <div>
          <label for="gw-medio-titulo" class="gw-etiqueta">Título</label>
          <input id="gw-medio-titulo" v-model="abierto.titulo" type="text" maxlength="255" class="gw-campo" />
        </div>
        <div>
          <label for="gw-medio-url" class="gw-etiqueta">Dirección del archivo</label>
          <div class="flex gap-2">
            <input id="gw-medio-url" :value="abierto.url" type="text" readonly class="gw-campo bg-gray-50" @focus="$event.target.select()" />
            <button type="button" class="gw-accion-icono" aria-label="Copiar dirección" title="Copiar" @click="copiar(abierto.url)">
              <ClipboardDocumentIcon class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>
    </div>
    <template #pie>
      <button type="button" class="gw-accion-peligro mr-auto" @click="eliminar">Eliminar para siempre</button>
      <button type="button" class="gw-accion-secundaria" @click="abierto = null">Cerrar</button>
      <button type="button" class="gw-accion-principal" :disabled="guardando" @click="guardar">{{ guardando ? 'Guardando…' : 'Guardar' }}</button>
    </template>
  </VentanaModal>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import {
  ArrowPathIcon, ArrowUpTrayIcon, ChevronDownIcon, ClipboardDocumentIcon, DocumentTextIcon, MagnifyingGlassIcon, PhotoIcon, ServerStackIcon,
} from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import MiniaturaMedio from '../componentes/MiniaturaMedio.vue'
import ListaSubidas from '../componentes/ListaSubidas.vue'
import VentanaModal from '../componentes/VentanaModal.vue'
import { mediosService } from '@/services/admin/mediosService'
import { mensajeError } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'
import { useSubidaMedios, ACEPTA } from '../composables/useSubidaMedios'
import { fechaHora, tamano } from '../utilidades'
import { usePanelSesion } from '@/stores/panelSesion'

const avisos = useAvisos()
const { subidas, subir, descartar } = useSubidaMedios()

const tipos = [{ clave: '', nombre: 'Todos' }, { clave: 'imagen', nombre: 'Imágenes' }, { clave: 'video', nombre: 'Vídeos' }, { clave: 'documento', nombre: 'Documentos' }]
const tipo = ref('')
const buscar = ref('')
const medios = ref([])
const meta = ref(null)
const cargando = ref(true)
const error = ref('')
const arrastrando = ref(false)
const abierto = ref(null)
const guardando = ref(false)
const uso = ref(null)
const porcentajeUso = computed(() => (uso.value?.cuota ? Math.round((uso.value.usado / uso.value.cuota) * 100) : 0))

// Almacenamiento: se pide al abrir el desplegable (consulta la API de Cloudinary)
const sesion = usePanelSesion()
const almacen = ref(null)
const cargandoAlmacen = ref(false)
const porcentajeCreditos = computed(() => {
  const c = almacen.value?.consumo
  return c?.creditos_limite ? Math.round((c.creditos_usados / c.creditos_limite) * 1000) / 10 : 0
})
const estadoConexion = computed(() => {
  const c = almacen.value?.conexion
  if (!c?.configurado) return { texto: 'En el servidor', clase: 'bg-gray-100 text-texto-secundario' }
  return c.ok ? { texto: 'Cloudinary conectado', clase: 'bg-emerald-50 text-emerald-800' } : { texto: 'Sin conexión', clase: 'bg-red-50 text-red-700' }
})
const datosConsumo = computed(() => {
  const c = almacen.value.consumo
  return [
    { nombre: 'Espacio ocupado', valor: tamano(c.espacio_bytes) },
    { nombre: 'Servido a visitantes', valor: tamano(c.trafico_bytes) },
    { nombre: 'Transformaciones', valor: c.transformaciones.toLocaleString('es-ES') },
    { nombre: 'Archivos', valor: c.archivos.toLocaleString('es-ES') },
  ]
})
async function cargarAlmacen(fn) {
  cargandoAlmacen.value = true
  try { almacen.value = await fn() }
  catch (e) { avisos.error(mensajeError(e)) }
  finally { cargandoAlmacen.value = false }
}
const alAbrirAlmacen = e => { if (e.target.open && !almacen.value) cargarAlmacen(mediosService.almacenamiento) }
const comprobarAlmacen = () => cargarAlmacen(mediosService.comprobarAlmacenamiento)

async function cargar(pagina = 1) {
  cargando.value = true
  error.value = ''
  try {
    const r = await mediosService.listar({ tipo: tipo.value || undefined, buscar: buscar.value || undefined, page: pagina, per_page: 48 })
    medios.value = pagina === 1 ? r.data : [...medios.value, ...r.data]
    meta.value = r.meta
    uso.value = r.uso
  } catch (e) {
    error.value = mensajeError(e, 'No se ha podido cargar la biblioteca.')
  } finally {
    cargando.value = false
  }
}
onMounted(() => cargar(1))

let retardo
function buscarConRetardo() { clearTimeout(retardo); retardo = setTimeout(() => cargar(1), 300) }

const alSubir = (m) => {
  if (uso.value) uso.value.usado += m.tamano
  if (!tipo.value || m.tipo === tipo.value) medios.value.unshift(m)
}
function alElegir(e) { subir([...e.target.files], alSubir); e.target.value = '' }
function alSoltar(e) { arrastrando.value = false; subir([...e.dataTransfer.files], alSubir) }

async function guardar() {
  guardando.value = true
  try {
    const m = await mediosService.actualizar(abierto.value.id, { titulo: abierto.value.titulo, alt: abierto.value.alt })
    medios.value = medios.value.map(x => (x.id === m.id ? m : x))
    avisos.ok('Archivo guardado.')
    abierto.value = null
  } catch (e) { avisos.error(mensajeError(e)) }
  finally { guardando.value = false }
}

async function eliminar() {
  if (!window.confirm('¿Eliminar este archivo para siempre? Si alguna página o entrada lo usa, dejará de verse allí.')) return
  try {
    await mediosService.eliminar(abierto.value.id)
    medios.value = medios.value.filter(x => x.id !== abierto.value.id)
    if (uso.value) uso.value.usado -= abierto.value.tamano
    avisos.ok('Archivo eliminado.')
    abierto.value = null
  } catch (e) { avisos.error(mensajeError(e)) }
}

async function copiar(texto) {
  try { await navigator.clipboard.writeText(texto); avisos.ok('Dirección copiada.') }
  catch { avisos.error('No se ha podido copiar: selecciónala y cópiala a mano.') }
}
</script>
