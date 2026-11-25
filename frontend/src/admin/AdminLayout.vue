<template>
  <div class="gw-panel min-h-dvh bg-fondo-panel text-gray-600">
    <a href="#gw-contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[70] focus:rounded-full focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-azul-noche">
      Saltar al contenido
    </a>
    <BarraSuperior :menu-abierto="menuAbierto" @alternar-menu="menuAbierto = !menuAbierto" />
    <PanelLateral :abierto="menuAbierto" @cerrar="menuAbierto = false" />

    <main id="gw-contenido" class="pt-16 lg:pl-72">
      <div class="mx-auto max-w-[90rem] px-4 pb-10 pt-5 sm:px-6 lg:px-8">
        <RouterView :key="route.path" />
      </div>
    </main>

    <AvisosPanel />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import BarraSuperior from './componentes/BarraSuperior.vue'
import PanelLateral from './componentes/PanelLateral.vue'
import AvisosPanel from './componentes/AvisosPanel.vue'

const route = useRoute()
const menuAbierto = ref(false)

// En móvil/tablet el menú es un cajón: se cierra al navegar
watch(() => route.fullPath, () => { menuAbierto.value = false })
</script>
