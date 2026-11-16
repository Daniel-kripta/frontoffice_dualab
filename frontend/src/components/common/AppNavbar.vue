<template>
  <nav
    class="sticky top-0 z-50 transition-all duration-500"
    :class="scrolled
      ? 'bg-white/80 backdrop-blur-xl shadow-sm border-b border-white/30'
      : 'bg-white border-b border-gray-100'"
  >
    <div
      class="max-w-6xl mx-auto px-6 flex items-center justify-between transition-all duration-300"
      :class="scrolled ? 'h-14' : 'h-16'"
    >

      <!-- Logo: separado de la D para que se vea completo el aspa azul, y con
           margen propio para no quedar pegado al resto de enlaces del topbar -->
      <RouterLink :to="{ name: 'home' }" class="logo-animate group flex items-center gap-1 mr-3 shrink-0">
        <img
          :src="apariencia.logo || logoOriginal"
          alt="Logo DuaLab"
          class="object-contain relative z-10 transition-all duration-300 group-hover:scale-105"
          :class="scrolled ? 'h-11' : 'h-14'"
        />
        <span class="relative z-20 flex flex-col items-start gap-[3px]">
          <span class="font-black tracking-tighter uppercase leading-none flex items-baseline gap-0">
            <span
              class="text-azul-noche transition-all duration-300"
              :class="scrolled ? 'text-[22px]' : 'text-[28px]'"
            >Dua</span><span
              class="text-primary-700 transition-all duration-300"
              :class="scrolled ? 'text-[22px]' : 'text-[28px]'"
            >Lab</span>
          </span>
          <LogoLegend :small="scrolled" />
        </span>
      </RouterLink>

      <!-- Desktop nav (orden del diseño Dualab Web_HOME): DuaLab, Centros Educativos,
           Empresas, Alumnado, Entidades, Contacto; luego los dos botones destacados
           (Conéctate / Solicita tu demo). Desde xl: logo + 6 enlaces + 2 botones no caben en lg
           (a 1024–1279 px desbordaba la pantalla). -->
      <div class="hidden xl:flex items-center gap-3.5">
        <template v-for="(link, i) in navLinks" :key="link.name">
          <RouterLink
            v-if="link.to"
            class="nav-link relative text-sm text-gray-600 hover:text-primary-700 transition-colors py-1 whitespace-nowrap"
            :style="{ animationDelay: `${180 + i * 100}ms` }"
            :to="link.to"
            exact-active-class="nav-link-active"
          >
            {{ link.label }}
            <span class="nav-underline absolute -bottom-0.5 left-0 right-0 h-[3px] rounded-full bg-azul-noche scale-x-0 transition-transform duration-200 origin-left" />
          </RouterLink>
          <!-- Enlace pendiente de su vista: visible pero sin navegación -->
          <span
            v-else-if="link.pending"
            class="nav-link text-sm text-gray-400 py-1 whitespace-nowrap cursor-default"
            :style="{ animationDelay: `${180 + i * 100}ms` }"
            aria-disabled="true"
            title="Próximamente"
          >{{ link.label }}</span>
          <button
            v-else
            class="nav-link text-sm text-gray-600 hover:text-primary-700 transition-colors whitespace-nowrap"
            :style="{ animationDelay: `${180 + i * 100}ms` }"
            @click="link.action"
          >{{ link.label }}</button>
        </template>

        <SelectorIdioma id="selector-idioma-escritorio" />

        <!-- Botones del diseño: Conéctate (login de la herramienta, otra SPA →
             enlace externo, nunca RouterLink) y Solicita tu demo (/contacto). -->
        <a
          :href="`${toolUrl}/login`"
          class="nav-link inline-flex items-center gap-2 px-4 py-2.5 bg-white text-primary-700 border-2 border-primary-600 rounded-full font-black text-xs uppercase tracking-wider hover:bg-primary-600 hover:text-white transition-all duration-300 hover:-translate-y-0.5 active:scale-95 whitespace-nowrap"
          :style="{ animationDelay: `${180 + (navLinks.length + 1) * 100}ms` }"
        >Conéctate</a>
        <RouterLink
          :to="{ name: 'contacto' }"
          class="nav-link inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 text-white rounded-full font-black text-xs uppercase tracking-widest shadow-[0_4px_14px_rgba(48,114,170,0.3)] hover:shadow-[0_8px_20px_rgba(48,114,170,0.4)] hover:bg-primary-700 transition-all duration-300 hover:-translate-y-0.5 active:scale-95 whitespace-nowrap"
          :style="{ animationDelay: `${180 + (navLinks.length + 2) * 100}ms` }"
        >Solicita tu demo</RouterLink>
      </div>

      <!-- Hamburger (mobile) -->
      <button
        class="xl:hidden flex flex-col items-center justify-center w-9 h-9 gap-[5px] rounded-lg hover:bg-gray-100 transition-colors"
        :aria-label="menuOpen ? 'Cerrar menú' : 'Abrir menú'"
        @click="menuOpen = !menuOpen"
      >
        <span
          class="block w-5 h-0.5 bg-azul-noche transition-all duration-300 origin-center"
          :class="menuOpen ? 'rotate-45 translate-y-[7px]' : ''"
        />
        <span
          class="block w-5 h-0.5 bg-azul-noche transition-all duration-300"
          :class="menuOpen ? 'opacity-0 scale-x-0' : ''"
        />
        <span
          class="block w-5 h-0.5 bg-azul-noche transition-all duration-300 origin-center"
          :class="menuOpen ? '-rotate-45 -translate-y-[7px]' : ''"
        />
      </button>
    </div>

    <!-- Mobile menu panel -->
    <Transition name="mobile-menu">
      <div v-if="menuOpen" class="xl:hidden border-t border-gray-100 bg-white shadow-lg">
        <div class="max-w-6xl mx-auto px-6 py-4 flex flex-col">
          <template v-for="link in navLinks" :key="link.name">
            <RouterLink
              v-if="link.to"
              class="text-sm font-semibold text-gray-700 hover:text-primary-700 py-3.5 border-b border-gray-100 transition-colors"
              :to="link.to"
              active-class="text-primary-700"
              @click="menuOpen = false"
            >{{ link.label }}</RouterLink>
            <span
              v-else-if="link.pending"
              class="text-sm font-semibold text-gray-400 py-3.5 border-b border-gray-100"
              aria-disabled="true"
            >{{ link.label }} <span class="text-xs font-normal">(próximamente)</span></span>
            <button
              v-else
              class="text-left text-sm font-semibold text-gray-700 hover:text-primary-700 py-3.5 border-b border-gray-100 transition-colors"
              @click="link.action(); menuOpen = false"
            >{{ link.label }}</button>
          </template>

          <div class="pt-4 space-y-3">
            <SelectorIdioma id="selector-idioma-movil" />
            <a
              :href="`${toolUrl}/login`"
              class="flex items-center justify-center gap-2 w-full py-3.5 bg-white text-primary-700 border-2 border-primary-600 rounded-full font-black text-sm uppercase tracking-widest active:scale-95 transition-all duration-300"
              @click="menuOpen = false"
            >Conéctate</a>
            <RouterLink
              :to="{ name: 'contacto' }"
              class="flex items-center justify-center gap-2 w-full py-3.5 bg-primary-600 text-white rounded-full font-black text-sm uppercase tracking-widest shadow-[0_4px_14px_rgba(48,114,170,0.3)] active:scale-95 transition-all duration-300"
              @click="menuOpen = false"
            >Solicita tu demo</RouterLink>
          </div>
        </div>
      </div>
    </Transition>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import LogoLegend from '@/components/common/LogoLegend.vue'
