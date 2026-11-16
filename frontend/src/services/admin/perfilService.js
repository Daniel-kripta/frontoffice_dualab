import panelApi from './panelApi'

export const perfilService = {
  ver: () => panelApi.get('/admin/perfil').then(r => r.data),
  guardar: datos => panelApi.patch('/admin/perfil', datos).then(r => r.data),
}
