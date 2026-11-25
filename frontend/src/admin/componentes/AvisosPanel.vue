<template>
  <!-- Con el aviso «Hay una versión nueva» (abajo a la derecha en escritorio), los avisos suben encima -->
  <div class="pointer-events-none fixed bottom-20 right-4 z-[60] flex max-h-[70vh] w-[min(24rem,calc(100vw-2rem))] flex-col gap-2 overflow-y-auto"
       :class="hayNuevaVersion ? 'sm:bottom-56' : 'sm:bottom-4'"
       aria-live="polite" role="status">
    <TransitionGroup name="gw-aviso">
      <div v-for="a in avisos" :key="a.id"
           class="pointer-events-auto flex items-start gap-3 rounded-2xl border bg-white p-4 shadow-xl"
           :class="a.tipo === 'error' ? 'border-red-200' : 'border-emerald-200'">
        <component :is="a.tipo === 'error' ? ExclamationCircleIcon : CheckCircleIcon"
                   class="mt-0.5 h-5 w-5 shrink-0" :class="a.tipo === 'error' ? 'text-red-600' : 'text-emerald-600'" aria-hidden="true" />
        <p class="min-w-0 flex-1 break-words text-sm font-medium text-azul-noche">{{ a.texto }}</p>
        <button type="button" class="-m-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                aria-label="Cerrar aviso" @click="cerrar(a.id)">
          <XMarkIcon class="h-4 w-4" aria-hidden="true" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { CheckCircleIcon, ExclamationCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useAvisos } from '../composables/useAvisos'
import { useNuevaVersion } from '@/composables/useNuevaVersion'

const { avisos, cerrar } = useAvisos()
const { hayNuevaVersion } = useNuevaVersion()
</script>

<style scoped>
.gw-aviso-enter-active, .gw-aviso-leave-active { transition: all 0.25s ease; }
.gw-aviso-enter-from, .gw-aviso-leave-to { opacity: 0; transform: translateY(8px); }
</style>
