import panelApi from './panelApi'

export const mediosService = {
  listar: params => panelApi.get('/admin/medios', { params }).then(r => r.data),
  subir(archivo, alProgresar) {
    const datos = new FormData()
    datos.append('archivo', archivo)
    return panelApi.post('/admin/medios', datos, {
      onUploadProgress: e => alProgresar?.(e.total ? Math.round((e.loaded / e.total) * 100) : 0),
    }).then(r => r.data)
  },
  actualizar: (id, datos) => panelApi.patch(`/admin/medios/${id}`, datos).then(r => r.data),
  eliminar: id => panelApi.delete(`/admin/medios/${id}`),
  // Solo admin: conexión con Cloudinary (sin claves) y consumo de la cuenta
  almacenamiento: () => panelApi.get('/admin/medios/almacenamiento').then(r => r.data),
  comprobarAlmacenamiento: () => panelApi.post('/admin/medios/almacenamiento/comprobar').then(r => r.data),
}
