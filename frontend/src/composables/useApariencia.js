import { ref } from 'vue'
import { APARIENCIA_ORIGINAL, TAMANOS, cargarFuentes, variablesApariencia } from '@/data/apariencia'

// Aplica en la web pública la apariencia elegida en el panel (Ajustes → Apariencia).
// La última se guarda en el navegador (preferencia de UI, no sensible) y se aplica
// antes de montar la app: así no se ven un instante los colores originales.
const CLAVE = 'dualab_apariencia'
let faviconOriginal = null

// Vista previa del panel (Ajustes → Apariencia): la web se abre en un iframe con
// ?vista_previa y recibe por postMessage la apariencia sin guardar. Solo se aceptan
// mensajes del mismo origen; nada se guarda en el navegador.
export const enVistaPrevia = window.self !== window.top && new URLSearchParams(window.location.search).has('vista_previa')
export const aparienciaPrevia = ref(null)

export function escucharVistaPrevia() {
  if (!enVistaPrevia) return
  window.addEventListener('message', (e) => {
    if (e.origin !== window.location.origin || e.data?.tipo !== 'dualab-apariencia') return
    aparienciaPrevia.value = e.data.apariencia
    aplicarApariencia(e.data.apariencia, { guardar: false })
  })
  window.parent.postMessage({ tipo: 'dualab-vista-lista' }, window.location.origin)
}

export function aparienciaDeAjustes(ajustes = {}) {
  return Object.fromEntries(Object.keys(APARIENCIA_ORIGINAL).map(k => [k, ajustes[`apariencia.${k}`] ?? APARIENCIA_ORIGINAL[k]]))
}

export function aparienciaGuardada() {
  try { return JSON.parse(localStorage.getItem(CLAVE)) } catch { return null }
}

export function aplicarApariencia(a, { guardar = true } = {}) {
  if (!a) return
  const raiz = document.documentElement
  for (const [variable, valor] of Object.entries(variablesApariencia(a))) raiz.style.setProperty(variable, valor)
  raiz.style.fontSize = (TAMANOS[a.tamano_texto] ?? TAMANOS.normal).raiz
  cargarFuentes(a)
  ponerFavicon(a.favicon)
  if (guardar && !enVistaPrevia) {
    try { localStorage.setItem(CLAVE, JSON.stringify(a)) } catch { /* modo privado */ }
  }
}

// El panel tiene su propia estética (la de la app DuaLab): se le quita la de la web
export function quitarApariencia() {
  const raiz = document.documentElement
  for (const variable of Object.keys(variablesApariencia())) raiz.style.removeProperty(variable)
  raiz.style.fontSize = ''
  ponerFavicon(null)
}

function ponerFavicon(url) {
  const icono = document.querySelector('link[rel="icon"]')
  if (!icono) return
  faviconOriginal ??= icono.getAttribute('href')
  icono.setAttribute('href', url || faviconOriginal)
}
