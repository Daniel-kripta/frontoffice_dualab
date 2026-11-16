// Roles del panel (mismos que config/cms.php). Quién aporta qué:
// - editor: crea contenido (páginas, noticias, medios, SEO) y atiende formularios
// - contactos: solo atiende los formularios recibidos
// - admin: todo lo anterior + aprueba, configura la web y gestiona el equipo
export const NOMBRES_ROLES = { admin: 'Administración', editor: 'Edición', contactos: 'Atención de contactos' }
export const ROLES_PANEL = Object.keys(NOMBRES_ROLES)
export const CONTENIDO = ['admin', 'editor']
export const CONTACTOS = ['admin', 'editor', 'contactos']
export const SOLO_ADMIN = ['admin']
