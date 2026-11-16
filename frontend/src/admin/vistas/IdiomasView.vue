<template>
  <CabeceraPanel antetitulo="Sitio web" titulo="Idiomas" subtitulo="Versiones de la web en otros idiomas (lo que hacía el plugin multidioma)." />

  <div class="grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
    <section class="gw-tarjeta overflow-hidden">
      <ul v-if="cargando" class="divide-y divide-gray-100"><li v-for="n in 2" :key="n" class="p-4"><span class="block h-10 animate-pulse rounded bg-gray-100" /></li></ul>
      <ul v-else class="divide-y divide-gray-100">
        <li v-for="i in idiomas" :key="i.id" class="flex flex-wrap items-center gap-3 p-4">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-azul-noche text-sm font-bold uppercase text-white">{{ i.codigo }}</span>
          <span class="min-w-0 flex-1">
            <span class="block font-semibold text-azul-noche">{{ i.nombre }}
              <span v-if="i.por_defecto" class="gw-chip ml-1 bg-administraciones/10 text-administraciones-dark">Principal</span>
            </span>
            <span class="text-xs text-texto-secundario">{{ i.entradas }} {{ i.entradas === 1 ? 'entrada' : 'entradas' }}</span>
          </span>
          <label v-if="!i.por_defecto" class="flex items-center gap-2 text-sm font-semibold text-azul-noche">
            <input type="checkbox" role="switch" class="gw-interruptor" :checked="i.activo" @change="actualizar(i, { activo: $event.target.checked })" />
            {{ i.activo ? 'Visible en la web' : 'Oculto' }}
          </label>
          <div v-if="!i.por_defecto" class="flex gap-2">
            <button type="button" class="gw-accion-secundaria !h-9" @click="hacerPrincipal(i)">Hacer principal</button>
            <button type="button" class="gw-accion-icono !h-9 !w-9 hover:!border-red-200 hover:!text-red-600" :aria-label="`Quitar ${i.nombre}`" @click="quitar(i)">
              <TrashIcon class="h-4 w-4" aria-hidden="true" />
            </button>
          </div>
        </li>
      </ul>
      <form v-if="disponibles.length" class="flex flex-col gap-2 border-t border-gray-100 bg-gray-50/60 p-4 sm:flex-row sm:items-end" @submit.prevent="anadir">
        <div class="min-w-0 flex-1">
          <label for="gw-nuevo-idioma" class="gw-etiqueta">Añadir idioma</label>
          <select id="gw-nuevo-idioma" v-model="nuevo" class="gw-campo">
            <option value="" disabled>Elige un idioma</option>
            <option v-for="d in disponibles" :key="d.codigo" :value="d.codigo">{{ d.nombre }}</option>
          </select>
        </div>
        <button type="submit" class="gw-accion-principal" :disabled="!nuevo">Añadir</button>
      </form>
    </section>

    <aside class="gw-tarjeta h-fit space-y-3 p-5 text-sm text-gray-700">
      <h2 class="font-heading text-base font-bold text-azul-noche">Cómo funciona</h2>
      <ol class="list-decimal space-y-2 pl-5">
        <li><strong>Añade el idioma.</strong> Empieza oculto, para que puedas traducir sin que se vea.</li>
        <li><strong>Páginas:</strong> en el editor de cada página aparece el selector de idioma. Traduce los textos y su SEO.</li>
        <li><strong>Entradas:</strong> en cada entrada, «Traducciones → Crear traducción» hace una copia en borrador para traducir.</li>
        <li><strong>Publica:</strong> activa el idioma aquí y, en cada página, marca su versión en «Publicación».</li>
      </ol>
      <p class="text-texto-secundario">Lo que no esté traducido se sigue viendo en el idioma principal.</p>
    </aside>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { TrashIcon } from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import { idiomasService } from '@/services/admin/idiomasService'
import { mensajeError } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'

const avisos = useAvisos()
const idiomas = ref([])
const catalogo = ref({})
const cargando = ref(true)
const nuevo = ref('')

const disponibles = computed(() => Object.entries(catalogo.value)
  .filter(([c]) => !idiomas.value.some(i => i.codigo === c)).map(([codigo, nombre]) => ({ codigo, nombre })))

async function cargar() {
  try {
    const r = await idiomasService.listar()
    idiomas.value = r.data
    catalogo.value = r.disponibles
  } catch (e) { avisos.error(mensajeError(e)) }
  finally { cargando.value = false }
}
onMounted(cargar)

async function anadir() {
  try {
    await idiomasService.anadir(nuevo.value)
    avisos.ok('Idioma añadido (oculto hasta que lo actives).')
    nuevo.value = ''
    cargar()
  } catch (e) { avisos.error(mensajeError(e)) }
}
async function actualizar(i, datos) {
  try {
    await idiomasService.actualizar(i.id, datos)
    avisos.ok('Idioma actualizado.')
    cargar()
  } catch (e) { avisos.error(mensajeError(e)); cargar() }
}
function hacerPrincipal(i) {
  if (!window.confirm(`¿Hacer ${i.nombre} el idioma principal de la web? Es el que se ve si el visitante no elige otro.`)) return
  actualizar(i, { por_defecto: true })
}
async function quitar(i) {
  if (!window.confirm(`¿Quitar ${i.nombre}? Se perderán los textos de páginas traducidos a este idioma.`)) return
  try {
    await idiomasService.quitar(i.id)
    avisos.ok('Idioma quitado.')
    cargar()
  } catch (e) { avisos.error(e.response?.data?.message ?? mensajeError(e)) }
}
</script>
