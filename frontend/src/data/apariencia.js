// Apariencia de la web pública que se puede cambiar desde el panel (Ajustes → Apariencia).
// Todo son opciones cerradas: el backend solo acepta estas claves (config/cms.php →
// apariencia) y el panel no permite CSS, HTML ni colores libres.
//
// Paletas: «principal» (botones, enlaces, cabeceras azules) en escala 50–900 y «noche»
// (pie, estructura). Todas probadas: texto blanco sobre principal-600 ≥ 4,5:1.
// Los colores de colectivo (centros, empresas, alumnado, administraciones) no cambian.
import catalogoFuentes from './fuentes.json'

export const PALETAS = {
  marca: {
    nombre: 'Marca DuaLab (azul)',
    noche: '#17283E',
    principal: { 50: '#f6fafc', 100: '#dae8f5', 200: '#bad5ec', 300: '#93bde0', 400: '#6ba4d5', 500: '#438cca', 600: '#3072aa', 700: '#275d8a', 800: '#1e476a', 900: '#15324b' },
  },
  turquesa: {
    nombre: 'Turquesa',
    noche: '#12313A',
    principal: { 50: '#f5fafa', 100: '#d4e7e8', 200: '#b2d5d5', 300: '#87bebe', 400: '#5ca6a7', 500: '#339090', 600: '#0f7c7d', 700: '#0c6364', 800: '#094d4e', 900: '#073838' },
  },
  bosque: {
    nombre: 'Verde bosque',
    noche: '#1B2E14',
    principal: { 50: '#f7faf6', 100: '#dce7d6', 200: '#c1d4b7', 300: '#9ebc8e', 400: '#7ba566', 500: '#5a8e40', 600: '#3d7a1e', 700: '#316218', 800: '#264c13', 900: '#1b370d' },
  },
  violeta: {
    nombre: 'Violeta',
    noche: '#211B3F',
    principal: { 50: '#f8f8fc', 100: '#e1dff2', 200: '#cbc5e8', 300: '#ada5db', 400: '#8f85ce', 500: '#7466c2', 600: '#5b4bb7', 700: '#493c92', 800: '#382e71', 900: '#292252' },
  },
  grafito: {
    nombre: 'Grafito',
    noche: '#111827',
    principal: { 50: '#f7f7f8', 100: '#dbdde0', 200: '#bfc2c7', 300: '#9ba0a8', 400: '#777e89', 500: '#555e6b', 600: '#374151', 700: '#2c3441', 800: '#222832', 900: '#191d24' },
  },
  teja: {
    nombre: 'Teja',
    noche: '#2B1A15',
    principal: { 50: '#fcf7f7', 100: '#f0ddd9', 200: '#e3c3bb', 300: '#d4a196', 400: '#c57f70', 500: '#b65f4c', 600: '#a9432c', 700: '#873623', 800: '#692a1b', 900: '#4c1e14' },
  },
}

// Fuentes de Google Fonts revisadas (sin URL libre): catálogo único en fuentes.json, con su
// grupo, grosores disponibles y usos. El backend genera su lista blanca del mismo archivo
// (config/cms.php → fuentes; un test comprueba que coinciden).
export const GRUPOS_FUENTES = catalogoFuentes.grupos
// Grosores que usa el diseño en cada caso; se piden solo los que la fuente tiene
// (Google rechaza la petición entera si se pide un grosor que no existe)
const PESOS_USO = { titulos: [600, 700, 800], texto: [300, 400, 500, 600, 700] }

function construir(uso) {
  return Object.fromEntries(catalogoFuentes.fuentes.filter(f => f.usos.includes(uso)).map((f) => {
    const pesos = PESOS_USO[uso].filter(p => f.pesos.includes(p))
    const respaldo = f.grupo === 'con-remates' ? 'Georgia, serif' : 'ui-sans-serif, system-ui, sans-serif'
    return [f.id, {
      nombre: f.familia,
      grupo: f.grupo,
      css: `'${f.familia}', ${respaldo}`,
      google: `${f.familia.replace(/ /g, '+')}:wght@${(pesos.length ? pesos : [f.pesos[0]]).join(';')}`,
      // Un grosor para la muestra del desplegable (600 si lo tiene, si no el más cercano)
      muestra: f.pesos.reduce((a, b) => (Math.abs(b - 600) < Math.abs(a - 600) ? b : a)),
    }]
  }))
}
export const FUENTES_TITULOS = construir('titulos')
export const FUENTES_TEXTO = construir('texto')

/** Carga de una vez las muestras de muchas fuentes, solo con las letras de su nombre (petición ligera). */
const muestrasCargadas = new Set()
export function cargarMuestrasFuentes(catalogo) {
  const pendientes = Object.entries(catalogo).filter(([id]) => !muestrasCargadas.has(id))
  for (let i = 0; i < pendientes.length; i += 20) {
    const lote = pendientes.slice(i, i + 20)
    const familias = lote.map(([, f]) => `family=${f.nombre.replace(/ /g, '+')}:wght@${f.muestra}`).join('&')
    const letras = encodeURIComponent([...new Set(`${lote.map(([, f]) => f.nombre).join('')}ÁÉÍÓÚáéíóúÑñ`)].join(''))
    document.head.appendChild(Object.assign(document.createElement('link'), {
      rel: 'stylesheet', href: `https://fonts.googleapis.com/css2?${familias}&text=${letras}&display=swap`,
    }))
    lote.forEach(([id]) => muestrasCargadas.add(id))
  }
}

