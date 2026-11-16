<template>
  <!-- El proyecto completado de un reto no se muestra en abierto: se ofrece
       una demo guiada a quien quiera verlo (sustituye a ProyectoDetalleView). -->
  <section class="relative bg-azul-noche overflow-hidden py-16 px-6">
    <div class="relative max-w-3xl mx-auto text-center">
      <RouterLink :to="{ name: 'reto-detalle', params: { id: route.params.id } }"
                  class="inline-flex items-center gap-1.5 text-white/70 hover:text-white text-sm mb-6 transition-colors">
        <ChevronLeftIcon class="w-4 h-4" />
        Volver al reto
      </RouterLink>
      <div class="mx-auto w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center mb-5">
        <RocketLaunchIcon class="w-7 h-7 text-alumnos" />
      </div>
      <p class="text-xs font-bold uppercase tracking-[0.25em] text-primary-300 mb-3">Proyecto completado</p>
      <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-white leading-tight">
        Solicita una demo para ver el proyecto
      </h1>
      <p v-if="reto" class="text-white/80 font-semibold mt-4">
        «{{ reto.titulo }}»<template v-if="reto.empresa_nombre"> · {{ reto.empresa_nombre }}</template>
      </p>
      <p class="text-white/70 mt-4 max-w-xl mx-auto leading-relaxed">
        Un equipo de alumnado real ya resolvió este reto. Te enseñamos el proyecto completo
        —fases, entregables y resultado final— en una demo guiada de DuaLab.
      </p>
    </div>
  </section>

  <section class="bg-primary-50 py-14 px-6">
    <div class="max-w-5xl mx-auto grid md:grid-cols-[2fr_3fr] gap-10 items-start">
      <div>
        <h2 class="font-heading font-bold text-azul-noche text-lg mb-4">En la demo verás</h2>
        <ul class="space-y-3">
          <li v-for="p in puntos" :key="p.title" class="flex items-start gap-3">
            <span class="w-5 h-5 rounded-full bg-administraciones flex items-center justify-center shrink-0 mt-0.5">
              <svg width="10" height="10" viewBox="0 0 10 10" fill="none" aria-hidden="true"><path d="M1.5 5l2.5 2.5L8.5 2" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <div>
              <div class="font-semibold text-sm text-azul-noche">{{ p.title }}</div>
              <div class="text-xs text-gray-500 leading-relaxed">{{ p.desc }}</div>
            </div>
          </li>
        </ul>
      </div>
      <div class="bg-white rounded-2xl p-6 md:p-8 shadow-[0_2px_12px_rgba(23,40,62,0.06)]">
        <h2 class="font-heading font-bold text-azul-noche text-lg mb-1">Solicitar demo</h2>
        <p class="text-sm text-gray-500 mb-6">Déjanos tus datos y te contactamos para enseñarte el proyecto.</p>
        <ContactForm />
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ChevronLeftIcon, RocketLaunchIcon } from '@heroicons/vue/24/outline'
import ContactForm from '@/components/home/ContactForm.vue'
import microretosPublicApi from '@/services/microretosPublicApi'
import { usePagina } from '@/composables/usePagina'

const route = useRoute()

// Solo para dar contexto (título y empresa del reto); si falla, la vista
// funciona igual sin él.
const reto = ref(null)
onMounted(async () => {
  try {
    const { data } = await microretosPublicApi.get(`/public/microretos/${route.params.id}`)
    reto.value = data.data ?? data
  } catch {
    reto.value = null
  }
})

const puntos = [
  { title: 'El proyecto completo', desc: 'Cómo el equipo de alumnado resolvió el reto, fase a fase.' },
  { title: 'Entregables y resultado final', desc: 'El trabajo entregado a la empresa y su valoración.' },
  { title: 'La plataforma por dentro', desc: 'Cómo DuaLab gestiona retos, equipos y seguimiento.' },
]

// SEO editable desde el panel de gestión web (config/cms_paginas.php → solicitar-demo)
usePagina('solicitar-demo', { titulo: 'Solicitar demo' })
</script>
