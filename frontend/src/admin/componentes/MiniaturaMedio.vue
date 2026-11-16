<template>
  <span class="relative block aspect-square overflow-hidden rounded-xl bg-gray-100">
    <img v-if="medio.tipo === 'imagen'" :src="medio.url" :alt="medio.alt || ''" class="h-full w-full object-cover" loading="lazy" />
    <span v-else class="flex h-full w-full flex-col items-center justify-center gap-1 p-2 text-center">
      <component :is="medio.tipo === 'video' ? FilmIcon : DocumentTextIcon" class="h-8 w-8 text-texto-secundario" aria-hidden="true" />
      <span class="line-clamp-2 break-all text-xs font-medium text-azul-noche">{{ medio.nombre_original }}</span>
    </span>
  </span>
</template>

<script setup>
import { FilmIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'

defineProps({ medio: { type: Object, required: true } })
</script>
