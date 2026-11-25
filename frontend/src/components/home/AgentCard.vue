<template>
  <article class="h-full flex flex-col bg-white rounded-2xl overflow-hidden shadow-[0_2px_12px_rgba(23,40,62,0.06)] hover:shadow-[0_12px_32px_rgba(23,40,62,0.1)] transition-shadow duration-300">
    <img :src="image" :alt="title" class="w-full h-40 object-cover" loading="lazy" />
    <div class="flex-1 flex flex-col p-5">
      <h3 class="font-heading font-bold text-lg text-azul-noche">{{ title }}</h3>
      <p class="text-xs text-gray-500 leading-relaxed mt-1 mb-3">{{ desc }}</p>
      <ul class="space-y-1.5 mb-5">
        <li v-for="b in bullets" :key="b" class="flex items-center gap-2 text-sm text-gray-700">
          <span class="w-[18px] h-[18px] rounded-full flex items-center justify-center shrink-0" :class="theme.bg">
            <svg width="9" height="9" viewBox="0 0 10 10" fill="none" aria-hidden="true">
              <path d="M1.5 5l2.5 2.5L8.5 2" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </span>
          {{ b }}
        </li>
      </ul>
      <button
        type="button"
        class="group mt-auto self-start inline-flex items-center gap-2 py-2 font-heading font-bold text-sm transition-colors"
        :class="theme.link"
        @click="$emit('more')"
      >
        {{ textoBoton }}
        <svg class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M13 6l6 6-6 6" />
        </svg>
      </button>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'

// Clases literales por colectivo para que Tailwind las detecte al escanear.
const THEMES = {
  centros:          { bg: 'bg-centros',          link: 'text-centros hover:text-primary-800' },
  empresas:         { bg: 'bg-empresas',         link: 'text-empresas hover:text-azul-noche' },
  alumnos:          { bg: 'bg-alumnos',          link: 'text-alumnos hover:text-azul-noche' },
  administraciones: { bg: 'bg-administraciones', link: 'text-administraciones hover:text-azul-noche' },
}

// textoBoton: lo pasa la vista (editable desde el panel de gestión web)
const props = defineProps({
  title: String, desc: String, image: String, bullets: Array,
  textoBoton: { type: String, default: 'Más información' },
  color: { type: String, default: 'centros', validator: (v) => ['centros', 'empresas', 'alumnos', 'administraciones'].includes(v) },
})
defineEmits(['more'])

const theme = computed(() => THEMES[props.color])
</script>
