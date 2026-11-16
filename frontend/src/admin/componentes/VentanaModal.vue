<!-- Modal accesible del panel (GUIA_DISENO §6.4): role="dialog", título enlazado,
     foco al abrir, foco atrapado, Escape para cerrar y foco devuelto al cerrar. -->
<template>
  <Teleport to="body">
    <Transition name="gw-modal">
      <div v-if="abierto" class="fixed inset-0 z-[55] flex items-end justify-center bg-azul-noche/50 p-0 sm:items-center sm:p-4" @mousedown.self="cerrar">
        <div ref="caja" role="dialog" aria-modal="true" :aria-labelledby="idTitulo" tabindex="-1"
             class="gw-panel flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-[2rem] bg-white shadow-2xl outline-none sm:rounded-[2rem]"
             :class="anchos[ancho]" @keydown="alTeclear">
          <div class="flex items-start gap-3 border-b border-gray-100 px-5 py-4 sm:px-6">
            <div class="min-w-0 flex-1">
              <h2 :id="idTitulo" class="break-words font-heading text-lg font-bold text-azul-noche">{{ titulo }}</h2>
              <p v-if="subtitulo" class="mt-0.5 text-sm text-texto-secundario">{{ subtitulo }}</p>
            </div>
            <button type="button" class="gw-accion-icono !h-9 !w-9" aria-label="Cerrar" @click="cerrar">
              <XMarkIcon class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
          <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6"><slot /></div>
          <div v-if="$slots.pie" class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 px-5 py-4 sm:px-6">
            <slot name="pie" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  abierto: { type: Boolean, default: false },
  titulo: { type: String, required: true },
  subtitulo: { type: String, default: '' },
  ancho: { type: String, default: 'md' },
})
const emit = defineEmits(['cerrar'])

const anchos = { sm: 'sm:max-w-md', md: 'sm:max-w-2xl', lg: 'sm:max-w-5xl', completo: 'sm:max-w-[min(96vw,110rem)]' }
const idTitulo = `gw-modal-${Math.random().toString(36).slice(2, 9)}`
const caja = ref(null)
let anterior = null

const enfocables = () => [...(caja.value?.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])') ?? [])]

watch(() => props.abierto, async (abierto) => {
  if (abierto) {
    anterior = document.activeElement
    document.body.style.overflow = 'hidden'
    await nextTick()
    ;(enfocables()[1] ?? caja.value)?.focus() // [0] es el botón cerrar
  } else {
    document.body.style.overflow = ''
    anterior?.focus?.()
  }
})

function cerrar() { emit('cerrar') }

function alTeclear(e) {
  if (e.key === 'Escape') { e.stopPropagation(); cerrar(); return }
  if (e.key !== 'Tab') return
  const lista = enfocables()
  if (!lista.length) return
  const [primero, ultimo] = [lista[0], lista[lista.length - 1]]
  if (e.shiftKey && document.activeElement === primero) { e.preventDefault(); ultimo.focus() }
  else if (!e.shiftKey && document.activeElement === ultimo) { e.preventDefault(); primero.focus() }
}
</script>

<style scoped>
.gw-modal-enter-active, .gw-modal-leave-active { transition: opacity 0.2s ease; }
.gw-modal-enter-from, .gw-modal-leave-to { opacity: 0; }
</style>
