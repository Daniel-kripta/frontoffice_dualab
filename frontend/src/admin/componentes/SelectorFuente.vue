<!-- Desplegable de fuentes con buscador, agrupado por estilo y con cada nombre en su
     propia letra. Patrón combobox: flechas para moverse, Intro para elegir, Escape para cerrar. -->
<template>
  <div ref="raiz" class="relative">
    <label :for="`${id}-boton`" class="mb-1 block text-xs font-semibold text-texto-secundario">{{ etiqueta }}</label>
    <button :id="`${id}-boton`" type="button" class="gw-campo flex min-h-12 items-center gap-3 text-left !py-2"
            aria-haspopup="listbox" :aria-expanded="abierto" @click="abierto ? cerrar() : abrir()">
      <span class="min-w-0 flex-1">
        <span class="block truncate text-base leading-snug text-azul-noche" :style="{ fontFamily: actual?.css }">{{ actual?.nombre ?? 'Elige una fuente' }}</span>
        <span v-if="actual" class="mt-0.5 block truncate text-xs leading-tight text-texto-secundario">{{ GRUPOS_FUENTES[actual.grupo] }}</span>
      </span>
      <ChevronDownIcon class="h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="{ 'rotate-180': abierto }" aria-hidden="true" />
    </button>

    <div v-if="abierto" class="absolute left-0 right-0 z-30 mt-1 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
      <div class="border-b border-gray-100 p-2">
        <label :for="`${id}-buscar`" class="sr-only">Buscar fuente</label>
        <input :id="`${id}-buscar`" ref="buscador" v-model="busqueda" type="search" role="combobox" autocomplete="off"
               class="gw-campo !py-2" placeholder="Buscar fuente…" :aria-controls="`${id}-lista`" aria-expanded="true"
               :aria-activedescendant="activa >= 0 ? `${id}-op-${filtradas[activa]?.id}` : undefined" @keydown="alTeclear" />
      </div>
      <ul :id="`${id}-lista`" ref="lista" role="listbox" :aria-label="etiqueta" class="max-h-72 overflow-y-auto overscroll-contain py-1">
        <template v-for="g in grupos" :key="g.clave">
          <li role="presentation" class="sticky top-0 bg-white/95 px-3 pb-1 pt-2 text-xs font-bold uppercase tracking-wider text-texto-secundario backdrop-blur">
            {{ g.nombre }}
          </li>
          <li v-for="f in g.fuentes" :id="`${id}-op-${f.id}`" :key="f.id" role="option" :aria-selected="f.id === modelValue"
              class="flex min-h-10 cursor-pointer items-center gap-2 px-3 py-1.5"
              :class="filtradas[activa]?.id === f.id ? 'bg-administraciones-dark/10' : 'hover:bg-gray-50'"
              @click="elegir(f.id)" @mousemove="activa = filtradas.findIndex(x => x.id === f.id)">
            <span class="min-w-0 flex-1 truncate text-base text-azul-noche" :style="{ fontFamily: f.css }">{{ f.nombre }}</span>
            <CheckIcon v-if="f.id === modelValue" class="h-4 w-4 shrink-0 text-administraciones-dark" aria-hidden="true" />
          </li>
        </template>
        <li v-if="!filtradas.length" role="presentation" class="px-3 py-4 text-center text-sm text-texto-secundario">Ninguna fuente coincide.</li>
      </ul>
      <p class="border-t border-gray-100 px-3 py-1.5 text-xs text-texto-secundario">{{ filtradas.length }} fuentes</p>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref } from 'vue'
import { CheckIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'
import { GRUPOS_FUENTES, cargarMuestrasFuentes } from '@/data/apariencia'

const props = defineProps({
  modelValue: { type: String, default: null },
  catalogo: { type: Object, required: true }, // FUENTES_TITULOS o FUENTES_TEXTO
  etiqueta: { type: String, required: true },
})
const emit = defineEmits(['update:modelValue'])
const id = `gw-fuente-${Math.random().toString(36).slice(2, 8)}`

const raiz = ref(null)
const buscador = ref(null)
const lista = ref(null)
const abierto = ref(false)
const busqueda = ref('')
const activa = ref(-1)

const todas = computed(() => Object.entries(props.catalogo).map(([clave, f]) => ({ id: clave, ...f })))
const actual = computed(() => props.catalogo[props.modelValue])
const normalizar = t => t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
const filtradas = computed(() => {
  const q = normalizar(busqueda.value.trim())
  const lista = q ? todas.value.filter(f => normalizar(`${f.nombre} ${GRUPOS_FUENTES[f.grupo]}`).includes(q)) : todas.value
  // Mismo orden que se pinta: por grupo
  return Object.keys(GRUPOS_FUENTES).flatMap(g => lista.filter(f => f.grupo === g))
})
const grupos = computed(() => Object.entries(GRUPOS_FUENTES)
  .map(([clave, nombre]) => ({ clave, nombre, fuentes: filtradas.value.filter(f => f.grupo === clave) }))
  .filter(g => g.fuentes.length))

async function abrir() {
  cargarMuestrasFuentes(props.catalogo) // primera vez: muestras ligeras de todas
  abierto.value = true
  busqueda.value = ''
  activa.value = filtradas.value.findIndex(f => f.id === props.modelValue)
  await nextTick()
  buscador.value?.focus()
  desplazarA(activa.value)
  document.addEventListener('pointerdown', alPulsarFuera)
}

function cerrar(devolverFoco = false) {
  abierto.value = false
  document.removeEventListener('pointerdown', alPulsarFuera)
  if (devolverFoco) document.getElementById(`${id}-boton`)?.focus()
}

function elegir(clave) {
  emit('update:modelValue', clave)
  cerrar(true)
}

function alPulsarFuera(e) {
  if (!raiz.value?.contains(e.target)) cerrar()
}

function desplazarA(i) {
  if (i < 0) return
  nextTick(() => document.getElementById(`${id}-op-${filtradas.value[i]?.id}`)?.scrollIntoView({ block: 'nearest' }))
}

function alTeclear(e) {
  const n = filtradas.value.length
  if (e.key === 'ArrowDown') { e.preventDefault(); activa.value = (activa.value + 1) % n; desplazarA(activa.value) }
  else if (e.key === 'ArrowUp') { e.preventDefault(); activa.value = (activa.value - 1 + n) % n; desplazarA(activa.value) }
  else if (e.key === 'Enter') { e.preventDefault(); if (filtradas.value[activa.value]) elegir(filtradas.value[activa.value].id) }
  else if (e.key === 'Escape') { e.preventDefault(); cerrar(true) }
  else if (e.key === 'Tab') cerrar()
}

onBeforeUnmount(() => document.removeEventListener('pointerdown', alPulsarFuera))
</script>
