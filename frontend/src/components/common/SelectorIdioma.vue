<!-- Selector de idioma de la web. Solo aparece si en el panel de gestión web hay más de
     un idioma activo; con uno solo (lo normal hoy) no pinta nada. -->
<template>
  <div v-if="idiomas.length > 1" class="relative">
    <label :for="id" class="sr-only">Idioma</label>
    <select :id="id" :value="idioma" class="h-10 appearance-none rounded-xl border-2 border-gray-200 bg-white pl-3 pr-8 text-xs font-black uppercase tracking-wider text-primary-700 focus:border-primary-600 focus:outline-none"
            @change="cambiarIdioma($event.target.value)">
      <option v-for="i in idiomas" :key="i.codigo" :value="i.codigo">{{ i.codigo.toUpperCase() }} · {{ i.nombre }}</option>
    </select>
    <ChevronDownIcon class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-primary-700" aria-hidden="true" />
  </div>
</template>

<script setup>
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import { useConfiguracionWeb } from '@/composables/useConfiguracionWeb'

defineProps({ id: { type: String, default: 'selector-idioma' } })
const { idiomas, idioma, cambiarIdioma } = useConfiguracionWeb()
</script>
