<!-- Menú lateral del panel de gestión web: mismo lenguaje que el SidePanel de la app
     DuaLab (azul noche, entradas .nav-item, activo en turquesa del rol admin) y la
     estructura de WordPress (secciones con submenú desplegado en la sección activa). -->
<template>
  <Transition name="gw-fade">
    <div v-if="abierto" class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="$emit('cerrar')" />
  </Transition>

  <aside
    id="gw-menu"
    class="fixed bottom-0 left-0 top-16 z-40 flex w-72 max-w-[85vw] flex-col border-r border-azul-noche-border bg-azul-noche
           shadow-[6px_0_32px_rgba(0,0,0,0.25)] transition-transform duration-300 lg:translate-x-0"
    :class="abierto ? 'translate-x-0' : '-translate-x-full'"
    aria-label="Menú del panel"
  >
    <nav class="flex min-h-0 flex-1 flex-col overflow-y-auto overscroll-contain px-3 py-3">
      <template v-for="grupo in grupos" :key="grupo.titulo ?? 'inicio'">
        <p v-if="grupo.titulo" class="nav-grupo">{{ grupo.titulo }}</p>
        <ul class="space-y-0.5">
          <li v-for="item in grupo.items" :key="item.label">
            <RouterLink :to="item.to" class="nav-item" :class="activo(item) ? 'nav-item--activo' : 'nav-item--reposo'"
                        :aria-current="activo(item) ? 'page' : undefined">
              <component :is="item.icono" class="h-5 w-5 shrink-0" aria-hidden="true" />
              <span class="min-w-0 flex-1">
                <span class="block truncate">{{ item.label }}</span>
                <span v-if="item.equivalente" class="block truncate text-xs font-medium opacity-60">antes: {{ item.equivalente }}</span>
              </span>
              <span v-if="item.badge === 'formularios' && nuevos" class="nav-badge" :aria-label="`${nuevos} formularios nuevos`">
                {{ nuevos > 99 ? '99+' : nuevos }}
              </span>
            </RouterLink>
            <!-- Submenú (como en WordPress): visible cuando la sección está activa -->
            <ul v-if="item.hijos && activo(item)" class="mb-1 ml-9 mt-0.5 space-y-0.5 border-l border-white/10 pl-2">
              <li v-for="hijo in item.hijos.filter(h => sesion.puede(h.roles))" :key="hijo.label">
                <RouterLink :to="hijo.to" class="nav-subitem" :class="hijo.rutas.includes(route.name) ? 'text-white' : 'text-white/60 hover:text-white'"
                            :aria-current="hijo.rutas.includes(route.name) ? 'page' : undefined">
                  {{ hijo.label }}
                </RouterLink>
              </li>
            </ul>
          </li>
        </ul>
      </template>

      <!-- Banner de marca al fondo, como en la app -->
      <div class="mt-auto pt-4 [@media(max-height:640px)]:hidden" aria-hidden="true">
        <div class="relative h-24 overflow-hidden rounded-2xl border border-white/15 bg-gradient-to-br from-[#2E5F8F] via-[#24486E] to-[#1B3553] px-4 py-3 shadow-[0_8px_24px_rgba(0,0,0,0.25)]">
          <svg class="absolute inset-x-0 bottom-0 h-12 w-full" viewBox="0 0 240 48" preserveAspectRatio="none">
            <path d="M0 30 C60 14 120 46 240 22 V48 H0Z" fill="#FF8920" opacity="0.85" />
            <path d="M0 38 C70 26 140 50 240 34 V48 H0Z" fill="#3FC7C8" opacity="0.9" />
            <path d="M0 44 C80 38 160 52 240 44 V48 H0Z" fill="#ffffff" opacity="0.35" />
          </svg>
          <img :src="logo" alt="" class="absolute right-3 top-1/2 h-16 w-auto -translate-y-1/2 object-contain drop-shadow-[0_4px_10px_rgba(0,0,0,0.35)]" />
          <p class="relative text-base font-bold tracking-wide text-white">DUALAB</p>
          <p class="relative mt-1 max-w-[8.5rem] text-xs font-semibold leading-snug text-white/85">Gestión de la web</p>
        </div>
      </div>
    </nav>
  </aside>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import logo from '@/assets/logo_colores.png'
import { GRUPOS_PANEL } from '../navegacion'
import { usePanelSesion } from '@/stores/panelSesion'
import { formulariosService } from '@/services/admin/formulariosService'

defineProps({ abierto: { type: Boolean, default: false } })
defineEmits(['cerrar'])

const route = useRoute()
const sesion = usePanelSesion()

const grupos = computed(() => GRUPOS_PANEL
  .map(g => ({ ...g, items: g.items.filter(i => sesion.puede(i.roles)) }))
  .filter(g => g.items.length))

const activo = item => (item.exacta ? route.path === '/gestion-web' || route.path === '/gestion-web/' : route.path.startsWith(item.prefijo))

// Contador de formularios nuevos: al navegar, como mucho cada 30 s
const nuevos = ref(0)
let ultima = 0
async function refrescarNuevos() {
  if (Date.now() - ultima < 30000) return
  ultima = Date.now()
  try {
    const r = await formulariosService.listar({ estado: 'nuevo', per_page: 1 })
    nuevos.value = r.meta.total
  } catch { /* el contador no es crítico */ }
}
watch(() => route.fullPath, refrescarNuevos, { immediate: true })
</script>

<style scoped>
.gw-fade-enter-active, .gw-fade-leave-active { transition: opacity 0.2s ease; }
.gw-fade-enter-from, .gw-fade-leave-to { opacity: 0; }

.nav-grupo {
  margin: 1rem 0.75rem 0.375rem;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.45);
}
.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 2.5rem;
  padding: 8px 12px;
  border-radius: 1rem;
  font-size: 0.875rem;
  font-weight: 700;
  transition: background-color 150ms ease, color 150ms ease;
}
.nav-item--reposo { color: rgba(255, 255, 255, 0.72); }
.nav-item--reposo:hover { background-color: rgba(255, 255, 255, 0.07); color: rgba(255, 255, 255, 0.92); }
/* Activo en turquesa aclarado (rol admin), como .nav-item--active-admin de la app */
.nav-item--activo {
  background: rgba(63, 199, 200, 0.18);
  color: #3FC7C8;
  box-shadow: inset 3px 0 0 #3FC7C8;
}
.nav-subitem {
  display: flex;
  align-items: center;
  min-height: 2.25rem; /* objetivo táctil de 36px */
  padding: 6px 10px;
  border-radius: 0.75rem;
  font-size: 0.8125rem;
  font-weight: 600;
  transition: color 150ms ease;
}
.nav-badge {
  min-width: 1.375rem;
  padding: 1px 7px;
  border-radius: 999px;
  background: #FF8920;
  color: #17283E;
  font-size: 0.75rem;
  font-weight: 800;
  text-align: center;
}
</style>
