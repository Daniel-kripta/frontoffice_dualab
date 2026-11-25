import { createRouter, createWebHistory } from 'vue-router'
import { CONTENIDO, SOLO_ADMIN } from '@/admin/roles'

const routes = [
  {
    path: '/',
    name: 'home',
    component: () => import('@/views/HomeView.vue'),
  },
  {
    path: '/noticias',
    name: 'noticias',
    component: () => import('@/views/NoticiasView.vue'),
  },
  {
    // Path distinto (no /empresas a secas): la herramienta ya usa /empresas
    // para su propia gestión interna de empresas — mismo dominio, tienen que
    // no chocar. El `name` se queda igual, así ningún RouterLink/$router.push
    // por nombre necesita tocarse.
    path: '/empresas-asociadas',
    name: 'empresas',
    component: () => import('@/views/EmpresasView.vue'),
  },
  {
    path: '/centros',
    name: 'centros',
    component: () => import('@/views/CentrosView.vue'),
  },
  {
    // Path distinto (no /alumnado a secas): la herramienta ya usa /alumnado/...
    // para su gestión interna — mismo dominio, tienen que no chocar.
    path: '/para-alumnado',
    name: 'alumnos',
    component: () => import('@/views/AlumnadoView.vue'),
  },
  {
    path: '/entidades',
    name: 'entidades',
    component: () => import('@/views/EntidadesView.vue'),
  },
  {
    path: '/noticias/:slug',
    name: 'noticia-detalle',
    component: () => import('@/views/NoticiaDetalleView.vue'),
  },
  {
    path: '/familias',
    name: 'familias',
    component: () => import('@/views/FamiliasView.vue'),
  },
  {
    path: '/familias/:slug',
    name: 'familia-detalle',
    component: () => import('@/views/FamiliaDetalleView.vue'),
  },
  {
    // Path distinto (no /retos/:id a secas): la herramienta ya usa /retos/:id
    // para la ficha interna de un microreto — mismo dominio, tienen que no
    // chocar. El `name` se queda igual.
    path: '/retos-destacados/:id',
    name: 'reto-detalle',
    component: () => import('@/views/RetoDetalleView.vue'),
  },
  {
    // "Ver proyecto" de un reto: en vez de mostrar el proyecto completado en
    // abierto, se pide una demo.
    path: '/retos-destacados/:id/solicitar-demo',
    name: 'reto-solicitar-demo',
    component: () => import('@/views/SolicitarDemoProyectoView.vue'),
  },
  {
    // El proyecto ya no se muestra en abierto (ProyectoDetalleView se conserva
    // por si se vuelve a activar): los enlaces antiguos van a contacto.
    path: '/proyectos-destacados/:uuid',
    name: 'proyecto-detalle',
    redirect: { name: 'contacto' },
  },
  {
    // Reemplaza a /login, /register y /dashboard: no hay un portal propio de
    // alumno/empresa/centro que mantener — el login real vive en dualab.es
    // (la herramienta). Esto es solo un formulario de contacto/lead.
    path: '/contacto',
    name: 'contacto',
    component: () => import('@/views/ContactoView.vue'),
  },
  // ── Panel de gestión web (WordPress-like) ─────────────────────────────────
  // /gestion-web y no /admin: el dominio se comparte con la herramienta DuaLab.
  {
    path: '/gestion-web/acceso',
    name: 'panel-acceso',
    component: () => import('@/admin/vistas/AccesoView.vue'),
    meta: { panel: true, titulo: 'Acceso' },
  },
  {
    // Enlace del correo de invitación o de «he olvidado mi contraseña»
    path: '/gestion-web/crear-contrasena',
    name: 'panel-crear-contrasena',
    component: () => import('@/admin/vistas/CrearContrasenaView.vue'),
    meta: { panel: true, publica: true, titulo: 'Crear contraseña' },
  },
  {
    path: '/gestion-web',
    component: () => import('@/admin/AdminLayout.vue'),
    meta: { panel: true, requiereSesion: true },
    children: [
      { path: '', name: 'panel-escritorio', component: () => import('@/admin/vistas/EscritorioView.vue'), meta: { titulo: 'Escritorio', roles: CONTENIDO } },
      { path: 'paginas', name: 'panel-paginas', component: () => import('@/admin/vistas/PaginasView.vue'), meta: { titulo: 'Páginas', roles: CONTENIDO } },
      { path: 'paginas/:clave', name: 'panel-pagina', component: () => import('@/admin/vistas/PaginaEditorView.vue'), meta: { titulo: 'Editar página', roles: CONTENIDO } },
      { path: 'entradas', name: 'panel-entradas', component: () => import('@/admin/vistas/EntradasView.vue'), meta: { titulo: 'Entradas', roles: CONTENIDO } },
      { path: 'entradas/nueva', name: 'panel-entrada-nueva', component: () => import('@/admin/vistas/EntradaEditorView.vue'), meta: { titulo: 'Nueva entrada', roles: CONTENIDO } },
      { path: 'entradas/categorias', name: 'panel-categorias', component: () => import('@/admin/vistas/CategoriasView.vue'), meta: { titulo: 'Categorías', roles: CONTENIDO } },
      { path: 'entradas/:id(\\d+)', name: 'panel-entrada', component: () => import('@/admin/vistas/EntradaEditorView.vue'), meta: { titulo: 'Editar entrada', roles: CONTENIDO } },
      // Formularios y Correo se atienden en DuaLab Central («Primer contacto»): los enlaces antiguos explican dónde
      { path: 'formularios', name: 'panel-formularios', component: () => import('@/admin/vistas/EnCentralView.vue'), meta: { titulo: 'Formularios', roles: CONTENIDO } },
      { path: 'formularios/integraciones', name: 'panel-integraciones', component: () => import('@/admin/vistas/IntegracionesView.vue'), meta: { titulo: 'Mailing y webhook', roles: SOLO_ADMIN } },
      { path: 'correo', name: 'panel-correo', component: () => import('@/admin/vistas/EnCentralView.vue'), meta: { titulo: 'Correo', roles: CONTENIDO } },
      { path: 'medios', name: 'panel-medios', component: () => import('@/admin/vistas/MediosView.vue'), meta: { titulo: 'Medios', roles: CONTENIDO } },
      { path: 'perfil', name: 'panel-perfil', component: () => import('@/admin/vistas/PerfilView.vue'), meta: { titulo: 'Mi perfil' } },
      { path: 'idiomas', name: 'panel-idiomas', component: () => import('@/admin/vistas/IdiomasView.vue'), meta: { titulo: 'Idiomas', roles: SOLO_ADMIN } },
      { path: 'apariencia', name: 'panel-apariencia', component: () => import('@/admin/vistas/AparienciaView.vue'), meta: { titulo: 'Apariencia', roles: SOLO_ADMIN } },
      { path: 'seo', name: 'panel-seo', component: () => import('@/admin/vistas/SeoView.vue'), meta: { titulo: 'SEO', roles: CONTENIDO } },
      { path: 'geo', name: 'panel-geo', component: () => import('@/admin/vistas/GeoView.vue'), meta: { titulo: 'GEO (IA)', roles: CONTENIDO } },
      { path: 'usuarios', name: 'panel-usuarios', component: () => import('@/admin/vistas/UsuariosView.vue'), meta: { titulo: 'Usuarios', roles: SOLO_ADMIN } },
      { path: 'historial', name: 'panel-actividad', component: () => import('@/admin/vistas/ActividadView.vue'), meta: { titulo: 'Historial', roles: SOLO_ADMIN } },
      // Antes se llamaba «Actividad»: los enlaces guardados siguen funcionando
      { path: 'actividad', redirect: to => ({ name: 'panel-actividad', query: to.query }) },
      { path: 'analitica', name: 'panel-analitica', component: () => import('@/admin/vistas/AnaliticaView.vue'), meta: { titulo: 'Analítica', roles: SOLO_ADMIN } },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (!from.name) return { top: 0, left: 0 }
    if (savedPosition) return savedPosition
    if (to.hash) return new Promise(resolve => setTimeout(() => resolve({ el: to.hash, behavior: 'smooth' }), 80))
    return { top: 0 }
  },
})

