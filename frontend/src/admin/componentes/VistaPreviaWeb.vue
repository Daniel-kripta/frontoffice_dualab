<!-- La web real dentro de un iframe (mismo dominio) con la apariencia sin guardar.
     Los cambios llegan por postMessage (ver useApariencia → escucharVistaPrevia).
     El iframe se dibuja al ancho del dispositivo elegido y se escala: «ajustada» para que
     quepa entera, o con la lupa al 100 % / 150 % y desplazamiento dentro del recuadro. -->
<template>
  <div class="gw-tarjeta flex flex-col overflow-hidden">
    <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 p-2 sm:p-3">
      <label :for="`${id}-pagina`" class="sr-only">Página que se previsualiza</label>
      <select :id="`${id}-pagina`" v-model="ruta" class="gw-campo !w-auto min-w-0 flex-1 !py-2 sm:flex-none">
        <option v-for="p in PAGINAS" :key="p.ruta" :value="p.ruta">{{ p.nombre }}</option>
      </select>

      <div class="gw-carril" role="radiogroup" aria-label="Dispositivo">
        <button v-for="(d, clave) in DISPOSITIVOS" :key="clave" type="button" role="radio" class="gw-pestana !h-9 !px-3"
                :class="{ 'gw-pestana--activa': dispositivo === clave }" :aria-checked="dispositivo === clave"
                :title="`${d.nombre} (${d.ancho} px)`" @click="dispositivo = clave">
          <component :is="d.icono" class="h-4 w-4" aria-hidden="true" />
          <span class="sr-only">{{ d.nombre }}</span>
        </button>
      </div>

      <!-- Lupa -->
      <div class="flex items-center gap-1" role="group" aria-label="Zoom de la vista previa">
        <button type="button" class="gw-accion-icono !h-9 !w-9" :disabled="nivel === 0" :class="{ 'opacity-40': nivel === 0 }"
                title="Alejar" aria-label="Alejar" @click="nivel--">
          <MagnifyingGlassMinusIcon class="h-4 w-4" aria-hidden="true" />
        </button>
        <span class="min-w-[4.5rem] text-center text-xs font-semibold text-azul-noche" aria-live="polite">{{ textoZoom }}</span>
        <button type="button" class="gw-accion-icono !h-9 !w-9" :disabled="nivel === ZOOMS.length - 1" :class="{ 'opacity-40': nivel === ZOOMS.length - 1 }"
                title="Acercar" aria-label="Acercar" @click="nivel++">
          <MagnifyingGlassPlusIcon class="h-4 w-4" aria-hidden="true" />
        </button>
      </div>

      <button v-if="!grande" type="button" class="gw-accion-secundaria !h-9 !px-4 sm:ml-auto" @click="$emit('detalle')">
        <ArrowsPointingOutIcon class="h-4 w-4" aria-hidden="true" /> Ver en detalle
      </button>
    </div>

    <div ref="marco" class="relative bg-gray-100" :class="[alturaMarco, nivel === 0 ? 'overflow-hidden' : 'overflow-auto']">
      <div class="relative mx-auto" :style="{ width: `${ancho * escala}px`, height: `${alto}px` }">
        <iframe
          ref="iframe"
          :key="ruta"
          :src="`${ruta}${ruta.includes('?') ? '&' : '?'}vista_previa=1`"
          title="Vista previa de la web"
          class="absolute left-0 top-0 origin-top-left border-0 bg-white shadow-lg"
          :style="{ width: `${ancho}px`, height: `${alto / escala}px`, transform: `scale(${escala})` }"
          @load="enviar"
        />
      </div>
    </div>
    <p v-if="!grande" class="border-t border-gray-100 px-3 py-2 text-xs text-texto-secundario">
      En directo: puedes navegar por la web dentro del recuadro. Nada se publica hasta que apliques los cambios.
    </p>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import {
  ArrowsPointingOutIcon, ComputerDesktopIcon, DevicePhoneMobileIcon, DeviceTabletIcon,
  MagnifyingGlassMinusIcon, MagnifyingGlassPlusIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  apariencia: { type: Object, required: true },
  grande: { type: Boolean, default: false }, // dentro de «Ver en detalle» (pantalla casi completa)
})
defineEmits(['detalle'])
const id = `gw-previa-${Math.random().toString(36).slice(2, 8)}`

const PAGINAS = [
  { ruta: '/', nombre: 'Inicio' }, { ruta: '/centros', nombre: 'Centros' }, { ruta: '/empresas-asociadas', nombre: 'Empresas' },
  { ruta: '/para-alumnado', nombre: 'Alumnado' }, { ruta: '/entidades', nombre: 'Entidades' }, { ruta: '/familias', nombre: 'Familias' },
  { ruta: '/noticias', nombre: 'Noticias' }, { ruta: '/contacto', nombre: 'Contacto' },
]
const DISPOSITIVOS = {
  movil: { nombre: 'Móvil', ancho: 390, icono: DevicePhoneMobileIcon },
  tablet: { nombre: 'Tablet', ancho: 820, icono: DeviceTabletIcon },
  escritorio: { nombre: 'Escritorio', ancho: 1280, icono: ComputerDesktopIcon },
}
// 0 = ajustada al recuadro; el resto, tamaño real × factor
const ZOOMS = ['ajustar', 1, 1.5]

const ruta = ref('/')
// En pantallas estrechas empieza en móvil: el escritorio escalado a 360px no se lee
const dispositivo = ref(window.matchMedia('(min-width: 1024px)').matches ? 'escritorio' : 'movil')
const nivel = ref(0)
const marco = ref(null)
const iframe = ref(null)
const anchoMarco = ref(800)
const alto = ref(600)

const ancho = computed(() => DISPOSITIVOS[dispositivo.value].ancho)
const escalaAjustada = computed(() => Math.min(1, (anchoMarco.value - 16) / ancho.value))
const escala = computed(() => (ZOOMS[nivel.value] === 'ajustar' ? escalaAjustada.value : ZOOMS[nivel.value]))
const textoZoom = computed(() => (nivel.value === 0 ? `Ajustada ${Math.round(escalaAjustada.value * 100)} %` : `${Math.round(escala.value * 100)} %`))
const alturaMarco = computed(() => (props.grande
  ? 'h-[calc(92dvh-13rem)] min-h-[20rem]'
  : 'h-[60vh] min-h-[22rem] xl:h-[calc(100dvh-16rem)]'))

const enviarA = msg => iframe.value?.contentWindow?.postMessage(msg, window.location.origin)

function enviar() {
  enviarA({ tipo: 'dualab-apariencia', apariencia: JSON.parse(JSON.stringify(props.apariencia)) })
}

// La web avisa cuando está lista para recibir la apariencia
function alMensaje(e) {
  if (e.origin === window.location.origin && e.data?.tipo === 'dualab-vista-lista') enviar()
}

let observador
onMounted(() => {
  window.addEventListener('message', alMensaje)
  observador = new ResizeObserver(([e]) => {
    anchoMarco.value = e.contentRect.width
    alto.value = e.contentRect.height
  })
  observador.observe(marco.value)
})
onBeforeUnmount(() => {
  window.removeEventListener('message', alMensaje)
  observador?.disconnect()
})

watch(() => props.apariencia, enviar, { deep: true })
</script>
