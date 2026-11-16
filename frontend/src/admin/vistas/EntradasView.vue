<template>
  <CabeceraPanel antetitulo="Contenido" titulo="Entradas" subtitulo="Noticias y artículos del blog de la web.">
    <RouterLink :to="{ name: 'panel-categorias' }" class="gw-accion-secundaria">Categorías</RouterLink>
    <RouterLink :to="{ name: 'panel-entrada-nueva' }" class="gw-accion-principal">
      <PlusIcon class="h-4 w-4" aria-hidden="true" /> Añadir nueva
    </RouterLink>
  </CabeceraPanel>

  <!-- Estados (como en WordPress): pestañas con contador -->
  <div class="gw-carril mb-4" role="tablist" aria-label="Estado de las entradas">
    <button v-for="e in estados" :key="e.clave" type="button" role="tab" class="gw-pestana" :class="{ 'gw-pestana--activa': filtros.estado === e.clave }"
            :aria-selected="filtros.estado === e.clave" @click="cambiar('estado', e.clave)">
      {{ e.nombre }} <span class="gw-contador">{{ contadores[e.clave || 'todas'] ?? 0 }}</span>
    </button>
  </div>

  <!-- Filtros -->
  <div class="gw-tarjeta mb-4 flex flex-col gap-3 p-4 md:flex-row md:items-end">
    <div class="min-w-0 flex-1">
      <label for="gw-buscar-entradas" class="gw-etiqueta">Buscar</label>
      <div class="relative">
        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true" />
        <input id="gw-buscar-entradas" v-model="filtros.buscar" type="search" class="gw-campo pl-9" placeholder="Título de la entrada" @input="buscarConRetardo" />
      </div>
    </div>
    <div class="md:w-56">
      <label for="gw-cat" class="gw-etiqueta">Categoría</label>
      <select id="gw-cat" v-model="filtros.categoria_id" class="gw-campo" @change="cargar(1)">
        <option value="">Todas</option>
        <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
      </select>
    </div>
    <div v-if="idiomas.length > 1" class="md:w-44">
      <label for="gw-idioma" class="gw-etiqueta">Idioma</label>
      <select id="gw-idioma" v-model="filtros.idioma" class="gw-campo" @change="cargar(1)">
        <option value="">Todos</option>
        <option v-for="i in idiomas" :key="i.codigo" :value="i.codigo">{{ i.nombre }}</option>
      </select>
    </div>
    <button v-if="hayFiltros" type="button" class="gw-accion-secundaria" @click="limpiar">Limpiar filtros</button>
  </div>

  <div v-if="error" class="gw-tarjeta p-6 text-center">
    <p class="text-sm text-gray-700">{{ error }}</p>
    <button type="button" class="gw-accion-secundaria mt-4" @click="cargar(1)">Reintentar</button>
  </div>

  <div v-else class="gw-tarjeta overflow-hidden">
    <ul v-if="cargando" class="divide-y divide-gray-100"><li v-for="n in 5" :key="n" class="p-4"><span class="block h-10 animate-pulse rounded bg-gray-100" /></li></ul>

    <div v-else-if="!entradas.length" class="p-10 text-center">
      <NewspaperIcon class="mx-auto h-10 w-10 text-gray-300" aria-hidden="true" />
      <p class="mt-3 text-sm text-gray-700">
        {{ hayFiltros || filtros.estado ? 'No hay entradas con estos filtros.' : 'Todavía no hay entradas. Escribe la primera noticia del blog.' }}
      </p>
      <button v-if="hayFiltros || filtros.estado" type="button" class="gw-accion-secundaria mt-4" @click="limpiar(true)">Ver todas</button>
      <RouterLink v-else :to="{ name: 'panel-entrada-nueva' }" class="gw-accion-principal mt-4">Añadir nueva</RouterLink>
    </div>

    <ul v-else class="divide-y divide-gray-100">
      <li v-for="e in entradas" :key="e.id" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <div class="flex min-w-0 flex-1 items-center gap-3">
          <span class="hidden h-14 w-20 shrink-0 overflow-hidden rounded-lg bg-gray-100 sm:block">
            <img v-if="e.imagen_destacada" :src="e.imagen_destacada" alt="" class="h-full w-full object-cover" loading="lazy" />
          </span>
          <div class="min-w-0">
            <component :is="e.en_papelera ? 'span' : 'RouterLink'" :to="e.en_papelera ? undefined : { name: 'panel-entrada', params: { id: e.id } }"
                       class="block break-words font-semibold text-azul-noche" :class="{ 'hover:text-administraciones-dark': !e.en_papelera }">
              {{ e.titulo }}
            </component>
            <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-texto-secundario">
              <span class="gw-chip" :class="ESTADOS_ENTRADA[e.estado]?.clase">{{ ESTADOS_ENTRADA[e.estado]?.nombre }}</span>
              <span v-if="e.categoria" class="gw-chip" :class="claseCategoria(e.categoria.color)">{{ e.categoria.nombre }}</span>
              <span v-if="idiomas.length > 1" class="font-bold uppercase">{{ e.idioma }}</span>
              <span>{{ e.estado === 'borrador' ? 'Editada ' + haceCuanto(e.updated_at) : fechaHora(e.publicada_en) }}</span>
              <span v-if="e.autor">· {{ e.autor }}</span>
            </p>
          </div>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2 sm:justify-end">
          <template v-if="e.en_papelera">
            <button type="button" class="gw-accion-secundaria !h-9" @click="restaurar(e)">Restaurar</button>
            <button type="button" class="gw-accion-peligro !h-9" @click="eliminar(e)">Eliminar para siempre</button>
          </template>
          <template v-else>
            <a v-if="e.estado === 'publicada'" :href="`/noticias/${e.slug}`" target="_blank" rel="noopener" class="gw-accion-icono !h-9 !w-9"
               :aria-label="`Ver «${e.titulo}» en la web`" title="Ver en la web">
              <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" />
            </a>
            <button v-if="e.estado === 'pendiente' && e.permisos?.aprobar" type="button" class="gw-accion-principal !h-9" @click="aprobar(e)">
              <CheckIcon class="h-4 w-4" aria-hidden="true" /> Aprobar
            </button>
            <RouterLink :to="{ name: 'panel-entrada', params: { id: e.id } }" class="gw-accion-secundaria !h-9">
              {{ e.permisos?.editar ? 'Editar' : 'Ver' }}
            </RouterLink>
            <button v-if="e.permisos?.editar" type="button" class="gw-accion-icono !h-9 !w-9 hover:!border-red-200 hover:!text-red-600" :aria-label="`Mover «${e.titulo}» a la papelera`"
                    title="Mover a la papelera" @click="aPapelera(e)">
              <TrashIcon class="h-4 w-4" aria-hidden="true" />
            </button>
          </template>
        </div>
      </li>
    </ul>
  </div>
  <Paginacion :meta="meta" @ir="cargar" />
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { PlusIcon, MagnifyingGlassIcon, NewspaperIcon, ArrowTopRightOnSquareIcon, TrashIcon, CheckIcon } from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import Paginacion from '../componentes/Paginacion.vue'
import { entradasService } from '@/services/admin/entradasService'
import { categoriasService } from '@/services/admin/categoriasService'
import { idiomasService } from '@/services/admin/idiomasService'
import { mensajeError } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'
import { claseCategoria } from '@/data/coloresCategoria'
import { ESTADOS_ENTRADA, fechaHora, haceCuanto } from '../utilidades'

