import { ref } from 'vue'

// Avisos breves del panel (guardado, error…). Los muestra AvisosPanel.vue.
const avisos = ref([])
let id = 0

function mostrar(tipo, texto, ms = 4000) {
  const aviso = { id: ++id, tipo, texto }
  avisos.value.push(aviso)
  if (avisos.value.length > 4) avisos.value.shift()
  setTimeout(() => cerrar(aviso.id), ms)
}

function cerrar(idAviso) {
  avisos.value = avisos.value.filter(a => a.id !== idAviso)
}

export function useAvisos() {
  return {
    avisos,
    cerrar,
    ok: texto => mostrar('ok', texto),
    error: texto => mostrar('error', texto, 6000),
  }
}
