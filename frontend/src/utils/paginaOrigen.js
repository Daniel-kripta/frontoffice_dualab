// Página de la web desde la que se envía un formulario (la bandeja del panel de
// gestión web los clasifica por ella). Con `anterior`, la página de la que venía
// el visitante (Vue Router la guarda en history.state.back): en /contacto interesa
// saber si llegó desde /centros, /empresas-asociadas…
export function paginaOrigen({ anterior = false } = {}) {
  const ruta = (anterior && window.history.state?.back) || window.location.pathname
  return String(ruta).split('#')[0].replace(/\s/g, '').slice(0, 255) || '/'
}
