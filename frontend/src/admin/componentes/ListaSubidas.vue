<template>
  <ul v-if="subidas.length" class="space-y-2" aria-live="polite">
    <li v-for="s in subidas" :key="s.id" class="flex items-center gap-3 rounded-xl border px-3 py-2"
        :class="s.error ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-white'">
      <span class="min-w-0 flex-1">
        <span class="block truncate text-sm font-medium text-azul-noche">{{ s.nombre }}</span>
        <span v-if="s.error" class="block text-xs text-red-700">{{ s.error }}</span>
        <span v-else class="mt-1 block h-1.5 overflow-hidden rounded-full bg-gray-100" role="progressbar" :aria-valuenow="s.progreso" aria-valuemin="0" aria-valuemax="100">
          <span class="block h-full rounded-full bg-administraciones-dark transition-all" :style="{ width: `${s.progreso}%` }" />
        </span>
      </span>
      <CheckCircleIcon v-if="s.hecho" class="h-5 w-5 text-emerald-600" aria-label="Subido" />
      <button v-if="s.error" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full text-red-700 hover:bg-red-100"
              aria-label="Descartar" @click="$emit('descartar', s)">
        <XMarkIcon class="h-4 w-4" aria-hidden="true" />
      </button>
    </li>
  </ul>
</template>

<script setup>
import { CheckCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline'

defineProps({ subidas: { type: Array, required: true } })
defineEmits(['descartar'])
</script>
