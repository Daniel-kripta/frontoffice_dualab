<template>
  <CabeceraPanel antetitulo="Gestión web" titulo="Hola," :destacado="nombre" subtitulo="Resumen de la web: lo último que ha llegado y lo que tienes a medias.">
    <RouterLink :to="{ name: 'panel-entrada-nueva' }" class="gw-accion-principal">
      <PlusIcon class="h-4 w-4" aria-hidden="true" /> Nueva entrada
    </RouterLink>
  </CabeceraPanel>

  <div v-if="error" class="gw-tarjeta p-6 text-center">
    <p class="text-sm text-gray-700">{{ error }}</p>
    <button type="button" class="gw-accion-secundaria mt-4" @click="cargar">Reintentar</button>
  </div>

  <template v-else>
    <!-- Cifras -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
      <RouterLink v-for="c in cifras" :key="c.titulo" :to="c.to" class="gw-tarjeta block p-4 transition hover:border-administraciones-dark/30 hover:shadow-md sm:p-5">
        <component :is="c.icono" class="h-6 w-6" :class="c.color" aria-hidden="true" />
        <p class="mt-3 font-heading text-2xl font-bold text-azul-noche sm:text-3xl">
          <span v-if="cargando" class="inline-block h-8 w-12 animate-pulse rounded bg-gray-200" />
          <template v-else>{{ c.valor }}</template>
        </p>
        <p class="mt-1 text-sm font-semibold text-azul-noche">{{ c.titulo }}</p>
        <p class="text-xs text-texto-secundario">{{ cargando ? '…' : c.detalle }}</p>
      </RouterLink>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
      <!-- Formularios y correo: se atienden en DuaLab Central -->
      <section class="gw-tarjeta flex flex-col justify-center p-5">
        <h2 class="font-heading text-base font-bold text-azul-noche">Formularios y correo</h2>
        <p class="mt-1 text-sm text-texto-secundario">Los formularios de la web y el correo de info@ se atienden en DuaLab Central, en «Primer contacto».</p>
        <a :href="URL_PRIMER_CONTACTO" target="_blank" rel="noopener" class="gw-accion-secundaria mt-4 inline-flex w-fit">
          Abrir DuaLab Central <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" />
          <span class="sr-only">(se abre en otra pestaña)</span>
        </a>
      </section>

      <!-- Entradas recientes -->
      <section class="gw-tarjeta p-5">
        <div class="mb-3 flex items-center justify-between gap-2">
          <h2 class="font-heading text-base font-bold text-azul-noche">Entradas recientes</h2>
          <RouterLink :to="{ name: 'panel-entradas' }" class="gw-enlace text-sm">Ver todas</RouterLink>
        </div>
        <ul v-if="cargando" class="space-y-3"><li v-for="n in 4" :key="n" class="h-10 animate-pulse rounded-xl bg-gray-100" /></ul>
        <div v-else-if="!datos.entradas.ultimas.length" class="py-6 text-center">
          <p class="text-sm text-texto-secundario">Aún no hay entradas en el blog.</p>
          <RouterLink :to="{ name: 'panel-entrada-nueva' }" class="gw-accion-secundaria mt-3">Escribir la primera</RouterLink>
        </div>
        <ul v-else class="divide-y divide-gray-100">
          <li v-for="e in datos.entradas.ultimas" :key="e.id">
            <RouterLink :to="{ name: 'panel-entrada', params: { id: e.id } }" class="-mx-2 flex items-center gap-3 rounded-xl px-2 py-2.5 hover:bg-gray-50">
              <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-medium text-azul-noche">{{ e.titulo }}</span>
                <span class="block text-xs text-texto-secundario">Editada {{ haceCuanto(e.updated_at) }}</span>
              </span>
              <span class="gw-chip shrink-0" :class="ESTADOS_ENTRADA[e.estado]?.clase">{{ ESTADOS_ENTRADA[e.estado]?.nombre }}</span>
            </RouterLink>
          </li>
        </ul>
      </section>
    </div>

    <!-- Accesos rápidos y configuración -->
    <div class="mt-6 grid gap-6 lg:grid-cols-[2fr_3fr]">
      <section class="gw-tarjeta p-5">
        <h2 class="mb-3 font-heading text-base font-bold text-azul-noche">Accesos rápidos</h2>
        <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
          <li v-for="a in accesos" :key="a.texto">
            <RouterLink :to="a.to" class="flex items-center gap-3 rounded-xl border border-gray-200 p-3 text-sm font-semibold text-azul-noche hover:border-administraciones-dark/30 hover:bg-administraciones-dark/5">
              <component :is="a.icono" class="h-5 w-5 shrink-0 text-administraciones-dark" aria-hidden="true" /> {{ a.texto }}
            </RouterLink>
          </li>
        </ul>
      </section>
      <section class="gw-tarjeta p-5">
        <h2 class="font-heading text-base font-bold text-azul-noche">Puesta a punto</h2>
        <p class="mb-3 text-sm text-texto-secundario">Lo que conviene dejar configurado (sustituye a los plugins de WordPress).</p>
        <ul class="divide-y divide-gray-100">
          <li v-for="t in tareas" :key="t.texto" class="flex flex-wrap items-center gap-3 py-2.5">
            <CheckCircleIcon v-if="t.hecho" class="h-5 w-5 shrink-0 text-emerald-600" aria-hidden="true" />
            <span v-else class="h-5 w-5 shrink-0 rounded-full border-2 border-gray-300" aria-hidden="true" />
            <span class="min-w-0 flex-1 text-sm">
              <span class="font-semibold text-azul-noche">{{ t.texto }}</span>
              <span class="block text-xs text-texto-secundario">{{ t.hecho ? 'Hecho' : t.ayuda }}</span>
            </span>
            <RouterLink v-if="!t.soloAdmin || sesion.esAdmin" :to="t.to" class="gw-enlace text-sm">{{ t.hecho ? 'Revisar' : 'Configurar' }}</RouterLink>
          </li>
        </ul>
      </section>
    </div>
  </template>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import {
  PlusIcon, ArrowTopRightOnSquareIcon, NewspaperIcon, DocumentDuplicateIcon, PhotoIcon, CheckCircleIcon,
  PencilSquareIcon, ArrowUpTrayIcon, MagnifyingGlassCircleIcon, HomeModernIcon,
} from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import { URL_PRIMER_CONTACTO } from '../central'
import { escritorioService } from '@/services/admin/escritorioService'
import { mensajeError } from '@/services/admin/panelApi'
import { usePanelSesion } from '@/stores/panelSesion'
import { haceCuanto, tamano, ESTADOS_ENTRADA } from '../utilidades'

