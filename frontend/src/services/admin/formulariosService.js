import panelApi from './panelApi'

export const formulariosService = {
  listar: params => panelApi.get('/admin/formularios', { params }).then(r => r.data),
  ver: id => panelApi.get(`/admin/formularios/${id}`).then(r => r.data),
  cambiarEstado: (id, estado) => panelApi.patch(`/admin/formularios/${id}`, { estado }).then(r => r.data),
  eliminar: id => panelApi.delete(`/admin/formularios/${id}`),
  // Descarga como blob: la petición lleva la cookie de sesión (un <a href> directo también,
  // pero así se controla el error y no se abre una pestaña en blanco)
  async exportar(params) {
    const r = await panelApi.get('/admin/formularios/exportar', { params, responseType: 'blob' })
    const url = URL.createObjectURL(r.data)
    const a = Object.assign(document.createElement('a'), { href: url, download: `formularios-${new Date().toISOString().slice(0, 10)}.csv` })
    a.click()
    URL.revokeObjectURL(url)
  },
}
