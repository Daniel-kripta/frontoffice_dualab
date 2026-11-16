<!-- Color en hexadecimal: selector nativo (con cuentagotas en Chrome/Edge), código #RRGGBB,
     muestras rápidas ya legibles y aviso de contraste con texto blanco en directo. -->
<template>
  <div>
    <label :for="id" class="mb-1 block text-xs font-semibold text-texto-secundario">{{ etiqueta }}</label>
    <div class="flex items-center gap-2">
      <input type="color" :value="esHex(modelValue) ? modelValue : '#000000'" class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-[#7D8CA1] bg-white p-1"
             :aria-label="`${etiqueta}: selector de color`" @input="$emit('update:modelValue', $event.target.value.toUpperCase())" />
      <input :id="id" type="text" maxlength="7" spellcheck="false" autocomplete="off" class="gw-campo !py-2 font-mono uppercase"
             :value="modelValue ?? ''" placeholder="#3072AA" :aria-describedby="`${id}-estado`"
             @input="alEscribir($event.target.value)" />
    </div>
    <p :id="`${id}-estado`" class="mt-1 flex items-center gap-1.5 text-xs font-semibold" :class="estado.clase" role="status">
      <component :is="estado.icono" class="h-4 w-4 shrink-0" aria-hidden="true" /> {{ estado.texto }}
    </p>
    <div class="mt-2 flex flex-wrap gap-1.5" role="group" :aria-label="`Muestras rápidas para ${etiqueta.toLowerCase()}`">
      <button v-for="m in muestras" :key="m" type="button" class="h-8 w-8 rounded-full border-2 transition"
              :class="modelValue?.toUpperCase() === m ? 'border-azul-noche ring-2 ring-administraciones-dark/30' : 'border-white shadow-sm'"
              :style="{ background: m }" :title="m" :aria-label="`Usar ${m}`" :aria-pressed="modelValue?.toUpperCase() === m"
              @click="$emit('update:modelValue', m)" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { contrasteConBlanco, esHex } from '@/data/apariencia'

const props = defineProps({
  modelValue: { type: String, default: null },
  etiqueta: { type: String, required: true },
  muestras: { type: Array, default: () => [] },
  // 'fondo': texto blanco encima (botones, franja) · 'texto': el color sobre fondo blanco (títulos)
  uso: { type: String, default: 'fondo' },
})
const emit = defineEmits(['update:modelValue'])
const id = `gw-color-${Math.random().toString(36).slice(2, 8)}`

function alEscribir(v) {
  let valor = v.trim().toUpperCase()
  if (valor && !valor.startsWith('#')) valor = `#${valor}`
  emit('update:modelValue', valor)
}

const estado = computed(() => {
  if (!esHex(props.modelValue)) return { texto: 'Escribe un código como #3072AA', clase: 'text-texto-secundario', icono: ExclamationTriangleIcon }
  const c = contrasteConBlanco(props.modelValue)
  return c >= 4.5
    ? { texto: `${props.uso === 'texto' ? 'Se lee bien sobre fondo blanco' : 'Legible con texto blanco'} (contraste ${c.toFixed(1)}:1)`, clase: 'text-emerald-700', icono: CheckCircleIcon }
    : { texto: `Demasiado claro: ${props.uso === 'texto' ? 'no se leería sobre fondo blanco' : 'el texto blanco no se leería'} (${c.toFixed(1)}:1, mínimo 4,5)`, clase: 'text-red-700', icono: ExclamationTriangleIcon }
})
</script>
