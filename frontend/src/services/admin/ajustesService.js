import panelApi from './panelApi'

// Grupos: seo | analitica | integraciones (los dos últimos, solo administradores)
export const ajustesService = {
  ver: grupo => panelApi.get(`/admin/ajustes/${grupo}`).then(r => r.data),
  guardar: (grupo, ajustes) => panelApi.put(`/admin/ajustes/${grupo}`, { ajustes }).then(r => r.data),
  borrarSecreto: (grupo, nombre) => panelApi.delete(`/admin/ajustes/${grupo}/${nombre}`).then(r => r.data),
  probarWebhook: () => panelApi.post('/admin/ajustes/integraciones/probar-webhook').then(r => r.data),
}
