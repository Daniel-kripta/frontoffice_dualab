import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { webService } from '@/services/webService'
import { useConfiguracionWeb, cargarConfiguracionWeb } from './useConfiguracionWeb'
import { useSeo } from './useSeo'

/**
 * Textos, botones e imágenes editables de una página desde el panel de gestión web.
 * La vista conserva su texto original como respaldo: se pinta al instante y, si en el
 * panel se ha cambiado algo, se sustituye en cuanto llega (o se queda si el backend falla).
 *
 *   const p = usePagina('centros', { titulo: 'Centros Educativos' })
 *   p.t('hero.titulo', 'La Formación Dual más conectada,')
 *   p.tiene('hero.descripcion')   // ¿editado? (para mostrar el original con sus resaltados)
 *   p.boton('hero.boton_principal', { texto: 'Registrar mi centro', to: contactoCentro })
 *   p.imagen('hero.imagen', dua17)
 */
export function usePagina(clave, { titulo = '', seo: conSeo = true } = {}) {
  const valores = ref({})
  const router = useRouter()
  const { idioma } = useConfiguracionWeb()
  const { aplicar } = useSeo()

  async function cargar() {
    await cargarConfiguracionWeb()
    try {
      const data = await webService.pagina(clave, idioma.value)
      if (data.publicada === false) {
        router.replace({ name: 'home' })
        return
      }
      valores.value = data.valores ?? {}
      if (conSeo) aplicar({ titulo, seo: data.seo ?? {} })
    } catch {
      if (conSeo) aplicar({ titulo })
    }
  }
  watch(idioma, cargar, { immediate: true })

  const vacio = v => v === undefined || v === null || v === ''

  return {
    valores,
    t: (campo, original) => (vacio(valores.value[campo]) ? original : valores.value[campo]),
    tiene: campo => !vacio(valores.value[campo]),
    boton(campo, original) {
      const v = valores.value[campo] ?? {}
      return { texto: v.texto || original.texto, to: v.url || original.to }
    },
    imagen: (campo, original) => valores.value[campo] || original,
  }
}
