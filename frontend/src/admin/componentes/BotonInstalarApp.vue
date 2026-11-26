<!-- «Instalar app» al pie del menú lateral del panel, como en el resto de herramientas de DuaLab
     (app, comercial, Central): fila fija fuera de la zona con scroll. Diálogo nativo en Chrome/Edge;
     en Safari y Firefox, los pasos de su navegador (useInstalarApp). No se pinta si ya está instalada. -->
<template>
  <button v-if="puedeInstalar" type="button" title="Instalar Gestión web como app" @click="abrir"
          class="flex min-h-11 w-full items-center gap-2.5 rounded-2xl border border-white/15 bg-white/5 px-3 text-left text-sm font-bold text-white/80 transition-colors hover:bg-white/10 hover:text-white">
    <ArrowDownTrayIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
    <span class="min-w-0 flex-1 truncate">Instalar app</span>
  </button>

  <VentanaModal :abierto="instrucciones" titulo="Instalar Gestión web como app" :subtitulo="guia.subtitulo" @cerrar="instrucciones = false">
    <p class="text-sm leading-relaxed text-azul-noche">{{ guia.intro }}</p>
    <ol v-if="guia.pasos" class="mt-4 space-y-2">
      <li v-for="(paso, i) in guia.pasos" :key="i" class="flex gap-3 text-sm text-azul-noche">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-administraciones-dark text-xs font-bold text-white">{{ i + 1 }}</span>
        <span class="min-w-0 pt-0.5">{{ paso }}</span>
      </li>
    </ol>
    <p v-if="guia.nota" class="mt-4 rounded-xl bg-gray-50 px-3 py-2 text-xs leading-relaxed text-texto-secundario">{{ guia.nota }}</p>
    <template #pie>
      <button type="button" class="gw-accion-principal" @click="instrucciones = false">Entendido</button>
    </template>
  </VentanaModal>
</template>

<script setup>
import { computed, ref } from 'vue'
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline'
import VentanaModal from './VentanaModal.vue'
import { useInstalarApp } from '../composables/useInstalarApp'

const { puedeInstalar, instalar, plataforma } = useInstalarApp()
const instrucciones = ref(false)
async function abrir() {
  if (!(await instalar())) instrucciones.value = true
}

// Mismos pasos que InstalarAppModal de la app DuaLab, sin las ilustraciones
const NOTA_SESION = 'La app guarda su propia sesión: la primera vez que la abras tendrás que iniciar sesión.'
const GUIAS = {
  ios: {
    subtitulo: 'Cómo hacerlo en iPhone o iPad',
    intro: 'En iPhone y iPad las apps web se añaden desde Safari en tres toques. No se descarga ningún archivo: el icono de Gestión web aparece en tu pantalla de inicio.',
    pasos: ['Pulsa Compartir en la barra de Safari (si no lo ves, pulsa antes ···).', 'Elige «Añadir a pantalla de inicio».', 'Pulsa «Añadir».'],
    nota: NOTA_SESION,
  },
  'safari-mac': {
    subtitulo: 'Cómo hacerlo en Safari en Mac',
    intro: 'Safari añade las apps web desde su propio menú, en dos clics: Gestión web aparece en el Dock y se abre en su propia ventana.',
    pasos: ['En la barra de menús de arriba, abre «Archivo».', 'Elige «Añadir al Dock…».', 'Pulsa «Añadir».'],
    nota: `${NOTA_SESION} Necesitas macOS Sonoma (14) o posterior.`,
  },
  'firefox-android': {
    subtitulo: 'Cómo hacerlo en Firefox en Android',
    intro: 'Firefox instala Gestión web desde su menú en tres toques: queda como una app más, con su icono.',
    pasos: ['Pulsa el menú ⋮ de Firefox.', 'Elige «Instalar» (en algunas versiones, «Añadir a pantalla de inicio»).', 'Pulsa «Instalar».'],
  },
  chrome: {
    subtitulo: 'Cómo hacerlo en Chrome o Edge',
    intro: 'Tu navegador instala Gestión web en dos clics: queda en tus aplicaciones y se abre en su propia ventana.',
    pasos: ['Pulsa el icono de instalar, a la derecha de la barra de direcciones (si no está, búscalo en el menú ⋮ como «Instalar Gestión web»).', 'Confirma con «Instalar».'],
    nota: 'Si no ves la opción, navega un poco por el panel y vuelve a intentarlo: el navegador tarda unos segundos en ofrecerla.',
  },
  'chrome-android': {
    subtitulo: 'Cómo hacerlo en Android',
    intro: 'Tu navegador instala Gestión web desde su menú en tres toques: queda como una app más, con su icono.',
    pasos: ['Pulsa el menú ⋮ del navegador, arriba a la derecha.', 'Elige «Añadir a pantalla de inicio» (en algunas versiones, «Instalar app»).', 'Pulsa «Instalar».'],
    nota: 'Si no ves la opción, navega un poco por el panel y vuelve a intentarlo: el navegador tarda unos segundos en ofrecerla.',
  },
  firefox: {
    subtitulo: 'Firefox para ordenador no permite instalar apps',
    intro: 'Firefox en ordenador no deja instalar apps web. Puedes seguir usando el panel aquí (truco: clic derecho en la pestaña → «Fijar pestaña»), instalarlo abriéndolo en Chrome, Edge o Safari, o instalarlo en el móvil.',
  },
}
const guia = computed(() => GUIAS[plataforma] ?? GUIAS.chrome)
</script>
