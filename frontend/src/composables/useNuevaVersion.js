import { ref } from 'vue'

// Tras un deploy, una pestaña abierta sigue ejecutando el código anterior (la SPA no vuelve a
// pedir index.html al navegar). Se compara la versión incrustada en el bundle (__APP_VERSION__,
// vite.config.js) con version.json del servidor y, si cambia, AvisoNuevaVersion.vue ofrece
// recargar. Igual que en la app DuaLab (microretos, composables/useNuevaVersion.js).
const VERSION_ACTUAL = __APP_VERSION__
const INTERVALO_MS = 10 * 60_000        // comprobación periódica con la pestaña abierta
const MIN_ENTRE_COMPROBACIONES_MS = 60_000 // navegar mucho no dispara una petición por ruta
const POSPONER_MS = 30 * 60_000          // "Más tarde": vuelve a salir pasado este rato

const hayNuevaVersion = ref(false)
let ultimaComprobacion = 0
let pospuestoHasta = 0
let iniciado = false

async function comprobarVersion() {
  if (import.meta.env.DEV || hayNuevaVersion.value) return
  if (Date.now() < pospuestoHasta) return
  if (Date.now() - ultimaComprobacion < MIN_ENTRE_COMPROBACIONES_MS) return
  ultimaComprobacion = Date.now()
  try {
    // BASE_URL: en producción el build vive en /home/ (deploy.sh)
    const res = await fetch(`${import.meta.env.BASE_URL}version.json?t=${Date.now()}`, { cache: 'no-store' })
    if (!res.ok) return
    const { version } = await res.json()
    if (version && version !== VERSION_ACTUAL) hayNuevaVersion.value = true
  } catch { /* sin conexión: se reintenta en la siguiente comprobación */ }
}

function posponer() {
  hayNuevaVersion.value = false
  pospuestoHasta = Date.now() + POSPONER_MS
}

// Pide la página saltándose la caché HTTP para que la recarga reciba el index.html nuevo
// (los JS/CSS llevan hash en el nombre, así que llegan los nuevos) y recarga
async function recargar() {
  try {
    await fetch(window.location.pathname, { cache: 'reload' })
  } catch { /* la recarga sigue aunque falle */ }
  window.location.reload()
}

// Una vez, desde App.vue: al cambiar de ruta, al volver a la pestaña y cada INTERVALO_MS
function iniciarComprobacion(router) {
  if (iniciado) return
  iniciado = true
  comprobarVersion()
  router.afterEach(() => comprobarVersion())
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') comprobarVersion()
  })
  setInterval(comprobarVersion, INTERVALO_MS)
}

export function useNuevaVersion() {
  return { hayNuevaVersion, iniciarComprobacion, recargar, posponer }
}