// Panel de gestión web: solo comprueba la sesión en sus rutas (la web pública no
// llama a /auth/me). Es solo UX: cada endpoint /api/admin lo protege el backend.
router.beforeEach(async (to) => {
  if (!to.matched.some(r => r.meta.panel)) return
  // Todo el panel (también el acceso y crear contraseña) se ve como «Gestión web» en la pestaña
  document.title = 'Gestión web'
  const { usePanelSesion } = await import('@/stores/panelSesion')
  const sesion = usePanelSesion()
  if (!sesion.iniciada) await sesion.init()

  if (to.meta.publica) return true
  if (to.name === 'panel-acceso') {
    return sesion.puedeEntrar ? sesion.inicio : true
  }
  if (!sesion.conSesion) return { name: 'panel-acceso', query: { redirect: to.fullPath } }
  if (!sesion.puedeEntrar) return { name: 'panel-acceso', query: { sinPermiso: '1' } }
  if (!sesion.puede(to.meta.roles)) return sesion.inicio
})

// El panel tiene su propio icono en la pestaña (turquesa con «W»): comparte build con la web
// pública y sin esto se confundiría con ella. Al volver a la web se repone el de la web.
const iconosWeb = {}
router.afterEach((to) => {
  const enPanel = to.matched.some(r => r.meta.panel)
  for (const [rel, archivo] of [['icon', 'favicon.png'], ['apple-touch-icon', 'apple-touch-icon.png']]) {
    const enlace = document.querySelector(`link[rel="${rel}"]`)
    if (!enlace) continue
    iconosWeb[rel] ??= enlace.getAttribute('href')
    enlace.setAttribute('href', enPanel ? `${import.meta.env.BASE_URL}gestion-web-icono/${archivo}` : iconosWeb[rel])
  }
})

export default router
