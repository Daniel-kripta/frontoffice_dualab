/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts}'],
  theme: {
    extend: {
      colors: {
        // Escala general de marca (chrome genérico: navbar, footer, secciones
        // que no pertenecen a un colectivo concreto). Generada a partir del
        // azul del nuevo logo (src/assets/logo_colores.png).
        // Variables CSS: la paleta se puede cambiar desde el panel (Ajustes → Apariencia,
        // opciones cerradas en src/data/apariencia.js). Valores por defecto en style.css
        // (:root), los de la escala original generada desde el azul del logo.
        primary: Object.fromEntries([50, 100, 200, 300, 400, 500, 600, 700, 800, 900]
          .map(n => [n, `rgb(var(--c-primary-${n}) / <alpha-value>)`])),
        // Colores por colectivo, extraídos directamente del logo — un único
        // punto de cambio si se pide ajustar el tono de un colectivo.
        centros: '#3072AA',
        empresas: '#509928',
        administraciones: '#19A7A8',
        alumnos: '#FF8920',
        // Azul noche de marca (Manual de marca e imagen DUALAB V2.0, sección
        // 05/Paleta): color global de estructura y navegación — footer,
        // cabeceras y chrome que representa la marca en su conjunto.
        'azul-noche': 'rgb(var(--c-noche) / <alpha-value>)',
        // Fondo claro para alternar secciones con el blanco (sutil, entre
        // primary-50 y primary-100). Mismo tono que el fondo del diseño.
        superficie: '#EDF4FA',
        // Panel de gestión web (/gestion-web), mismos valores que la app DuaLab
        // (frontend-microretos/src/style.css): texto secundario 6,3:1 sobre blanco y
        // turquesa oscuro del rol admin para botones con texto blanco (5,0:1).
        'texto-secundario': '#526176',
        'administraciones-dark': '#0F7C7D',
        'azul-noche-border': '#2C3F57',
        'fondo-panel': '#F4F7FA',
      },
      // El texto «azul noche» (títulos) tiene su propia variable, para poder cambiarlo sin
      // cambiar los fondos oscuros (franja final): Apariencia → color de los títulos.
      textColor: {
        'azul-noche': 'rgb(var(--c-titulos) / <alpha-value>)',
      },
      fontFamily: {
        // Fuentes elegibles desde el panel (Apariencia); por defecto Inter y Montserrat
        sans: ['var(--fuente-texto)'],
        // Montserrat para titulares (manual, sección 07/Tipografía).
        heading: ['var(--fuente-titulos)'],
        // Rotulación manuscrita de los diseños de marca ("Ideas de hoy…"): fija.
        hand: ['Caveat', 'cursive'],
      },

    },
  },
  plugins: [],
}
