import api from './api'

// Contenido gestionado desde el panel de gestión web (backend: /api/web/*)
export const webService = {
  configuracion: () => api.get('/web/configuracion').then(r => r.data),
  pagina: (clave, idioma) => api.get(`/web/paginas/${clave}`, { params: { idioma } }).then(r => r.data),
  entradas: (params) => api.get('/web/entradas', { params }).then(r => r.data),
  entrada: (slug, idioma) => api.get(`/web/entradas/${slug}`, { params: { idioma } }).then(r => r.data),
  categorias: () => api.get('/web/categorias').then(r => r.data),
}
