<template>
  <header class="fixed inset-x-0 top-0 z-50 flex h-16 items-center gap-2 border-b border-azul-noche-border bg-azul-noche px-3 text-white sm:gap-3 sm:px-5">
    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full hover:bg-white/10 lg:hidden"
            :aria-expanded="menuAbierto" aria-controls="gw-menu" :aria-label="menuAbierto ? 'Cerrar menú' : 'Abrir menú'"
            @click="$emit('alternar-menu')">
      <XMarkIcon v-if="menuAbierto" class="h-6 w-6" aria-hidden="true" />
      <Bars3Icon v-else class="h-6 w-6" aria-hidden="true" />
    </button>

    <RouterLink :to="{ name: 'panel-escritorio' }" class="flex min-w-0 items-center gap-2.5">
      <img :src="logo" alt="" class="h-9 w-auto shrink-0" />
      <span class="min-w-0">
        <span class="block truncate font-heading text-sm font-bold leading-tight sm:text-base">DuaLab</span>
        <span class="block truncate text-xs font-semibold text-white/60">Gestión de la web</span>
      </span>
    </RouterLink>

    <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
      <a href="/" target="_blank" rel="noopener" class="inline-flex h-10 items-center gap-2 rounded-full border border-white/25 px-3 text-sm font-semibold hover:bg-white/10 sm:px-4"
         title="Abre la web pública en otra pestaña">
        <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" />
        <span class="hidden sm:inline">Ver web</span>
        <span class="sr-only sm:hidden">Ver web</span>
      </a>
      <!-- Mi perfil (en móvil, solo las iniciales) -->
      <RouterLink :to="{ name: 'panel-perfil' }" class="flex items-center gap-2 rounded-full py-0.5 pl-0.5 pr-0.5 hover:bg-white/10 md:pr-3"
                  title="Mi perfil" :aria-label="`Mi perfil: ${sesion.usuario?.name ?? ''}`">
        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#3FC7C8] text-sm font-bold text-azul-noche" aria-hidden="true">{{ iniciales }}</span>
        <span class="hidden max-w-[10rem] leading-tight md:block" aria-hidden="true">
          <span class="block truncate text-sm font-semibold">{{ sesion.usuario?.name }}</span>
          <span class="block text-xs text-white/60">{{ NOMBRES_ROLES[sesion.rol] ?? '' }}</span>
        </span>
      </RouterLink>
      <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full hover:bg-white/10"
              title="Cerrar sesión" aria-label="Cerrar sesión" @click="salir">
        <ArrowRightOnRectangleIcon class="h-5 w-5" aria-hidden="true" />
      </button>
    </div>
  </header>
</template>

<script setup>
import { NOMBRES_ROLES } from '../roles'
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { Bars3Icon, XMarkIcon, ArrowTopRightOnSquareIcon, ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline'
import logo from '@/assets/logo_colores.png'
import { usePanelSesion } from '@/stores/panelSesion'

defineProps({ menuAbierto: { type: Boolean, default: false } })
defineEmits(['alternar-menu'])

const sesion = usePanelSesion()
const router = useRouter()

const iniciales = computed(() => (sesion.usuario?.name ?? '?')
  .split(/\s+/).filter(Boolean).slice(0, 2).map(p => p[0].toUpperCase()).join(''))

async function salir() {
  await sesion.salir()
  router.push({ name: 'panel-acceso' })
}
</script>
