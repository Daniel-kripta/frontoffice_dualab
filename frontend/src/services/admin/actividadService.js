import panelApi from './panelApi'

export const actividadService = {
  // Registro completo (solo admin)
  listar: params => panelApi.get('/admin/actividad', { params }).then(r => r.data),
  // Historial de una página (clave) o entrada (id)
  historial: (tipo, ref) => panelApi.get(`/admin/actividad/${tipo}/${encodeURIComponent(ref)}`).then(r => r.data),
}
