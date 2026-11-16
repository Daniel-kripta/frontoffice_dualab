<template>
  <nav v-if="meta && meta.last_page > 1" class="mt-5 flex flex-wrap items-center justify-between gap-3" aria-label="Paginación">
    <p class="text-sm text-texto-secundario">
      {{ meta.from }}–{{ meta.to }} de {{ meta.total }}
    </p>
    <div class="flex items-center gap-2">
      <button type="button" class="gw-accion-icono" :disabled="meta.current_page <= 1" aria-label="Página anterior"
              :class="{ 'opacity-40': meta.current_page <= 1 }" @click="$emit('ir', meta.current_page - 1)">
        <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
      </button>
      <span class="text-sm font-semibold text-azul-noche">{{ meta.current_page }} / {{ meta.last_page }}</span>
      <button type="button" class="gw-accion-icono" :disabled="meta.current_page >= meta.last_page" aria-label="Página siguiente"
              :class="{ 'opacity-40': meta.current_page >= meta.last_page }" @click="$emit('ir', meta.current_page + 1)">
        <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
      </button>
    </div>
  </nav>
</template>

<script setup>
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

defineProps({ meta: { type: Object, default: null } })
defineEmits(['ir'])
</script>
