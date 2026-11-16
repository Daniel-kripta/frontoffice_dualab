<!-- Elegir un archivo de la biblioteca (o subir uno nuevo) desde cualquier editor. -->
<template>
  <VentanaModal :abierto="abierto" :titulo="titulo" subtitulo="Elige un archivo de la biblioteca o sube uno nuevo" ancho="lg" @cerrar="$emit('cerrar')">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
      <div class="min-w-0 flex-1">
        <label for="gw-selector-buscar" class="gw-etiqueta">Buscar</label>
        <input id="gw-selector-buscar" v-model="buscar" type="search" class="gw-campo" placeholder="Nombre, título o texto alternativo" @input="buscarConRetardo" />
      </div>
      <label class="gw-accion-secundaria cursor-pointer">
        <ArrowUpTrayIcon class="h-4 w-4" aria-hidden="true" /> Subir archivo
        <input type="file" class="sr-only" :accept="ACEPTA" multiple @change="alElegirArchivos" />
      </label>
    </div>

    <ListaSubidas class="mt-4" :subidas="subidas" @descartar="descartar" />

    <div v-if="cargando && !medios.length" class="mt-5 grid grid-cols-2 gap-3 min-[400px]:grid-cols-3 sm:grid-cols-5 lg:grid-cols-6" aria-busy="true">
      <span v-for="n in 12" :key="n" class="aspect-square animate-pulse rounded-xl bg-gray-200" />
    </div>
    <p v-else-if="!medios.length" class="mt-8 text-center text-sm text-texto-secundario">
      No hay archivos{{ buscar ? ' que coincidan con la búsqueda' : '' }}. Sube el primero con «Subir archivo».
    </p>
    <ul v-else class="mt-5 grid grid-cols-2 gap-3 min-[400px]:grid-cols-3 sm:grid-cols-5 lg:grid-cols-6">
      <li v-for="m in medios" :key="m.id">
        <button type="button" class="block w-full rounded-xl ring-offset-2 transition hover:ring-2 hover:ring-administraciones-dark/40"
                :class="{ 'ring-2 ring-administraciones-dark': estaElegido(m) }"
                :aria-pressed="estaElegido(m)" :aria-label="m.titulo || m.nombre_original" @click="alternar(m)">
          <span class="relative block">
            <MiniaturaMedio :medio="m" />
            <span v-if="multiple && estaElegido(m)" class="absolute right-1.5 top-1.5 flex h-6 min-w-6 items-center justify-center rounded-full bg-administraciones-dark px-1.5 text-xs font-bold text-white">
              {{ elegidos.findIndex(x => x.id === m.id) + 1 }}
            </span>
          </span>
        </button>
      </li>
    </ul>
    <div v-if="meta && meta.current_page < meta.last_page" class="mt-5 text-center">
      <button type="button" class="gw-accion-secundaria" :disabled="cargando" @click="cargar(meta.current_page + 1)">Cargar más</button>
    </div>

    <template #pie>
      <p v-if="multiple && elegidos.length" class="mr-auto text-sm text-texto-secundario">{{ elegidos.length }} {{ elegidos.length === 1 ? 'foto elegida' : 'fotos elegidas' }}</p>
      <p v-else-if="elegido" class="mr-auto min-w-0 truncate text-sm text-texto-secundario">{{ elegido.titulo || elegido.nombre_original }}</p>
      <button type="button" class="gw-accion-secundaria" @click="$emit('cerrar')">Cancelar</button>
      <button type="button" class="gw-accion-principal" :disabled="multiple ? !elegidos.length : !elegido" @click="confirmar">
        {{ multiple ? 'Añadir las elegidas' : 'Usar este archivo' }}
      </button>
    </template>
  </VentanaModal>
</template>

<script setup>
import { ref, watch } from 'vue'
import { ArrowUpTrayIcon } from '@heroicons/vue/24/outline'
import VentanaModal from './VentanaModal.vue'
import MiniaturaMedio from './MiniaturaMedio.vue'
import ListaSubidas from './ListaSubidas.vue'
import { mediosService } from '@/services/admin/mediosService'
import { useSubidaMedios, ACEPTA } from '../composables/useSubidaMedios'

const props = defineProps({
  abierto: { type: Boolean, default: false },
  tipo: { type: String, default: 'imagen' }, // imagen | video | documento | '' (todos)
  titulo: { type: String, default: 'Biblioteca de medios' },
  // Varias a la vez (galerías): emite `elegir` con la lista en el orden en que se marcaron
  multiple: { type: Boolean, default: false },
})
const emit = defineEmits(['cerrar', 'elegir'])

const medios = ref([])
const meta = ref(null)
const cargando = ref(false)
const buscar = ref('')
const elegido = ref(null)
const elegidos = ref([])
const estaElegido = m => (props.multiple ? elegidos.value.some(x => x.id === m.id) : elegido.value?.id === m.id)
function alternar(m) {
  if (!props.multiple) { elegido.value = m; return }
  elegidos.value = estaElegido(m) ? elegidos.value.filter(x => x.id !== m.id) : [...elegidos.value, m]
}
const { subidas, subir, descartar } = useSubidaMedios()

async function cargar(pagina = 1) {
  cargando.value = true
  try {
    const r = await mediosService.listar({ tipo: props.tipo || undefined, buscar: buscar.value || undefined, page: pagina, per_page: 36 })
    medios.value = pagina === 1 ? r.data : [...medios.value, ...r.data]
    meta.value = r.meta
  } catch {
    medios.value = []
  } finally {
    cargando.value = false
  }
}

let retardo
function buscarConRetardo() {
  clearTimeout(retardo)
  retardo = setTimeout(() => cargar(1), 300)
}

function alElegirArchivos(e) {
  subir([...e.target.files], (medio) => {
    if (!props.tipo || medio.tipo === props.tipo) {
      medios.value.unshift(medio)
      if (props.multiple) elegidos.value.push(medio)
      else elegido.value = medio
    }
  })
  e.target.value = ''
}

function confirmar() {
  emit('elegir', props.multiple ? elegidos.value : elegido.value)
  emit('cerrar')
}

watch(() => props.abierto, (a) => { if (a) { elegido.value = null; elegidos.value = []; cargar(1) } })
</script>
