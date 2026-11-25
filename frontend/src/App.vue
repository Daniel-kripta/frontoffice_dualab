<template>
  <!-- Panel de gestión web: su propio layout, sin la cabecera ni el pie de la web -->
  <RouterView v-if="enPanel" />
  <div v-else class="min-h-screen flex flex-col">
    <AppNavbar />
    <main class="flex-1">
      <RouterView />
    </main>
    <AppFooter />
    <AvisoCookies />
  </div>
  <!-- Fuera del v-if: también en el panel de gestión web -->
  <AvisoNuevaVersion :en-panel="enPanel" />
</template>

<script setup>
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppNavbar from '@/components/common/AppNavbar.vue'
import AppFooter from '@/components/common/AppFooter.vue'
import AvisoCookies from '@/components/common/AvisoCookies.vue'
import AvisoNuevaVersion from '@/components/common/AvisoNuevaVersion.vue'
import { useConfiguracionWeb } from '@/composables/useConfiguracionWeb'
import { aplicarApariencia, enVistaPrevia, quitarApariencia } from '@/composables/useApariencia'

const route = useRoute()
// Antes de resolver la primera ruta `route` aún es '/': se mira la URL para no pintar
// un instante la cabecera de la web al abrir el panel.
const enPanel = computed(() => (route.matched.length
  ? !!route.meta.panel
  : window.location.pathname.startsWith('/gestion-web')))

// La apariencia elegida en el panel es solo para la web: al entrar al panel se quita y
// al volver a la web se repone
const { apariencia, cargada } = useConfiguracionWeb()
watch([enPanel, cargada], ([panel]) => {
  if (panel) quitarApariencia()
  else if (cargada.value && !enVistaPrevia) aplicarApariencia(apariencia.value)
})
</script>
