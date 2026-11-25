<!-- Consentimiento de cookies analíticas. Solo aparece si en el panel de gestión web
     (Analítica) hay un ID de Google Analytics / Tag Manager y la analítica está activa;
     los scripts de Google no se cargan hasta que el visitante acepta. Se vuelve a abrir desde
     «Configurar cookies» en el pie (useConsentimientoCookies) para cambiar o retirar la decisión. -->
<template>
  <div v-if="visible" role="dialog" aria-modal="false" aria-labelledby="cookies-titulo"
       class="fixed inset-x-3 bottom-3 z-50 mx-auto max-w-xl max-h-[70vh] overflow-y-auto rounded-2xl bg-azul-noche text-white shadow-2xl p-5 sm:inset-x-auto sm:right-5 sm:bottom-5">
    <p id="cookies-titulo" class="font-heading font-bold">Cookies de analítica</p>
    <p class="mt-2 text-sm text-white/85 leading-relaxed">
      Usamos cookies de Google Analytics para saber cómo se usa la web y mejorarla. Solo se activan si las aceptas.
      Puedes cambiar tu decisión cuando quieras desde «Configurar cookies», al pie de la página.
    </p>
    <p v-if="reabierto && decision" class="mt-2 text-xs text-white/70">
      Ahora mismo están {{ decision === 'aceptadas' ? 'aceptadas' : 'rechazadas' }}.
    </p>
    <div class="mt-4 flex flex-wrap gap-2">
      <button type="button" class="inline-flex h-10 items-center rounded-full bg-white px-5 text-sm font-bold text-azul-noche" @click="decidir('aceptadas')">Aceptar</button>
      <button type="button" class="inline-flex h-10 items-center rounded-full border border-white/50 px-5 text-sm font-bold text-white hover:bg-white/10" @click="decidir('rechazadas')">Rechazar</button>
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import { enVistaPrevia } from '@/composables/useApariencia'
import { useConsentimientoCookies } from '@/composables/useConsentimientoCookies'

const { decision, reabierto, ga4, gtm, hayAnalitica, decidir } = useConsentimientoCookies()

// En la vista previa del panel no se pregunta (ni se carga analítica)
const visible = computed(() => hayAnalitica.value && (!decision.value || reabierto.value) && !enVistaPrevia)

let cargado = false
function cargarScripts() {
  if (cargado) return
  cargado = true
  // Los ID se validan en el backend (G-XXXX / GTM-XXXX): solo letras, números y guion
  if (gtm.value) {
    window.dataLayer = window.dataLayer || []
    window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' })
    const s = document.createElement('script')
    s.async = true
    s.src = `https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(gtm.value)}`
    document.head.appendChild(s)
  } else if (ga4.value) {
    const s = document.createElement('script')
    s.async = true
    s.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(ga4.value)}`
    document.head.appendChild(s)
    window.dataLayer = window.dataLayer || []
    window.gtag = function gtag() { window.dataLayer.push(arguments) }
    window.gtag('js', new Date())
    window.gtag('config', ga4.value, { anonymize_ip: true })
  }
}

watch([hayAnalitica, decision], () => {
  if (hayAnalitica.value && decision.value === 'aceptadas' && !enVistaPrevia) cargarScripts()
}, { immediate: true })
</script>
