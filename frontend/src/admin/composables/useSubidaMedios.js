import { ref } from 'vue'
import { mediosService } from '@/services/admin/mediosService'
import { mensajeError } from '@/services/admin/panelApi'

// Subida de uno o varios archivos a la biblioteca, de uno en uno, con progreso.
// Los límites reales los pone el backend (StoreMedioRequest); aquí solo se avisa antes.
const LIMITES_MB = { imagen: 8, video: 60, documento: 20 }
const EXTENSIONES = {
  imagen: ['jpg', 'jpeg', 'png', 'webp', 'gif'],
  video: ['mp4', 'webm'],
  documento: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'txt', 'csv'],
}
export const ACEPTA = Object.values(EXTENSIONES).flat().map(e => `.${e}`).join(',')

function tipoDe(nombre) {
  const ext = nombre.split('.').pop()?.toLowerCase()
  return Object.keys(EXTENSIONES).find(t => EXTENSIONES[t].includes(ext))
}

let contador = 0

export function useSubidaMedios() {
  const subidas = ref([])

  async function subir(archivos, alSubir) {
    for (const archivo of archivos) {
      subidas.value.unshift({ id: ++contador, nombre: archivo.name, progreso: 0, error: '', hecho: false })
      const item = subidas.value[0] // el proxy reactivo, para que el progreso se vea
      const tipo = tipoDe(archivo.name)
      if (!tipo) { item.error = 'Tipo de archivo no permitido (los SVG tampoco, por seguridad).'; continue }
      if (archivo.size > LIMITES_MB[tipo] * 1024 * 1024) { item.error = `Supera el máximo de ${LIMITES_MB[tipo]} MB para ${tipo}.`; continue }
      try {
        const medio = await mediosService.subir(archivo, p => { item.progreso = p })
        item.hecho = true
        alSubir?.(medio)
      } catch (e) {
        item.error = mensajeError(e, 'No se ha podido subir.')
      }
    }
    // Las correctas desaparecen solas; los errores se quedan hasta cerrarlos
    setTimeout(() => { subidas.value = subidas.value.filter(s => !s.hecho) }, 2500)
  }

  const descartar = s => { subidas.value = subidas.value.filter(x => x !== s) }

  return { subidas, subir, descartar }
}
