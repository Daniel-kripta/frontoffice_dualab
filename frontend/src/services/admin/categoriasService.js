import panelApi from './panelApi'

export const categoriasService = {
  listar: () => panelApi.get('/admin/categorias').then(r => r.data),
  crear: datos => panelApi.post('/admin/categorias', datos).then(r => r.data),
  actualizar: (id, datos) => panelApi.put(`/admin/categorias/${id}`, datos).then(r => r.data),
  eliminar: id => panelApi.delete(`/admin/categorias/${id}`),
}
