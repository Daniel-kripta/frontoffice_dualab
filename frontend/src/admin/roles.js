// Roles del panel (mismos que config/cms.php). Quién aporta qué:
// - editor: crea contenido (páginas, noticias, medios, SEO)
// - admin: todo lo anterior + aprueba, configura la web y gestiona el equipo
// Atención al cliente (formularios y correo de info@) ya no es de Gestión web: es un rol de la app
// DuaLab que trabaja en DuaLab Central («Primer contacto»)
export const NOMBRES_ROLES = { admin: 'Administración', editor: 'Edición' }
export const ROLES_PANEL = Object.keys(NOMBRES_ROLES)
export const CONTENIDO = ['admin', 'editor']
export const SOLO_ADMIN = ['admin']
