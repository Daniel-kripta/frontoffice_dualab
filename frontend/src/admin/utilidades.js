// Utilidades de presentación del panel

export function fechaHora(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('es-ES', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

export function fecha(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' })
}

// «hace 5 min», «hace 3 h», «ayer», o la fecha
export function haceCuanto(iso) {
  if (!iso) return '—'
  const min = Math.round((Date.now() - new Date(iso).getTime()) / 60000)
  if (min < 1) return 'ahora mismo'
  if (min < 60) return `hace ${min} min`
  const h = Math.round(min / 60)
  if (h < 24) return `hace ${h} h`
  if (h < 48) return 'ayer'
  return fecha(iso)
}

export function tamano(bytes) {
  if (!bytes) return '0 KB'
  if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`
  if (bytes < 1024 ** 3) return `${(bytes / 1024 / 1024).toFixed(1).replace('.', ',')} MB`
  return `${(bytes / 1024 ** 3).toFixed(2).replace('.', ',')} GB`
}

// URL pública de la web (para enlaces «Ver» y la vista previa de Google)
export const URL_WEB = window.location.origin

export const ESTADOS_ENTRADA = {
  publicada:  { nombre: 'Publicada',  clase: 'bg-emerald-50 text-emerald-700' },
  programada: { nombre: 'Programada', clase: 'bg-sky-50 text-sky-800' },
  pendiente:  { nombre: 'Pendiente de aprobar', clase: 'bg-amber-50 text-amber-800' },
  borrador:   { nombre: 'Borrador',   clase: 'bg-gray-100 text-gray-700' },
}

// Chips por formulario / tipo de contacto (Formularios y remitentes conocidos en Correo)
export const COLORES_FORMULARIO = {
  rapido: 'bg-primary-100 text-primary-700', centro: 'bg-centros/10 text-centros', empresa: 'bg-empresas/10 text-[#3D7A1E]',
  alumno: 'bg-alumnos/15 text-[#B85E0A]', administracion: 'bg-administraciones/10 text-[#0F7C7D]',
}
