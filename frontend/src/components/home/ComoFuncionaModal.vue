<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="como-funciona-title"
      >
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="$emit('close')" />

        <div class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[88vh] overflow-y-auto p-8 md:p-10">
          <button
            type="button"
            class="absolute top-4 right-4 w-9 h-9 flex items-center justify-center rounded-full text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
            aria-label="Cerrar"
            @click="$emit('close')"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <div class="text-center mb-10">
            <p class="section-eyebrow">{{ t.antetitulo }}</p>
            <h2 id="como-funciona-title" class="text-3xl font-extrabold tracking-tight text-azul-noche">{{ t.titulo }}</h2>
            <p class="text-gray-500 mt-2 text-sm font-medium">{{ t.descripcion }}</p>
          </div>

          <div class="grid md:grid-cols-3 gap-4">
            <StepCard v-for="(paso, i) in t.pasos" :key="i" :number="String(i + 1)" :icon="ICONOS_PASOS[i]"
              :title="paso.titulo" :desc="paso.texto" />
          </div>

          <div class="text-center mt-10">
            <button type="button" class="btn-pill bg-primary-600 hover:bg-primary-700 px-8" @click="$emit('demo')">
              {{ t.boton }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, watch, onUnmounted } from 'vue'
import StepCard from '@/components/home/StepCard.vue'
import { BuildingOffice2Icon, SparklesIcon, AcademicCapIcon } from '@heroicons/vue/24/outline'

// textos: { antetitulo, titulo, descripcion, pasos: [{ titulo, texto }] ×3, boton }; los pasa
// la vista (editables desde el panel de gestión web). Lo que falte se queda con el original.
const ORIGINALES = {
  antetitulo: 'Flujo de trabajo',
  titulo: '¿Cómo funciona DuaLab?',
  descripcion: 'Tres pasos para transformar necesidades reales en aprendizaje práctico',
  pasos: [
    { titulo: 'La Empresa propone', texto: 'Identifican una fricción o cuello de botella real en su día a día.' },
    { titulo: 'La IA lo transforma', texto: 'DuaLab genera un reto académico alineado al currículo oficial.' },
    { titulo: 'El Alumnado resuelve', texto: 'Ganan experiencia práctica mientras aportan valor real a la empresa.' },
  ],
  boton: 'Solicitar demo',
}
const ICONOS_PASOS = [BuildingOffice2Icon, SparklesIcon, AcademicCapIcon]

const props = defineProps({ open: Boolean, textos: { type: Object, default: () => ({}) } })
const t = computed(() => ({ ...ORIGINALES, ...props.textos }))
const emit = defineEmits(['close', 'demo'])

function handleKeydown(e) {
  if (e.key === 'Escape') emit('close')
}

watch(() => props.open, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
  if (open) window.addEventListener('keydown', handleKeydown)
  else window.removeEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.body.style.overflow = ''
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.25s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
