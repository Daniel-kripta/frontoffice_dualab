// Formularios y correo de info@ se atienden en DuaLab Central («Primer contacto», decisión de
// Cynthia del 2026-11-17): Gestión web se queda con el contenido, el diseño, el SEO y la analítica.
// VITE_CENTRAL_URL es pública (no es un secreto).
const local = ['localhost', '127.0.0.1'].includes(window.location.hostname)

export const URL_CENTRAL = (import.meta.env.VITE_CENTRAL_URL || (local ? 'http://localhost:5179' : 'https://central.dualab.es')).replace(/\/$/, '')
export const URL_PRIMER_CONTACTO = `${URL_CENTRAL}/primer-contacto`
export const URL_CORREO_CENTRAL = `${URL_CENTRAL}/primer-contacto/correo`
