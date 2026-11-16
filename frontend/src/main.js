import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'
import { capturarOrigen } from './services/origen'
import { aparienciaGuardada, aplicarApariencia, enVistaPrevia, escucharVistaPrevia } from '@/composables/useApariencia'

// Última apariencia elegida en el panel, antes de pintar nada (ver useApariencia).
// En la vista previa del panel manda lo que envía el panel.
if (enVistaPrevia) escucharVistaPrevia()
else if (!window.location.pathname.startsWith('/gestion-web')) {
  aplicarApariencia(aparienciaGuardada(), { guardar: false })
}

if ('scrollRestoration' in history) {
  history.scrollRestoration = 'manual'
}

// Origen de la visita (utm_*, página de entrada, web de procedencia): antes de que el router cambie la dirección
capturarOrigen()

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
