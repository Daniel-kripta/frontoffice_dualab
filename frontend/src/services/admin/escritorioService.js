import panelApi from './panelApi'

export const escritorioService = {
  resumen: () => panelApi.get('/admin/escritorio').then(r => r.data),
}