import SelectorIdioma from '@/components/common/SelectorIdioma.vue'
import logoOriginal from '@/assets/logo_colores.png'
import { useConfiguracionWeb } from '@/composables/useConfiguracionWeb'

// Logo: el de Apariencia en el panel o el original
const { apariencia } = useConfiguracionWeb()

const scrolled  = ref(false)
const menuOpen  = ref(false)

// La herramienta DuaLab (login real, docente/empresa/admin) — otra SPA, otro
// dominio en producción (dualab.es) u otro puerto en local (localhost:5173).
const toolUrl = import.meta.env.VITE_TOOL_URL ?? (import.meta.env.DEV ? 'http://localhost:5173' : 'https://dualab.es')

function onScroll() {
  scrolled.value = window.scrollY > 12
}

onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', onScroll))

// pending: enlaces pendientes de su nueva vista (se muestran sin navegar).
const navLinks = [
  { label: 'DuaLab',             name: 'home',      to: { name: 'home' } },
  { label: 'Centros Educativos', name: 'centros',   to: { name: 'centros'  } },
  { label: 'Empresas',           name: 'empresas',  to: { name: 'empresas' } },
  { label: 'Alumnado',           name: 'alumnos',   to: { name: 'alumnos'  } },
  { label: 'Entidades',          name: 'entidades', to: { name: 'entidades' } },
  { label: 'Contacto',           name: 'contacto',  to: { name: 'contacto' } },
]
</script>

<style scoped>
@keyframes fadeSlideDown {
  from { opacity: 0; transform: translateY(-10px); }
  to   { opacity: 1; transform: translateY(0); }
}

.logo-animate {
  animation: fadeSlideDown 0.9s cubic-bezier(0.16, 1, 0.3, 1) both;
  animation-delay: 60ms;
}

.nav-link {
  animation: fadeSlideDown 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
}

/* Enlace de la página actual: texto oscuro y subrayado azul noche (diseño) */
.nav-link-active {
  color: #17283E;
  font-weight: 600;
}
.nav-link-active .nav-underline,
.nav-link:hover .nav-underline {
  transform: scaleX(1);
}

/* Mobile menu slide-down */
.mobile-menu-enter-active,
.mobile-menu-leave-active {
  transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.mobile-menu-enter-from,
.mobile-menu-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
