import { ref, computed } from 'vue'
import { webService } from '@/services/webService'
import { aparienciaDeAjustes, aparienciaPrevia, aplicarApariencia, enVistaPrevia } from './useApariencia'

// Ajustes públicos del panel (SEO global, analítica) e idiomas activos. Se piden
// una vez por visita; si el backend no responde, la web sigue con sus textos.
const config = ref({ idiomas: [], ajustes: {} })
const cargada = ref(false)
let peticion = null

const CLAVE_IDIOMA = 'dualab_idioma' // preferencia de UI, no sensible
function idiomaGuardado() {
  try { return localStorage.getItem(CLAVE_IDIOMA) } catch { return null }
}
const idioma = ref(new URLSearchParams(window.location.search).get('idioma') || idiomaGuardado() || 'es')

export function cargarConfiguracionWeb() {
  peticion ??= webService.configuracion()
    .then((data) => {
      config.value = data
      const activos = data.idiomas.map(i => i.codigo)
      if (!activos.includes(idioma.value)) {
        idioma.value = data.idiomas.find(i => i.por_defecto)?.codigo ?? 'es'
      }
      document.documentElement.lang = idioma.value
      ponerVerificacionSearchConsole(data.ajustes['seo.search_console'])
      if (!window.location.pathname.startsWith('/gestion-web')) ponerOrganizacion(data.organizacion)
      if (!enVistaPrevia && !window.location.pathname.startsWith('/gestion-web')) aplicarApariencia(aparienciaDeAjustes(data.ajustes))
    })
    .catch(() => {})
    .finally(() => { cargada.value = true })
  return peticion
}

function ponerVerificacionSearchConsole(codigo) {
  if (!codigo || document.querySelector('meta[name="google-site-verification"]')) return
  const meta = document.createElement('meta')
  meta.name = 'google-site-verification'
  meta.content = codigo
  document.head.appendChild(meta)
}

// Datos de la organización (SEO → Datos de la organización) en JSON-LD para Google y las IA.
// textContent: el navegador no lo interpreta como HTML aunque un texto lleve «</script>».
function ponerOrganizacion(organizacion) {
  if (!organizacion || document.getElementById('ld-organizacion')) return
  const script = document.createElement('script')
  script.type = 'application/ld+json'
  script.id = 'ld-organizacion'
  script.textContent = JSON.stringify(organizacion)
  document.head.appendChild(script)
}

export function useConfiguracionWeb() {
  cargarConfiguracionWeb()
  return {
    config,
    cargada,
    idioma,
    idiomas: computed(() => config.value.idiomas),
    apariencia: computed(() => aparienciaPrevia.value ?? aparienciaDeAjustes(config.value.ajustes)),
    ajuste: (clave, defecto = null) => config.value.ajustes?.[clave] ?? defecto,
    cambiarIdioma(codigo) {
      idioma.value = codigo
      document.documentElement.lang = codigo
      try { localStorage.setItem(CLAVE_IDIOMA, codigo) } catch { /* modo privado */ }
    },
  }
}
