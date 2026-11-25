// Decisión del visitante sobre las cookies de analítica. Compartida entre el aviso (AvisoCookies)
// y el enlace «Configurar cookies» del pie, que permite cambiarla o retirar el consentimiento.
import { ref, computed } from 'vue'
import { useConfiguracionWeb } from '@/composables/useConfiguracionWeb'

const CLAVE = 'dualab_cookies_analitica' // decisión del visitante (no es un dato sensible)

function leer() { try { return localStorage.getItem(CLAVE) } catch { return null } }
const decision = ref(leer())
const reabierto = ref(false)

// Borra las cookies de Google Analytics (_ga, _ga_<ID>, _gid…) en este host y en sus dominios padre
function borrarCookiesAnalitica() {
  const partes = location.hostname.split('.')
  const dominios = partes.map((_, i) => '.' + partes.slice(i).join('.')).slice(0, -1)
  for (const c of document.cookie.split(';')) {
    const nombre = c.split('=')[0].trim()
    if (!/^(_ga|_gid|_gat)/.test(nombre)) continue
    for (const d of [null, ...dominios]) document.cookie = `${nombre}=; Max-Age=0; path=/${d ? `; domain=${d}` : ''}`
  }
}

export function useConsentimientoCookies() {
  const { ajuste, cargada } = useConfiguracionWeb()

  const ga4 = computed(() => ajuste('analitica.ga4_id'))
  const gtm = computed(() => ajuste('analitica.gtm_id'))
  const hayAnalitica = computed(() => cargada.value && ajuste('analitica.activa') === true && !!(ga4.value || gtm.value))

  function decidir(valor) {
    const antes = decision.value
    decision.value = valor
    reabierto.value = false
    try { localStorage.setItem(CLAVE, valor) } catch { /* modo privado: se volverá a preguntar */ }
    // Retirada del consentimiento: los scripts de Google ya cargados no se pueden descargar, así
    // que se borran sus cookies y se recarga la página (ya sin analítica)
    if (antes === 'aceptadas' && valor === 'rechazadas') {
      borrarCookiesAnalitica()
      location.reload()
    }
  }

  const reabrir = () => { reabierto.value = true }

  return { decision, reabierto, ga4, gtm, hayAnalitica, decidir, reabrir }
}
