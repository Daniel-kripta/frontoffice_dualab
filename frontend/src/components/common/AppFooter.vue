<template>
  <footer class="bg-primary-50 border-t border-primary-100">
    <div class="max-w-6xl mx-auto px-6 pt-12 pb-8 grid gap-10 lg:grid-cols-[1fr_3fr]">

      <!-- Logo — mismo tratamiento que navbar -->
      <div>
        <RouterLink :to="{ name: 'home' }" class="inline-flex items-center gap-1 group">
          <img
            :src="apariencia.logo || logoOriginal"
            alt="Logo DuaLab"
            class="h-14 object-contain transition-transform duration-300 group-hover:scale-105"
          />
          <span class="flex flex-col items-start gap-[3px]">
            <span class="font-black tracking-tighter uppercase leading-none flex items-baseline gap-0">
              <span class="text-azul-noche text-[26px]">Dua</span><span class="text-primary-700 text-[26px]">Lab</span>
            </span>
            <LogoLegend />
          </span>
        </RouterLink>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-[auto_auto_auto_auto_auto] gap-8 md:gap-10">
        <nav v-for="col in columns" :key="col.title" :aria-label="col.title">
          <h3 class="font-heading font-bold text-sm text-azul-noche mb-3">{{ col.title }}</h3>
          <ul class="space-y-1.5">
            <li v-for="l in col.links" :key="l.label">
              <RouterLink :to="l.to" class="text-sm text-gray-600 hover:text-primary-700 transition-colors">{{ l.label }}</RouterLink>
            </li>
          </ul>
        </nav>

        <div>
          <h3 class="font-heading font-bold text-sm text-azul-noche mb-3">Contacto</h3>
          <ul class="space-y-1.5 text-sm text-gray-600">
            <li v-if="contact.phone" class="flex items-center gap-2">
              <PhoneIcon class="w-4 h-4 text-azul-noche shrink-0" />
              <a :href="`tel:${contact.phone.replace(/\s/g, '')}`" class="hover:text-primary-700">{{ contact.phone }}</a>
            </li>
            <li v-if="contact.email" class="flex items-center gap-2">
              <EnvelopeIcon class="w-4 h-4 text-azul-noche shrink-0" />
              <a :href="`mailto:${contact.email}`" class="hover:text-primary-700">{{ contact.email }}</a>
            </li>
            <li class="flex items-center gap-2">
              <MapPinIcon class="w-4 h-4 text-azul-noche shrink-0" />
              {{ contact.location }}
            </li>
          </ul>
        </div>

        <div v-if="socials.length" class="flex md:justify-end gap-2.5 col-span-2 md:col-span-1">
          <a v-for="s in socials" :key="s.label" :href="s.href" target="_blank" rel="noopener noreferrer"
             :aria-label="s.label" class="text-azul-noche hover:text-primary-600 transition-colors">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path :d="s.icon" /></svg>
          </a>
        </div>
      </div>
    </div>

    <div class="border-t border-primary-100">
      <div class="max-w-6xl mx-auto px-6 py-5 flex flex-col md:flex-row items-center justify-between gap-3 text-sm text-primary-700">
        <p>© {{ year }} DuaLab. Todos los derechos reservados.</p>
        <!-- PENDIENTE: no existen todavía las páginas legales — texto sin enlace hasta que se creen. -->
        <ul class="flex flex-wrap justify-center items-center gap-x-3 gap-y-1">
          <li v-for="(l, i) in legal" :key="l" class="flex items-center gap-3">
            <span>{{ l }}</span>
            <span v-if="i < legal.length - 1" class="text-primary-300" aria-hidden="true">|</span>
          </li>
        </ul>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { PhoneIcon, EnvelopeIcon, MapPinIcon } from '@heroicons/vue/24/outline'
import LogoLegend from '@/components/common/LogoLegend.vue'
import logoOriginal from '@/assets/logo_colores.png'
import { useConfiguracionWeb } from '@/composables/useConfiguracionWeb'

// Logo: el de Apariencia en el panel o el original
const { apariencia } = useConfiguracionWeb()

const year = new Date().getFullYear()

// Destinos existentes en el frontoffice; las páginas que aún no existen
// (Quiénes somos, Blog, FAQ…) apuntan a la sección más cercana.
const columns = [
  {
    title: 'DuaLab',
    links: [
      { label: 'Quiénes somos',   to: { name: 'home', hash: '#ecosistema' } },
      { label: 'Nuestro impacto', to: { name: 'home', hash: '#experiencia' } },
      { label: 'Actualidad',      to: { name: 'noticias' } },
    ],
  },
  {
    title: 'Soluciones',
    links: [
      { label: 'Centros',          to: { name: 'centros' } },
      { label: 'Empresas',         to: { name: 'empresas' } },
      { label: 'Alumnado',         to: { name: 'alumnos' } },
      { label: 'Administraciones', to: { name: 'entidades' } },
    ],
  },
  {
    title: 'Banco de Conocimiento',
    links: [
      { label: 'Blog',                  to: { name: 'noticias' } },
      { label: 'Casos de éxito',        to: { name: 'noticias' } },
      { label: 'Guías y documentación', to: { name: 'home', hash: '#servicios' } },
      { label: 'Preguntas frecuentes',  to: { name: 'contacto' } },
    ],
  },
]

// PENDIENTE confirmar datos reales: el teléfono del diseño (+34 900 000 000) es
// un marcador, por eso no se muestra (phone: null). Email tomado del diseño.
const contact = {
  phone: null,
  email: 'info@dualab.es',
  location: 'Gran Canaria, España',
}

// PENDIENTE: añadir las URLs reales de los perfiles; solo se muestran los que tengan href.
const socials = [
  { label: 'LinkedIn', href: null, icon: 'M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z' },
  { label: 'Facebook', href: null, icon: 'M22.68 0H1.32C.59 0 0 .59 0 1.32v21.36C0 23.41.59 24 1.32 24h11.5v-9.29H9.69v-3.62h3.13V8.41c0-3.1 1.89-4.79 4.66-4.79 1.32 0 2.46.1 2.79.14v3.24h-1.92c-1.5 0-1.79.72-1.79 1.77v2.31h3.59l-.47 3.62h-3.12V24h6.12c.73 0 1.32-.59 1.32-1.32V1.32C24 .59 23.41 0 22.68 0z' },
  { label: 'YouTube',  href: null, icon: 'M23.5 6.19a3.02 3.02 0 0 0-2.12-2.14C19.5 3.55 12 3.55 12 3.55s-7.5 0-9.38.5A3.02 3.02 0 0 0 .5 6.19C0 8.07 0 12 0 12s0 3.93.5 5.81a3.02 3.02 0 0 0 2.12 2.14c1.88.5 9.38.5 9.38.5s7.5 0 9.38-.5a3.02 3.02 0 0 0 2.12-2.14C24 15.93 24 12 24 12s0-3.93-.5-5.81zM9.55 15.57V8.43L15.82 12l-6.27 3.57z' },
].filter((s) => s.href)

const legal = ['Aviso Legal', 'Política de privacidad', 'Política de cookies', 'Accesibilidad']
</script>
