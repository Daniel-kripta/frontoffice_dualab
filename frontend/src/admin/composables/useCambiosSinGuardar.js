import { onBeforeUnmount, onMounted } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'

// Avisa antes de salir de un editor con cambios sin guardar (navegación interna y cierre de pestaña).
export function useCambiosSinGuardar(hayCambios) {
  const alCerrar = (e) => {
    if (!hayCambios()) return
    e.preventDefault()
    e.returnValue = ''
  }
  onMounted(() => window.addEventListener('beforeunload', alCerrar))
  onBeforeUnmount(() => window.removeEventListener('beforeunload', alCerrar))
  onBeforeRouteLeave(() => !hayCambios() || window.confirm('Tienes cambios sin guardar. ¿Salir sin guardar?'))
}
