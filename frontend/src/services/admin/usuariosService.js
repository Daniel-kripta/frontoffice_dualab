import panelApi from './panelApi'

// Usuarios del panel (solo admin)
export const usuariosService = {
  listar: params => panelApi.get('/admin/usuarios', { params }).then(r => r.data),
  crear: datos => panelApi.post('/admin/usuarios', datos).then(r => r.data),
  actualizar: (id, datos) => panelApi.patch(`/admin/usuarios/${id}`, datos).then(r => r.data),
  bloquear: id => panelApi.post(`/admin/usuarios/${id}/bloquear`).then(r => r.data),
  desbloquear: id => panelApi.post(`/admin/usuarios/${id}/desbloquear`).then(r => r.data),
  activar: id => panelApi.post(`/admin/usuarios/${id}/activar`).then(r => r.data),
  reenviarCorreo: id => panelApi.post(`/admin/usuarios/${id}/reenviar-correo`).then(r => r.data),
  eliminar: id => panelApi.delete(`/admin/usuarios/${id}`).then(r => r.data),
  restaurar: id => panelApi.post(`/admin/usuarios/${id}/restaurar`).then(r => r.data),
  destruir: id => panelApi.delete(`/admin/usuarios/${id}/definitivo`).then(r => r.data),
}
