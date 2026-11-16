import axios from 'axios'
import { ensureCsrfCookie } from '@/services/api'

// Cliente del panel de gestión web: misma API y cookie de sesión (Sanctum SPA) que
// la web, pero antes de cada escritura renueva la cookie XSRF (ver api.js) y, si la
// sesión caduca, vuelve a la pantalla de acceso del panel en vez de a la home.
const panelApi = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '/api',
  headers: { Accept: 'application/json' },
  withCredentials: true,
  withXSRFToken: true,
})

panelApi.interceptors.request.use(async (config) => {
  if (config.method && config.method.toLowerCase() !== 'get') await ensureCsrfCookie()
  return config
})

panelApi.interceptors.response.use(
  r => r,
  (error) => {
    const esComprobacion = error.config?.url === '/auth/me'
    if (error.response?.status === 401 && !esComprobacion) {
      const vuelta = encodeURIComponent(window.location.pathname + window.location.search)
      window.location.href = `/gestion-web/acceso?redirect=${vuelta}`
    }
    return Promise.reject(error)
  },
)

// Mensaje entendible para el usuario (nunca el texto técnico del servidor)
export function mensajeError(error, porDefecto = 'No se ha podido completar la acción. Inténtalo de nuevo.') {
  const r = error?.response
  if (!r) return 'Sin conexión con el servidor. Revisa tu conexión e inténtalo de nuevo.'
  if (r.status === 422) {
    const primero = Object.values(r.data?.errors ?? {})[0]?.[0]
    return primero || r.data?.message || 'Revisa los datos marcados.'
  }
  if (r.status === 403) return 'No tienes permiso para esta acción.'
  if (r.status === 404) return 'No se ha encontrado lo que buscas (puede que se haya eliminado).'
  if (r.status === 413) return 'El archivo es demasiado grande para el servidor.'
  if (r.status === 419) return 'La sesión ha caducado. Recarga la página.'
  if (r.status === 429) return 'Demasiadas peticiones seguidas. Espera un momento.'
  return porDefecto
}

// Errores de validación por campo: { 'valores.hero.titulo': 'mensaje' }
export function erroresCampos(error) {
  if (error?.response?.status !== 422) return {}
  return Object.fromEntries(Object.entries(error.response.data?.errors ?? {}).map(([k, v]) => [k, v[0]]))
}

export default panelApi
