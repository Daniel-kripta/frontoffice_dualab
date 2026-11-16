<!-- Campo de imagen: vista previa + elegir de la biblioteca + quitar. v-model = URL. -->
<template>
  <div>
    <div class="flex gap-3" :class="compacto ? 'items-center' : 'flex-col sm:flex-row sm:items-center'">
      <div class="relative shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-gray-50"
           :class="compacto ? 'h-16 w-20' : 'h-28 w-full sm:w-44'">
        <img v-if="modelValue || original" :src="modelValue || original" alt="" class="h-full w-full" :class="compacto ? 'object-contain p-1' : 'object-cover'" />
        <span v-else class="flex h-full items-center justify-center p-2 text-center text-xs text-texto-secundario">{{ textoVacio }}</span>
        <span v-if="!modelValue && original" class="absolute left-2 top-2 rounded-full bg-white/90 px-2 py-0.5 text-xs font-bold text-texto-secundario">Original</span>
      </div>
      <div class="flex min-w-0 flex-wrap gap-2">
        <button type="button" class="gw-accion-secundaria" :class="{ '!h-9 !px-4': compacto }" @click="abierto = true">
          <PhotoIcon class="h-4 w-4" aria-hidden="true" /> {{ modelValue ? 'Cambiar imagen' : 'Elegir imagen' }}
        </button>
        <button v-if="modelValue" type="button" class="gw-accion-peligro" :class="{ '!h-9 !px-4': compacto }" @click="$emit('update:modelValue', '')">
          {{ original || hayOriginal ? 'Volver a la original' : 'Quitar' }}
        </button>
      </div>
    </div>
    <SelectorMedio :abierto="abierto" tipo="imagen" :titulo="tituloSelector" @cerrar="abierto = false" @elegir="m => $emit('update:modelValue', m.url)" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { PhotoIcon } from '@heroicons/vue/24/outline'
import SelectorMedio from './SelectorMedio.vue'

defineProps({
  modelValue: { type: String, default: '' },
  original: { type: String, default: '' }, // imagen que se ve si no se elige otra
  tituloSelector: { type: String, default: 'Elegir imagen' },
  textoVacio: { type: String, default: 'Sin imagen' },
  // Si al quitar la imagen la web vuelve a una del diseño, el botón lo dice
  hayOriginal: { type: Boolean, default: false },
  compacto: { type: Boolean, default: false }, // miniatura pequeña, para columnas estrechas
})
defineEmits(['update:modelValue'])

const abierto = ref(false)
</script>
