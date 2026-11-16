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

export default defineConfig({
  plugins: [vue(), avisoTailwind],
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
    },
  },
})
