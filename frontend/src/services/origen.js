// De dónde llega cada persona a la web: los parámetros utm_* del enlace por el que entró (los que pone
// marketing en anuncios, correos y publicaciones), la página de entrada y la web de procedencia.
//
// Se guarda SOLO en memoria (una variable del módulo), nunca en cookies ni en el almacenamiento del
// navegador: así no es una tecnología que haya que consentir. Dura mientras se navega dentro de la web;
// si la persona recarga la página sin los parámetros en la dirección, se pierde (es lo esperable).
//
// Solo se aceptan las claves de CLAVES, recortadas: el servidor las valida igualmente.
const CLAVES = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term']
const MAX = 150

let origen = null

const limpio = (v) => (typeof v === 'string' ? v.trim().slice(0, MAX) : '')

/** Webs de procedencia que no cuentan como origen: la propia. */
function referrerExterno() {
  try {
    if (!document.referrer) return ''
    const url = new URL(document.referrer)
    return url.host === window.location.host ? '' : `${url.origin}${url.pathname}`.slice(0, 255)
  } catch {
    return ''
  }
}

/** Se llama una vez al arrancar la web. Si el enlace no trae nada, no guarda nada. */
export function capturarOrigen() {
  const params = new URLSearchParams(window.location.search)
  const nuevo = {}
  for (const clave of CLAVES) {
    const valor = limpio(params.get(clave))
    if (valor) nuevo[clave] = valor
  }
  const referrer = referrerExterno()
  if (referrer) nuevo.referrer = referrer
  if (Object.keys(nuevo).length) {
    nuevo.landing = window.location.pathname.slice(0, 255)
    origen = nuevo
  }
}

/** Lo que se envía con el formulario (undefined si no hay origen: la clave no viaja). */
export function origenActual() {
  return origen ?? undefined
}