const route = useRoute()
const router = useRouter()
const avisos = useAvisos()

const estados = [
  { clave: '', nombre: 'Todas' }, { clave: 'pendiente', nombre: 'Pendientes de aprobar' }, { clave: 'publicada', nombre: 'Publicadas' }, { clave: 'programada', nombre: 'Programadas' },
  { clave: 'borrador', nombre: 'Borradores' }, { clave: 'papelera', nombre: 'Papelera' },
]

// Filtros en la URL (?estado=…&buscar=…) para que «volver» los conserve
const filtros = reactive({
  estado: route.query.estado ?? '', buscar: route.query.buscar ?? '', categoria_id: route.query.categoria_id ?? '', idioma: route.query.idioma ?? '',
})
const entradas = ref([])
const meta = ref(null)
const contadores = ref({})
const categorias = ref([])
const idiomas = ref([])
const cargando = ref(true)
const error = ref('')

const hayFiltros = computed(() => !!(filtros.buscar || filtros.categoria_id || filtros.idioma))

async function cargar(pagina = 1) {
  cargando.value = true
  error.value = ''
  const params = Object.fromEntries(Object.entries({ ...filtros, page: pagina }).filter(([, v]) => v !== '' && v !== null))
  router.replace({ query: Object.fromEntries(Object.entries(params).filter(([k]) => k !== 'page')) })
  try {
    const r = await entradasService.listar(params)
    entradas.value = r.data
    meta.value = r.meta
    contadores.value = r.contadores
  } catch (e) {
    error.value = mensajeError(e, 'No se han podido cargar las entradas.')
  } finally {
    cargando.value = false
  }
}

function cambiar(campo, valor) { filtros[campo] = valor; cargar(1) }
function limpiar(todo = false) {
  Object.assign(filtros, { buscar: '', categoria_id: '', idioma: '' })
  if (todo === true) filtros.estado = ''
  cargar(1)
}
let retardo
function buscarConRetardo() { clearTimeout(retardo); retardo = setTimeout(() => cargar(1), 350) }

async function aPapelera(e) {
  try {
    await entradasService.aPapelera(e.id)
    avisos.ok('Entrada movida a la papelera.')
    cargar(meta.value?.current_page ?? 1)
  } catch (err) { avisos.error(mensajeError(err)) }
}
async function aprobar(e) {
  try {
    const r = await entradasService.aprobar(e.id)
    avisos.ok(r.estado === 'programada' ? 'Aprobada: se publicará en su fecha.' : 'Aprobada y publicada en la web.')
    cargar(meta.value?.current_page ?? 1)
  } catch (err) { avisos.error(mensajeError(err)) }
}
async function restaurar(e) {
  try {
    await entradasService.restaurar(e.id)
    avisos.ok('Entrada restaurada.')
    cargar(meta.value?.current_page ?? 1)
  } catch (err) { avisos.error(mensajeError(err)) }
}
async function eliminar(e) {
  if (!window.confirm(`¿Eliminar «${e.titulo}» para siempre? No se puede deshacer.`)) return
  try {
    await entradasService.eliminarDefinitivamente(e.id)
    avisos.ok('Entrada eliminada.')
    cargar(meta.value?.current_page ?? 1)
  } catch (err) { avisos.error(mensajeError(err)) }
}

onMounted(() => {
  cargar(1)
  categoriasService.listar().then(r => { categorias.value = r.data }).catch(() => {})
  idiomasService.listar().then(r => { idiomas.value = r.data }).catch(() => {})
})
</script>
