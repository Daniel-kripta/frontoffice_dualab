<!-- Lista ordenada de fotos (v-model = array de URLs). Vacía = la web usa sus fotos originales. -->
<template>
  <div>
    <p v-if="!modelValue.length" class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-texto-secundario">
      Se están usando las fotos originales del diseño. Añade fotos para sustituirlas.
    </p>
    <ol v-else class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
      <li v-for="(url, i) in modelValue" :key="url + i" class="group relative">
        <img :src="url" alt="" class="aspect-square w-full rounded-xl object-cover" loading="lazy" />
        <span class="absolute left-1.5 top-1.5 rounded-full bg-white/90 px-2 text-xs font-bold text-azul-noche">{{ i + 1 }}</span>
        <!-- Botones siempre visibles (no dependen del hover: táctil) -->
        <div class="mt-1.5 flex justify-center gap-1">
          <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 hover:text-azul-noche disabled:opacity-30"
                  :disabled="i === 0" :aria-label="`Mover la foto ${i + 1} antes`" @click="mover(i, -1)">
            <ChevronLeftIcon class="h-4 w-4" aria-hidden="true" />
          </button>
          <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 hover:text-azul-noche disabled:opacity-30"
                  :disabled="i === modelValue.length - 1" :aria-label="`Mover la foto ${i + 1} después`" @click="mover(i, 1)">
            <ChevronRightIcon class="h-4 w-4" aria-hidden="true" />
          </button>
          <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 hover:border-red-200 hover:text-red-600"
                  :aria-label="`Quitar la foto ${i + 1}`" @click="quitar(i)">
            <TrashIcon class="h-4 w-4" aria-hidden="true" />
          </button>
        </div>
      </li>
    </ol>
    <div class="mt-3 flex flex-wrap items-center gap-2">
      <button type="button" class="gw-accion-secundaria" :disabled="modelValue.length >= max" @click="abierto = true">
        <PlusIcon class="h-4 w-4" aria-hidden="true" /> Añadir fotos
      </button>
      <button v-if="modelValue.length" type="button" class="gw-accion-peligro" @click="$emit('update:modelValue', [])">Volver a las originales</button>
      <span class="text-xs text-texto-secundario">{{ modelValue.length }} / {{ max }}</span>
    </div>
    <SelectorMedio :abierto="abierto" tipo="imagen" multiple :titulo="titulo" @cerrar="abierto = false" @elegir="anadir" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'
import SelectorMedio from './SelectorMedio.vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  max: { type: Number, default: 40 },
  titulo: { type: String, default: 'Añadir fotos' },
})
const emit = defineEmits(['update:modelValue'])
const abierto = ref(false)

const anadir = medios => emit('update:modelValue', [...props.modelValue, ...medios.map(m => m.url)].slice(0, props.max))
const quitar = i => emit('update:modelValue', props.modelValue.filter((_, j) => j !== i))
function mover(i, paso) {
  const lista = [...props.modelValue]
  ;[lista[i], lista[i + paso]] = [lista[i + paso], lista[i]]
  emit('update:modelValue', lista)
}
</script>
