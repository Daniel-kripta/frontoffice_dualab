<!-- "Hay una versión nueva": tras un deploy, una pestaña abierta (la web o el panel de gestión
     web) sigue con el código anterior hasta recargar. En el panel, abajo a la derecha como en las
     demás herramientas (los avisos del panel suben encima, ver AvisosPanel); en la web, abajo a la
     izquierda para no tapar el aviso de cookies. En móvil, arriba, bajo la cabecera. -->
<template>
  <Transition name="aviso-version">
    <div v-if="hayNuevaVersion && !enVistaPrevia" role="status"
         class="fixed inset-x-3 top-20 z-[70] mx-auto max-w-sm rounded-2xl bg-azul-noche p-4 text-white shadow-2xl sm:inset-x-auto sm:top-auto sm:bottom-5 sm:mx-0 sm:w-[min(24rem,calc(100vw-2rem))]"
         :class="enPanel ? 'sm:right-4' : 'sm:left-5'">
      <div class="flex items-start gap-3">
        <ArrowPathIcon class="mt-0.5 h-5 w-5 shrink-0 text-white/80" aria-hidden="true" />
        <div class="min-w-0">
          <p class="font-heading font-bold leading-tight">Hay una versión nueva</p>
          <p class="mt-1 text-sm leading-relaxed text-white/85">
            Recarga para ver las novedades{{ enPanel ? '. Guarda antes lo que tengas a medias' : '' }}.
          </p>
        </div>
      </div>
      <div class="mt-3 flex flex-wrap justify-end gap-2">
        <button type="button" class="inline-flex h-10 items-center rounded-full border border-white/50 px-5 text-sm font-bold text-white hover:bg-white/10"
                @click="posponer">Más tarde</button>
        <button type="button" :disabled="recargando"
                class="inline-flex h-10 items-center rounded-full bg-white px-5 text-sm font-bold text-azul-noche disabled:opacity-60"
                @click="alRecargar">{{ recargando ? 'Recargando…' : 'Recargar' }}</button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'
import { useNuevaVersion } from '@/composables/useNuevaVersion'
// Dentro del iframe de la vista previa de Apariencia ya lo muestra el propio panel
import { enVistaPrevia } from '@/composables/useApariencia'

defineProps({ enPanel: { type: Boolean, default: false } })

const router = useRouter()
const { hayNuevaVersion, iniciarComprobacion, recargar, posponer } = useNuevaVersion()
const recargando = ref(false)

// Con cambios sin guardar en el panel, el navegador pregunta antes de recargar
// (useCambiosSinGuardar): si se cancela, el botón vuelve a estar disponible
function alRecargar() {
  recargando.value = true
  recargar()
  setTimeout(() => { recargando.value = false }, 3000)
}

onMounted(() => iniciarComprobacion(router))
</script>

<style scoped>
.aviso-version-enter-active, .aviso-version-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.aviso-version-enter-from, .aviso-version-leave-to { opacity: 0; transform: translateY(8px); }
</style>
