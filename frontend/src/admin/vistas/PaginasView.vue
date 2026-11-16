<template>
  <CabeceraPanel antetitulo="Contenido" titulo="Páginas" subtitulo="Textos, botones, fotos y SEO de cada página de la web. Las subpáginas aparecen bajo su página principal." />

  <div v-if="error" class="gw-tarjeta p-6 text-center">
    <p class="text-sm text-gray-700">{{ error }}</p>
    <button type="button" class="gw-accion-secundaria mt-4" @click="cargar">Reintentar</button>
  </div>

  <div v-else class="gw-tarjeta overflow-hidden">
    <!-- Escritorio: tabla -->
    <div class="hidden overflow-x-auto md:block">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-wider text-texto-secundario">
          <tr>
            <th scope="col" class="px-5 py-3">Página</th>
            <th scope="col" class="px-5 py-3">Estado</th>
            <th scope="col" class="px-5 py-3">Textos editados</th>
            <th scope="col" class="px-5 py-3">SEO</th>
            <th scope="col" class="px-5 py-3">Última edición</th>
            <th scope="col" class="px-5 py-3"><span class="sr-only">Acciones</span></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <template v-if="cargando">
            <tr v-for="n in 6" :key="n"><td colspan="6" class="px-5 py-3"><span class="block h-6 animate-pulse rounded bg-gray-100" /></td></tr>
          </template>
          <tr v-for="p in ordenadas" v-else :key="p.clave" class="group hover:bg-gray-50">
            <td class="px-5 py-3">
              <RouterLink :to="{ name: 'panel-pagina', params: { clave: p.clave } }" class="flex items-center gap-2 font-semibold text-azul-noche hover:text-administraciones-dark">
                <span v-if="p.nivel" class="text-gray-400" aria-hidden="true">—</span>
                {{ p.titulo }}
              </RouterLink>
              <span class="block pl-0 text-xs text-texto-secundario" :class="{ 'pl-5': p.nivel }">{{ p.ruta ?? 'Bloque que aparece en varias páginas' }}</span>
            </td>
            <td class="px-5 py-3">
              <span class="gw-chip" :class="p.publicada ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700'">{{ p.publicada ? 'Publicada' : 'Oculta' }}</span>
            </td>
            <td class="px-5 py-3 text-texto-secundario">
              <template v-if="p.num_campos">{{ p.campos_editados }} de {{ p.num_campos }}</template>
              <template v-else>Solo SEO</template>
            </td>
            <td class="px-5 py-3">
              <span v-if="!p.ruta" class="text-texto-secundario">—</span>
              <span v-else class="gw-chip" :class="p.seo_completo ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'">
                {{ p.seo_completo ? '✓ Completo' : 'Falta título o descripción' }}
              </span>
            </td>
            <td class="px-5 py-3 text-texto-secundario">{{ p.editada_en ? haceCuanto(p.editada_en) : 'Original' }}</td>
            <td class="px-5 py-3 text-right">
              <div class="flex justify-end gap-2">
                <a v-if="enlacePublico(p)" :href="enlacePublico(p)" target="_blank" rel="noopener" class="gw-accion-icono !h-9 !w-9" :title="`Ver ${p.titulo} en la web`" :aria-label="`Ver ${p.titulo} en la web`">
                  <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" />
                </a>
                <RouterLink :to="{ name: 'panel-pagina', params: { clave: p.clave } }" class="gw-accion-secundaria !h-9">Editar</RouterLink>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Móvil: tarjetas -->
    <ul class="divide-y divide-gray-100 md:hidden">
      <template v-if="cargando"><li v-for="n in 5" :key="n" class="p-4"><span class="block h-10 animate-pulse rounded bg-gray-100" /></li></template>
      <li v-for="p in ordenadas" v-else :key="p.clave">
        <RouterLink :to="{ name: 'panel-pagina', params: { clave: p.clave } }" class="flex items-center gap-3 p-4" :class="{ 'pl-8': p.nivel }">
          <span class="min-w-0 flex-1">
            <span class="block truncate font-semibold text-azul-noche">{{ p.titulo }}</span>
            <span class="block truncate text-xs text-texto-secundario">{{ p.ruta ?? 'Bloque común' }} · {{ p.editada_en ? haceCuanto(p.editada_en) : 'Original' }}</span>
          </span>
          <span v-if="!p.publicada" class="gw-chip bg-gray-100 text-gray-700">Oculta</span>
          <ChevronRightIcon class="h-5 w-5 shrink-0 text-gray-400" aria-hidden="true" />
        </RouterLink>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ArrowTopRightOnSquareIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import { paginasService } from '@/services/admin/paginasService'
import { mensajeError } from '@/services/admin/panelApi'
import { haceCuanto } from '../utilidades'

const paginas = ref([])
const cargando = ref(true)
const error = ref('')

async function cargar() {
  cargando.value = true
  error.value = ''
  try {
    paginas.value = await paginasService.listar()
  } catch (e) {
    error.value = mensajeError(e, 'No se han podido cargar las páginas.')
  } finally {
    cargando.value = false
  }
}
onMounted(cargar)

// Árbol aplanado: cada página principal seguida de sus subpáginas
const ordenadas = computed(() => {
  const raiz = paginas.value.filter(p => !p.padre)
  return raiz.flatMap(p => [{ ...p, nivel: 0 }, ...paginas.value.filter(h => h.padre === p.clave).map(h => ({ ...h, nivel: 1 }))])
})

const enlacePublico = p => (p.ruta && !p.ruta.includes(':') ? p.ruta : null)
</script>
