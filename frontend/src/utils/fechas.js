// Fecha corta en español: «19 jun 2026»
export function fechaCorta(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' }).replace('.', '')
}
