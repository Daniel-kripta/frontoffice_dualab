import panelApi from './panelApi'

export const visibilidadService = {
  // «Cómo nos ven Google y la IA»: lo que recibe un rastreador en esa dirección de la web
  comoNosVen: (ruta, idioma) => panelApi.get('/admin/vista-buscadores', { params: { ruta, idioma } }).then(r => r.data),
}
