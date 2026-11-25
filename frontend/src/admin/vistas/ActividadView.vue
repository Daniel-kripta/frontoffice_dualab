<!-- Historial (solo admin): quién ha hecho qué en el panel, agrupado por día. Cada acción se abre
     con su detalle (qué cambió). Mismo nombre que el Historial de DuaLab Central. -->
<template>
  <CabeceraPanel antetitulo="Equipo" titulo="Historial de actividad" subtitulo="Quién ha hecho qué en el panel y cuándo. Pulsa una acción para ver qué cambió. Se guarda durante 2 años." />

  <div class="gw-tarjeta mb-4 grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_10rem_10rem_auto] xl:items-end">
    <div>
      <label for="gw-act-persona" class="gw-etiqueta">Persona</label>
      <select id="gw-act-persona" v-model="filtros.user_id" class="gw-campo" @change="cargar(1)">
        <option value="">Todo el equipo</option>
        <option v-for="p in personas" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
      </select>
    </div>
    <div>
      <label for="gw-act-seccion" class="gw-etiqueta">Sección</label>
      <select id="gw-act-seccion" v-model="filtros.seccion" class="gw-campo" @change="cargar(1)">
        <option value="">Todas</option>
        <option v-for="(n, clave) in secciones" :key="clave" :value="clave">{{ n }}</option>
      </select>
    </div>
    <div>
      <label for="gw-act-desde" class="gw-etiqueta">Desde</label>
      <input id="gw-act-desde" v-model="filtros.desde" type="date" class="gw-campo" @change="cargar(1)" />
    </div>
    <div>
      <label for="gw-act-hasta" class="gw-etiqueta">Hasta</label>
      <input id="gw-act-hasta" v-model="filtros.hasta" type="date" :min="filtros.desde || undefined" class="gw-campo" @change="cargar(1)" />
    </div>
    <button v-if="hayFiltros" type="button" class="gw-accion-secundaria" @click="limpiar">Limpiar filtros</button>
  </div>

  <div v-if="cargando" class="gw-tarjeta space-y-3 p-4" aria-busy="true">
    <span v-for="n in 5" :key="n" class="block h-10 animate-pulse rounded bg-gray-100" />
  </div>
  <p v-else-if="!dias.length" class="gw-tarjeta p-8 text-center text-sm text-texto-secundario">No hay actividad con esos filtros.</p>
  <div v-else class="space-y-4">
    <section v-for="d in dias" :key="d.clave" class="gw-tarjeta overflow-hidden">
      <h2 class="border-b border-gray-100 bg-gray-50 px-4 py-2 text-xs font-bold uppercase tracking-wide text-texto-secundario">{{ d.titulo }}</h2>
      <ul class="divide-y divide-gray-100">
        <li v-for="a in d.items" :key="a.id">
          <!-- Fila a todo el ancho (excepción de forma): se abre el detalle -->
          <button type="button" class="flex w-full items-start gap-3 p-4 text-left hover:bg-gray-50 focus-visible:bg-gray-50" @click="abierta = a">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-azul-noche text-xs font-bold text-white" aria-hidden="true">{{ inicial(a.usuario.nombre) }}</span>
            <span class="min-w-0 flex-1">
              <span class="block break-words text-sm text-azul-noche">
                <strong>{{ a.usuario.nombre }}</strong> {{ a.descripcion }}
              </span>
              <span class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-texto-secundario">
                <span>{{ hora(a.fecha) }}</span>
                <span class="gw-chip bg-gray-100 text-texto-secundario">{{ a.seccion_nombre }}</span>
                <span v-if="a.detalle?.length" class="gw-chip bg-primary-100 text-primary-700">{{ a.detalle.length }} {{ a.detalle.length === 1 ? 'cambio' : 'cambios' }}</span>
              </span>
            </span>
            <ChevronRightIcon class="mt-2 h-4 w-4 shrink-0 text-gray-400" aria-hidden="true" />
          </button>
        </li>
      </ul>
    </section>
  </div>
  <Paginacion class="mt-4" :meta="meta" @ir="cargar" />
  <DetalleActividad :accion="abierta" @cerrar="abierta = null" />
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ChevronRightIcon } from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import DetalleActividad from '../componentes/DetalleActividad.vue'
import Paginacion from '../componentes/Paginacion.vue'
import { actividadService } from '@/services/admin/actividadService'
import { mensajeError } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'
import { fecha } from '../utilidades'

const route = useRoute()
const avisos = useAvisos()
const actividad = ref([])
const meta = ref(null)
const personas = ref([])
const secciones = ref({})
const cargando = ref(true)
const abierta = ref(null) // acción con el detalle abierto
const filtros = reactive({ user_id: typeof route.query.user_id === 'string' ? route.query.user_id : '', seccion: '', desde: '', hasta: '' })
const hayFiltros = computed(() => Object.values(filtros).some(Boolean))

const inicial = nombre => (nombre || '?').trim().charAt(0).toUpperCase()
const hora = iso => new Date(iso).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })

function tituloDia(iso) {
  const d = new Date(iso)
  const hoy = new Date()
  const ayer = new Date(Date.now() - 864e5)
  if (d.toDateString() === hoy.toDateString()) return 'Hoy'
  if (d.toDateString() === ayer.toDateString()) return 'Ayer'
  return fecha(iso)
}
const dias = computed(() => {
  const grupos = []
  for (const a of actividad.value) {
    const clave = new Date(a.fecha).toDateString()
    if (grupos.at(-1)?.clave !== clave) grupos.push({ clave, titulo: tituloDia(a.fecha), items: [] })
    grupos.at(-1).items.push(a)
  }
  return grupos
})

async function cargar(pagina = 1) {
  cargando.value = true
  try {
    const params = Object.fromEntries(Object.entries(filtros).filter(([, v]) => v))
    const r = await actividadService.listar({ ...params, page: pagina })
    actividad.value = r.data
    meta.value = r.meta
    personas.value = r.personas
    secciones.value = r.secciones
  } catch (e) { avisos.error(mensajeError(e)) }
  finally { cargando.value = false }
}
onMounted(cargar)

function limpiar() {
  Object.assign(filtros, { user_id: '', seccion: '', desde: '', hasta: '' })
  cargar(1)
}
</script>
