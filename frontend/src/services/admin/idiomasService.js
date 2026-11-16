import panelApi from './panelApi'

export const idiomasService = {
  listar: () => panelApi.get('/admin/idiomas').then(r => r.data),
  anadir: codigo => panelApi.post('/admin/idiomas', { codigo }).then(r => r.data),
  actualizar: (id, datos) => panelApi.patch(`/admin/idiomas/${id}`, datos).then(r => r.data),
  quitar: id => panelApi.delete(`/admin/idiomas/${id}`),
}
