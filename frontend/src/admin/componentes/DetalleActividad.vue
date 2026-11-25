<!-- Detalle de una acción del historial: quién, cuándo, qué hizo y qué cambió (antes → después).
     Lo usan Equipo → Historial y el historial de cada página o entrada. Las acciones anteriores
     al 20-nov-2026 no guardaron el detalle: solo se ve la descripción. -->
<template>
  <VentanaModal :abierto="!!accion" titulo="Detalle del historial" :subtitulo="accion ? fechaHora(accion.fecha) : ''" ancho="lg" @cerrar="emit('cerrar')">
    <div v-if="accion" class="space-y-5">
      <div class="flex items-start gap-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-azul-noche text-xs font-bold text-white" aria-hidden="true">{{ inicial }}</span>
        <div class="min-w-0">
          <p class="break-words text-sm text-azul-noche"><strong>{{ accion.usuario.nombre }}</strong> {{ accion.descripcion }}</p>
          <p class="mt-1 flex flex-wrap items-center gap-2 text-xs text-texto-secundario">
            <span class="gw-chip bg-gray-100 text-texto-secundario">{{ accion.seccion_nombre }}</span>
            <RouterLink v-if="enlace" :to="enlace" class="gw-enlace" @click="emit('cerrar')">Abrir {{ nombreObjeto }}</RouterLink>
          </p>
        </div>
      </div>

      <section v-if="accion.detalle?.length">
        <h3 class="gw-etiqueta">Qué cambió ({{ accion.detalle.length }})</h3>
        <!-- Tabla desde tablet; en móvil, una tarjeta por cambio -->
        <div class="hidden overflow-x-auto rounded-xl border border-gray-200 md:block">
          <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-texto-secundario">
              <tr><th class="px-4 py-2 font-semibold">Campo</th><th class="px-4 py-2 font-semibold">Antes</th><th class="px-4 py-2 font-semibold">Después</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(c, i) in accion.detalle" :key="i" class="align-top">
                <td class="w-1/4 px-4 py-3 font-semibold text-azul-noche">{{ c.campo }}</td>
                <td class="px-4 py-3 break-words text-gray-500"><span :class="{ 'line-through decoration-red-300': c.antes }">{{ c.antes ?? '—' }}</span></td>
                <td class="px-4 py-3 break-words text-azul-noche">{{ c.despues ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <ul class="space-y-2 md:hidden">
          <li v-for="(c, i) in accion.detalle" :key="i" class="rounded-xl border border-gray-200 p-3 text-sm">
            <p class="font-semibold text-azul-noche">{{ c.campo }}</p>
            <p class="mt-1 break-words text-gray-500"><span class="text-xs font-semibold uppercase">Antes:</span> {{ c.antes ?? '—' }}</p>
            <p class="mt-1 break-words text-azul-noche"><span class="text-xs font-semibold uppercase text-texto-secundario">Después:</span> {{ c.despues ?? '—' }}</p>
          </li>
        </ul>
      </section>
      <p v-else class="rounded-xl bg-gray-50 p-4 text-sm text-texto-secundario">
        No hay más detalle de esta acción{{ antiguo ? ': se registró antes de que el historial guardara qué cambiaba' : '' }}.
      </p>
    </div>
  </VentanaModal>
</template>

<script setup>
import { computed } from 'vue'
import VentanaModal from './VentanaModal.vue'
import { fechaHora } from '../utilidades'

const props = defineProps({ accion: { type: Object, default: null } })
const emit = defineEmits(['cerrar'])

// Desde este día cada acción guarda qué cambió (migración 2026_11_20_000001)
const INICIO_DETALLE = new Date('2026-11-20T00:00:00')

const inicial = computed(() => (props.accion?.usuario.nombre || '?').trim().charAt(0).toUpperCase())
const antiguo = computed(() => props.accion && new Date(props.accion.fecha) < INICIO_DETALLE)

// Enlace al elemento afectado, si sigue existiendo un sitio donde verlo
const DESTINOS = {
  pagina: { nombre: 'la página', ruta: ref => ({ name: 'panel-pagina', params: { clave: ref } }) },
  entrada: { nombre: 'la entrada', ruta: ref => ({ name: 'panel-entrada', params: { id: ref } }) },
  medio: { nombre: 'Medios', ruta: () => ({ name: 'panel-medios' }) },
  usuario: { nombre: 'Usuarios', ruta: () => ({ name: 'panel-usuarios' }) },
}
const destino = computed(() => DESTINOS[props.accion?.objeto?.tipo])
const enlace = computed(() => destino.value?.ruta(props.accion.objeto.ref) ?? null)
const nombreObjeto = computed(() => destino.value?.nombre ?? '')
</script>
