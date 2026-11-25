// vite.config.js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

// Tailwind guarda su configuración en memoria mientras vive el proceso: un cambio en
// tailwind.config.js (colores, fuentes) no se aplica hasta reiniciar `npm run dev`.
// Reiniciar Vite desde dentro no basta, así que se avisa en la consola.
const avisoTailwind = {
  name: 'aviso-tailwind-config',
  configureServer(server) {
    server.watcher.on('change', (archivo) => {
      if (archivo.endsWith('tailwind.config.js')) {
        server.config.logger.warn('\n⚠ tailwind.config.js ha cambiado: para de `npm run dev` y vuelve a arrancarlo.\n')
      }
    })
  },
}

// Identificador de este build: __APP_VERSION__ en el bundle y dist/version.json
// (composables/useNuevaVersion.js los compara para avisar de un deploy nuevo)
const APP_VERSION = Date.now().toString(36)

const versionJson = {
  name: 'version-json',
  apply: 'build',
  generateBundle() {
    this.emitFile({ type: 'asset', fileName: 'version.json', source: JSON.stringify({ version: APP_VERSION }) })
  },
}

export default defineConfig({
  plugins: [vue(), avisoTailwind, versionJson],
  define: {
    __APP_VERSION__: JSON.stringify(APP_VERSION),
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: 5174,
    proxy: {
      '/api': {
        target: 'http://localhost:8001',
        changeOrigin: true,
      },
      '/sanctum': {
        target: 'http://localhost:8001',
        changeOrigin: true,
      },
      // Como en producción (.htaccess de la raíz de dualab.es): los sirve el backend
      '^/(robots\\.txt|llms\\.txt|sitemap\\.xml)$': {
        target: 'http://localhost:8001',
        changeOrigin: true,
      },
    },
  },
})
