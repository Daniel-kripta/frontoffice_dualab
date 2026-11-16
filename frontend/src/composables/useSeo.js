import { useConfiguracionWeb } from './useConfiguracionWeb'

// Etiquetas <head> de la página (título, descripción, Open Graph, canonical, robots).
// La SPA las pone al cargar cada vista; el sitemap lo sirve el backend (/api/web/sitemap.xml).
function meta(atributo, clave, contenido) {
  let el = document.head.querySelector(`meta[${atributo}="${clave}"]`)
  if (!contenido) { el?.remove(); return }
  if (!el) {
    el = document.createElement('meta')
    el.setAttribute(atributo, clave)
    document.head.appendChild(el)
  }
  el.setAttribute('content', contenido)
}

function canonical(url) {
  let el = document.head.querySelector('link[rel="canonical"]')
  if (!url) { el?.remove(); return }
  if (!el) {
    el = document.createElement('link')
    el.rel = 'canonical'
    document.head.appendChild(el)
  }
  el.href = url
}

export function useSeo() {
  const { ajuste } = useConfiguracionWeb()

  function aplicar({ titulo, seo = {} } = {}) {
    const sitio = ajuste('seo.titulo_sitio', 'DuaLab')
    const sep = ajuste('seo.separador', '|')
    const tituloFinal = seo.meta_titulo || (titulo ? `${titulo} ${sep} ${sitio}` : sitio)
    const descripcion = seo.meta_descripcion || ajuste('seo.descripcion')
    const imagen = seo.imagen_og || ajuste('seo.imagen_og')

    document.title = tituloFinal
    meta('name', 'description', descripcion)
    meta('name', 'robots', seo.noindex ? 'noindex, nofollow' : null)
    meta('property', 'og:title', tituloFinal)
    meta('property', 'og:description', descripcion)
    meta('property', 'og:image', imagen)
    meta('property', 'og:url', window.location.href.split('#')[0])
    meta('property', 'og:type', seo.tipo || 'website')
    meta('name', 'twitter:card', imagen ? 'summary_large_image' : 'summary')
    canonical(seo.canonical || window.location.origin + window.location.pathname)
  }

  return { aplicar }
}
