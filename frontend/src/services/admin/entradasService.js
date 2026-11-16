import panelApi from './panelApi'

export const entradasService = {
  listar: params => panelApi.get('/admin/entradas', { params }).then(r => r.data),
  ver: id => panelApi.get(`/admin/entradas/${id}`).then(r => r.data),
  crear: datos => panelApi.post('/admin/entradas', datos).then(r => r.data),
  actualizar: (id, datos) => panelApi.put(`/admin/entradas/${id}`, datos).then(r => r.data),
  aPapelera: id => panelApi.delete(`/admin/entradas/${id}`),
  restaurar: id => panelApi.post(`/admin/entradas/${id}/restaurar`).then(r => r.data),
  eliminarDefinitivamente: id => panelApi.delete(`/admin/entradas/${id}/definitivo`),
  aprobar: id => panelApi.post(`/admin/entradas/${id}/aprobar`).then(r => r.data),
  devolver: (id, nota) => panelApi.post(`/admin/entradas/${id}/devolver`, { nota }).then(r => r.data),
  retirar: id => panelApi.post(`/admin/entradas/${id}/retirar`).then(r => r.data),
  traducir: (id, idioma) => panelApi.post(`/admin/entradas/${id}/traducir`, { idioma }).then(r => r.data),
}