const sesion = usePanelSesion()
const nombre = computed(() => sesion.usuario?.name?.split(' ')[0] ?? '')

const vacio = { entradas: { publicadas: 0, borradores: 0, pendientes: 0, ultimas: [] }, paginas: { total: 0, editadas: 0 }, medios: { total: 0, tamano: 0 }, idiomas: {}, configuracion: {} }
const datos = ref(vacio)
const cargando = ref(true)
const error = ref('')

async function cargar() {
  cargando.value = true
  error.value = ''
  try {
    datos.value = await escritorioService.resumen()
  } catch (e) {
    error.value = mensajeError(e, 'No se ha podido cargar el resumen.')
  } finally {
    cargando.value = false
  }
}
onMounted(cargar)

const cifras = computed(() => [
  {
    titulo: 'Entradas publicadas', valor: datos.value.entradas.publicadas, icono: NewspaperIcon, color: 'text-centros',
    detalle: datos.value.entradas.pendientes ? `${datos.value.entradas.pendientes} pendientes de aprobar` : `${datos.value.entradas.borradores} en borrador`,
    to: { name: 'panel-entradas', query: datos.value.entradas.pendientes ? { estado: 'pendiente' } : {} },
  },
  { titulo: 'Páginas', valor: datos.value.paginas.total, detalle: `${datos.value.paginas.editadas} con textos editados`, icono: DocumentDuplicateIcon, color: 'text-empresas', to: { name: 'panel-paginas' } },
  { titulo: 'Archivos', valor: datos.value.medios.total, detalle: `${tamano(datos.value.medios.tamano)} en la biblioteca`, icono: PhotoIcon, color: 'text-administraciones-dark', to: { name: 'panel-medios' } },
])

const accesos = [
  { texto: 'Escribir una noticia', icono: PencilSquareIcon, to: { name: 'panel-entrada-nueva' } },
  { texto: 'Editar la página de inicio', icono: HomeModernIcon, to: { name: 'panel-pagina', params: { clave: 'inicio' } } },
  { texto: 'Subir fotos o documentos', icono: ArrowUpTrayIcon, to: { name: 'panel-medios' } },
  { texto: 'Revisar el SEO del sitio', icono: MagnifyingGlassCircleIcon, to: { name: 'panel-seo' } },
]

const tareas = computed(() => [
  { texto: 'SEO general del sitio', ayuda: 'Título del sitio, descripción e imagen para redes.', hecho: datos.value.configuracion.seo, to: { name: 'panel-seo' } },
  { texto: 'Google Analytics', ayuda: 'Pega el ID de medición (antes en Site Kit). Search Console se verifica en SEO.', hecho: datos.value.configuracion.analitica, to: { name: 'panel-analitica' }, soloAdmin: true },
  { texto: 'Formularios conectados al CRM', ayuda: 'Webhook hacia tu CRM o herramienta de email.', hecho: datos.value.configuracion.integraciones, to: { name: 'panel-integraciones' }, soloAdmin: true },
  { texto: 'Idiomas de la web', ayuda: 'Solo español por ahora.', hecho: Object.keys(datos.value.idiomas ?? {}).length > 1, to: { name: 'panel-idiomas' }, soloAdmin: true },
])
</script>
