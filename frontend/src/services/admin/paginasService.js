import panelApi from './panelApi'

export const paginasService = {
  listar: () => panelApi.get('/admin/paginas').then(r => r.data),
  ver: (clave, idioma) => panelApi.get(`/admin/paginas/${clave}`, { params: { idioma } }).then(r => r.data),
  guardar: (clave, datos) => panelApi.put(`/admin/paginas/${clave}`, datos).then(r => r.data),
}
