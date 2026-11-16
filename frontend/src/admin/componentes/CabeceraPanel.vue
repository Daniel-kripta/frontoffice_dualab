<!-- Cabecera común de las vistas del panel (como CabeceraSeccion de la app): flecha
     volver encima del título si es de segundo nivel, antetítulo, título con palabra
     destacada, subtítulo y cinta de los cuatro colores. Acciones en el slot, a la derecha. -->
<template>
  <section class="relative mb-6">
    <div class="flex flex-wrap items-start justify-between gap-3 sm:flex-nowrap">
      <div class="min-w-0 flex-1 basis-full sm:basis-0">
        <RouterLink v-if="volver" :to="volver" class="mb-2 inline-flex items-center gap-1.5 text-sm font-semibold text-texto-secundario hover:text-azul-noche">
          <ArrowLeftIcon class="h-4 w-4" aria-hidden="true" /> {{ volverLabel }}
        </RouterLink>
        <p v-if="antetitulo" class="mb-1 text-xs font-bold uppercase tracking-[0.15em] text-texto-secundario">{{ antetitulo }}</p>
        <h1 class="break-words font-heading text-2xl font-bold tracking-tight text-azul-noche sm:text-4xl">
          {{ titulo }} <span v-if="destacado" class="text-administraciones-dark">{{ destacado }}</span>
        </h1>
        <p v-if="subtitulo" class="mt-2 max-w-2xl text-sm text-texto-secundario sm:text-base">{{ subtitulo }}</p>
        <span aria-hidden="true" class="mt-3 flex h-1 w-full max-w-md gap-1.5">
          <span class="flex-1 rounded-full bg-centros" /><span class="flex-1 rounded-full bg-empresas" /><span class="flex-1 rounded-full bg-alumnos" /><span class="flex-1 rounded-full bg-administraciones" />
        </span>
      </div>
      <div v-if="$slots.default" class="flex flex-wrap items-center gap-2">
        <slot />
      </div>
    </div>
  </section>
</template>

<script setup>
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

defineProps({
  titulo: { type: String, required: true },
  destacado: { type: String, default: '' },
  antetitulo: { type: String, default: '' },
  subtitulo: { type: String, default: '' },
  volver: { type: [String, Object], default: null },
  volverLabel: { type: String, default: 'Volver' },
})
</script>
