import panelApi from './panelApi'

export const correoService = {
  listar: params => panelApi.get('/admin/correo', { params }).then(r => r.data),
  ver: id => panelApi.get(`/admin/correo/${id}`).then(r => r.data),
  cambiarEstado: (id, estado) => panelApi.patch(`/admin/correo/${id}`, { estado }).then(r => r.data),
  // multipart: puede llevar adjuntos. Queda en la bandeja de salida y sale por la cola
  enviar: datos => panelApi.post('/admin/correo/enviar', datos).then(r => r.data),
  reintentar: id => panelApi.post(`/admin/correo/envios/${id}/reintentar`).then(r => r.data),
  descartar: id => panelApi.delete(`/admin/correo/envios/${id}`),
  // Como blob con la cookie de sesión; el backend lo sirve siempre como descarga
  async descargarAdjunto(correoId, adjunto) {
    const r = await panelApi.get(`/admin/correo/${correoId}/adjuntos/${adjunto.id}`, { responseType: 'blob' })
    const url = URL.createObjectURL(r.data)
    const a = Object.assign(document.createElement('a'), { href: url, download: adjunto.nombre })
    a.click()
    URL.revokeObjectURL(url)
  },
}
