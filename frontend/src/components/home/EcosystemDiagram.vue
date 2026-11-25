<template>
  <div ref="wrapper" class="relative">
    <!-- Órbitas decorativas (solo desktop): dos anillos punteados alrededor del logo.
         El anillo exterior se calcula a partir de la posición real de los iconos para
         que los cuatro queden siempre encima, sea cual sea el ancho disponible. -->
    <div v-if="orbit" class="hidden md:block absolute inset-0 pointer-events-none" aria-hidden="true">
      <div
        v-for="r in [orbit.r, orbit.r * INNER_RATIO]"
        :key="r"
        class="absolute rounded-full border border-dashed border-primary-200 -translate-x-1/2 -translate-y-1/2"
        :style="{ left: `${orbit.cx}px`, top: `${orbit.cy}px`, width: `${r * 2}px`, height: `${r * 2}px` }"
      />
      <span
        v-for="(d, i) in dots"
        :key="i"
        class="absolute w-2 h-2 rounded-full bg-primary-200 -translate-x-1/2 -translate-y-1/2"
        :style="dotStyle(d)"
      />
    </div>

    <!-- Columnas laterales iguales (minmax(0,1fr)) para que el logo quede centrado
         y los iconos sean simétricos aunque los textos tengan distinta longitud. -->
    <div class="relative grid grid-cols-2 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] md:grid-rows-2 gap-x-6 gap-y-10 md:gap-y-24 items-center">
      <!-- Centro: logo DuaLab -->
      <div class="col-span-2 md:col-span-1 md:col-start-2 md:row-start-1 md:row-span-2 flex flex-col items-center justify-center md:px-3">
        <img src="@/assets/logo_colores.png" alt="" class="w-24 md:w-28 object-contain" />
        <span class="font-heading font-extrabold tracking-tight text-azul-noche text-2xl md:text-3xl leading-none mt-1">DuaLab</span>
      </div>

      <div
        v-for="a in agentesVista"
        :key="a.key"
        class="min-w-0 flex flex-col items-center text-center gap-3 md:gap-4"
        :class="[a.position, a.side === 'left' ? 'md:flex-row-reverse md:text-right' : 'md:flex-row md:text-left']"
      >
        <div
          ref="icons"
          class="w-16 h-16 md:w-20 md:h-20 rounded-full flex items-center justify-center text-white shrink-0 shadow-[0_8px_20px_rgba(23,40,62,0.15)] ring-4 ring-white"
          :class="a.bg"
        >
          <component :is="a.icon" class="w-8 h-8 md:w-10 md:h-10" />
        </div>
        <div class="min-w-0 max-w-[190px] break-words">
          <h3 class="font-heading font-extrabold text-xs md:text-sm uppercase tracking-wide" :class="a.text">{{ a.label }}</h3>
          <p class="text-xs md:text-sm text-gray-500 leading-snug mt-1">{{ a.desc }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import {
  AcademicCapIcon,
  BuildingOffice2Icon,
  UserGroupIcon,
  BuildingLibraryIcon,
} from '@heroicons/vue/24/outline'

// Clases literales (no construidas a partir de `key`) para que Tailwind las detecte.
// En md+ el icono queda centrado en vertical respecto al texto (md:items-center)
// para que los cuatro estén a la misma altura relativa del logo.
const agents = [
  {
    key: 'centros', label: 'Centros Educativos', side: 'left',
    desc: 'Gestionan, coordinan y potencian la FP Dual',
    icon: AcademicCapIcon, bg: 'bg-centros', text: 'text-centros',
    position: 'md:col-start-1 md:row-start-1 md:items-center',
  },
  {
    key: 'empresas', label: 'Empresas', side: 'right',
    desc: 'Apoyan el talento y colaboran en proyectos reales',
    icon: BuildingOffice2Icon, bg: 'bg-empresas', text: 'text-empresas',
    position: 'md:col-start-3 md:row-start-1 md:items-center',
  },
  {
    key: 'alumnado', label: 'Alumnado', side: 'left',
    desc: 'Desarrolla su talento en entornos profesionales',
    icon: UserGroupIcon, bg: 'bg-alumnos', text: 'text-alumnos',
    position: 'md:col-start-1 md:row-start-2 md:items-center',
  },
  {
    key: 'administraciones', label: 'Administraciones', side: 'right',
    desc: 'Impulsan y hacen crecer la Formación Dual',
    icon: BuildingLibraryIcon, bg: 'bg-administraciones', text: 'text-administraciones',
    position: 'md:col-start-3 md:row-start-2 md:items-center',
  },
]

// textos: [{ titulo, texto }] en el orden de `agents`; los pasa la vista (editables desde
// el panel de gestión web). Si falta alguno se usa el texto original.
const props = defineProps({ textos: { type: Array, default: () => [] } })
const agentesVista = computed(() => agents.map((a, i) => ({
  ...a,
  label: props.textos[i]?.titulo || a.label,
  desc: props.textos[i]?.texto || a.desc,
})))

// Proporción anillo interior / exterior (antes 260px / 400px).
const INNER_RATIO = 0.65

// Puntos decorativos: anillo (exterior/interior) y ángulo en grados (0° = derecha, 90° = abajo).
const dots = [
  { ring: 'outer', angle: -90 },
  { ring: 'inner', angle: 215 },
  { ring: 'inner', angle: 35 },
  { ring: 'outer', angle: 95 },
]

const wrapper = ref(null)
const icons = ref([])
const orbit = ref(null)

// Centro = media de los centros de los cuatro iconos; radio = distancia media a ese centro.
// Se mide relativo al wrapper, así el translateY del scroll-reveal no afecta.
function measure() {
  if (!wrapper.value || icons.value.length !== agents.length) return
  const box = wrapper.value.getBoundingClientRect()
  const centers = icons.value.map((el) => {
    const r = el.getBoundingClientRect()
    return { x: r.left - box.left + r.width / 2, y: r.top - box.top + r.height / 2 }
  })
  const cx = centers.reduce((s, c) => s + c.x, 0) / centers.length
  const cy = centers.reduce((s, c) => s + c.y, 0) / centers.length
  const r = centers.reduce((s, c) => s + Math.hypot(c.x - cx, c.y - cy), 0) / centers.length
  orbit.value = { cx, cy, r }
}

function dotStyle({ ring, angle }) {
  const { cx, cy, r } = orbit.value
  const radius = ring === 'outer' ? r : r * INNER_RATIO
  const rad = (angle * Math.PI) / 180
  return { left: `${cx + radius * Math.cos(rad)}px`, top: `${cy + radius * Math.sin(rad)}px` }
}

let observer
onMounted(() => {
  measure()
  observer = new ResizeObserver(measure)
  observer.observe(wrapper.value)
  // Las fuentes web cambian el ancho del texto al cargar
  document.fonts?.ready.then(measure)
})
onBeforeUnmount(() => observer?.disconnect())
</script>
