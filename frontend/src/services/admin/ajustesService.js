import panelApi from './panelApi'

// Grupos: seo | analitica | integraciones (los dos últimos, solo administradores)
export const ajustesService = {
  ver: grupo => panelApi.get(`/admin/ajustes/${grupo}`).then(r => r.data),
  guardar: (grupo, ajustes) => panelApi.put(`/admin/ajustes/${grupo}`, { ajustes }).then(r => r.data),
  borrarSecreto: (grupo, nombre) => panelApi.delete(`/admin/ajustes/${grupo}/${nombre}`).then(r => r.data),
  // destino: 'webhook' (la app DuaLab) o 'crm' (CRM externo)
  probarWebhook: destino => panelApi.post('/admin/ajustes/integraciones/probar-webhook', { destino }).then(r => r.data),
}