// Tamaño base del texto: todo el diseño está en rem, así que escala de forma proporcional
export const TAMANOS = {
  normal:       { nombre: 'Normal',     raiz: '100%' },
  grande:       { nombre: 'Grande',     raiz: '106.25%' },
  'muy-grande': { nombre: 'Muy grande', raiz: '112.5%' },
}

export const APARIENCIA_ORIGINAL = {
  paleta: 'marca', color_principal: null, color_noche: null, color_titulos: null,
  fuente_titulos: 'montserrat', fuente_texto: 'inter', tamano_texto: 'normal', logo: null, favicon: null,
}

// ── Paleta personalizada: dos colores en hexadecimal y la escala 50–900 calculada ──
// Misma mezcla con blanco/negro que se usó para las paletas predefinidas.
const MEZCLA_BLANCO = { 50: 0.96, 100: 0.82, 200: 0.68, 300: 0.5, 400: 0.32, 500: 0.15 }
const MEZCLA_NEGRO = { 700: 0.2, 800: 0.38, 900: 0.55 }
const aRgb = hex => hex.replace('#', '').match(/../g).map(h => parseInt(h, 16))
const aHex = rgb => `#${rgb.map(c => Math.round(c).toString(16).padStart(2, '0')).join('')}`
const mezclar = (rgb, destino, peso) => rgb.map(c => c * (1 - peso) + destino * peso)

export const esHex = v => typeof v === 'string' && /^#[0-9a-fA-F]{6}$/.test(v)

export function escalaDesde(hex) {
  const rgb = aRgb(hex)
  const escala = { 600: hex.toLowerCase() }
  for (const [n, p] of Object.entries(MEZCLA_BLANCO)) escala[n] = aHex(mezclar(rgb, 255, p))
  for (const [n, p] of Object.entries(MEZCLA_NEGRO)) escala[n] = aHex(mezclar(rgb, 0, p))
  return escala
}

/** Contraste del texto blanco sobre el color (WCAG). ≥ 4,5 = legible; el backend exige lo mismo. */
export function contrasteConBlanco(hex) {
  const l = aRgb(hex).map((c) => { c /= 255; return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4 })
  return 1.05 / (0.2126 * l[0] + 0.7152 * l[1] + 0.0722 * l[2] + 0.05)
}

/**
 * Paleta efectiva: la predefinida elegida o la personalizada. En la personalizada cada
 * color se aplica en cuanto es un código válido (mientras se escribe el otro no se pierde).
 */
export function paletaDe(a = {}) {
  if (a.paleta === 'personalizada') {
    return {
      nombre: 'Personalizada',
      principal: esHex(a.color_principal) ? escalaDesde(a.color_principal) : PALETAS.marca.principal,
      noche: esHex(a.color_noche) ? a.color_noche : PALETAS.marca.noche,
      // Títulos: su color propio o, si no hay, el de la franja
      titulos: esHex(a.color_titulos) ? a.color_titulos : (esHex(a.color_noche) ? a.color_noche : PALETAS.marca.noche),
    }
  }
  return PALETAS[a.paleta] ?? PALETAS.marca
}

const canales = hex => hex.replace('#', '').match(/../g).map(h => parseInt(h, 16)).join(' ')

/** Variables CSS de una apariencia (para :root o para un contenedor de vista previa). */
export function variablesApariencia(a = {}) {
  const paleta = paletaDe(a)
  const vars = {
    '--c-noche': canales(paleta.noche),
    '--c-titulos': canales(paleta.titulos ?? paleta.noche),
    '--fuente-titulos': (FUENTES_TITULOS[a.fuente_titulos] ?? FUENTES_TITULOS.montserrat).css,
    '--fuente-texto': (FUENTES_TEXTO[a.fuente_texto] ?? FUENTES_TEXTO.inter).css,
  }
  for (const [n, hex] of Object.entries(paleta.principal)) vars[`--c-primary-${n}`] = canales(hex)
  return vars
}

/** Carga desde Google Fonts las fuentes elegidas que no vienen ya en style.css. */
export function cargarFuentes(a = {}) {
  const yaCargadas = ['montserrat', 'inter'] // vienen en style.css
  for (const [catalogo, clave] of [[FUENTES_TITULOS, a.fuente_titulos], [FUENTES_TEXTO, a.fuente_texto]]) {
    const f = catalogo[clave]
    if (!f || yaCargadas.includes(clave) || document.getElementById(`fuente-${clave}`)) continue
    const link = Object.assign(document.createElement('link'), {
      id: `fuente-${clave}`, rel: 'stylesheet', href: `https://fonts.googleapis.com/css2?family=${f.google}&display=swap`,
    })
    document.head.appendChild(link)
  }
}
