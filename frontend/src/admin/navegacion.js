import { CONTENIDO, SOLO_ADMIN } from './roles'
import {
  ClipboardDocumentListIcon, UsersIcon,
  HomeIcon, DocumentDuplicateIcon, NewspaperIcon, PhotoIcon, InboxIcon,
  LanguageIcon, MagnifyingGlassCircleIcon, ChartBarIcon, SwatchIcon, SparklesIcon,
} from '@heroicons/vue/24/outline'

// Menú lateral del panel: Escritorio, Contenido, Sitio web (aspecto global), Visibilidad (Google e IA),
// Formularios y Equipo. `roles`: quién lo ve (ver roles.js); el backend lo vuelve a comprobar. `equivalente`: el plugin de WordPress al que sustituye,
// para quien viene de WordPress.
export const GRUPOS_PANEL = [
  {
    titulo: null,
    items: [
      { label: 'Escritorio', to: { name: 'panel-escritorio' }, icono: HomeIcon, exacta: true, roles: CONTENIDO },
    ],
  },
  {
    titulo: 'Contenido',
    items: [
      { label: 'Páginas', to: { name: 'panel-paginas' }, icono: DocumentDuplicateIcon, prefijo: '/gestion-web/paginas', roles: CONTENIDO },
      {
        label: 'Entradas', to: { name: 'panel-entradas' }, icono: NewspaperIcon, prefijo: '/gestion-web/entradas', roles: CONTENIDO,
        hijos: [
          { label: 'Todas las entradas', to: { name: 'panel-entradas' }, rutas: ['panel-entradas', 'panel-entrada'] },
          { label: 'Añadir nueva', to: { name: 'panel-entrada-nueva' }, rutas: ['panel-entrada-nueva'] },
          { label: 'Categorías', to: { name: 'panel-categorias' }, rutas: ['panel-categorias'] },
        ],
      },
      { label: 'Medios', to: { name: 'panel-medios' }, icono: PhotoIcon, prefijo: '/gestion-web/medios', roles: CONTENIDO },
    ],
  },
  {
    titulo: 'Sitio web',
    items: [
      { label: 'Apariencia', to: { name: 'panel-apariencia' }, icono: SwatchIcon, prefijo: '/gestion-web/apariencia', equivalente: 'Personalizar', roles: SOLO_ADMIN },
      { label: 'Idiomas', to: { name: 'panel-idiomas' }, icono: LanguageIcon, prefijo: '/gestion-web/idiomas', equivalente: 'Multidioma', roles: SOLO_ADMIN },
    ],
  },
  {
    titulo: 'Visibilidad',
    items: [
      { label: 'SEO', to: { name: 'panel-seo' }, icono: MagnifyingGlassCircleIcon, prefijo: '/gestion-web/seo', equivalente: 'All in One SEO', roles: CONTENIDO },
      { label: 'GEO (IA)', to: { name: 'panel-geo' }, icono: SparklesIcon, prefijo: '/gestion-web/geo', equivalente: 'llms.txt y Schema', roles: CONTENIDO },
      { label: 'Analítica', to: { name: 'panel-analitica' }, icono: ChartBarIcon, prefijo: '/gestion-web/analitica', equivalente: 'Site Kit', roles: SOLO_ADMIN },
    ],
  },
  {
    // Los formularios y el correo de info@ se atienden en DuaLab Central («Primer contacto», enlazado
    // desde la propia pantalla): aquí solo queda adónde se manda cada formulario (mailing y webhook)
    titulo: 'Formularios',
    items: [
      { label: 'Mailing y webhook', to: { name: 'panel-integraciones' }, icono: InboxIcon, prefijo: '/gestion-web/formularios/integraciones', equivalente: 'Integraciones', roles: SOLO_ADMIN },
    ],
  },
  {
    titulo: 'Equipo',
    items: [
      { label: 'Usuarios', to: { name: 'panel-usuarios' }, icono: UsersIcon, prefijo: '/gestion-web/usuarios', roles: SOLO_ADMIN },
      { label: 'Historial', to: { name: 'panel-actividad' }, icono: ClipboardDocumentListIcon, prefijo: '/gestion-web/historial', roles: SOLO_ADMIN },
    ],
  },
]
