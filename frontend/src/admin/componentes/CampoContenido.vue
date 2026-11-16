<!-- Un campo editable de una página (tipos de config/cms_paginas.php). Muestra el texto
     actual; si difiere del original del diseño, lo marca y deja restaurarlo. -->
<template>
  <div>
    <div class="mb-1.5 flex flex-wrap items-center gap-2">
      <label :for="id" class="text-sm font-semibold text-azul-noche" :id="`${id}-etiqueta`">{{ campo.etiqueta }}</label>
      <span v-if="modificado" class="gw-chip bg-administraciones/10 text-administraciones-dark">Editado</span>
      <button v-if="modificado" type="button" class="ml-auto inline-flex min-h-9 items-center rounded-full px-3 text-xs font-semibold text-texto-secundario underline-offset-2 hover:bg-gray-100 hover:text-azul-noche hover:underline"
              @click="restaurar">Restaurar original</button>
    </div>

    <template v-if="campo.tipo === 'texto'">
      <input :id="id" type="text" class="gw-campo" :maxlength="campo.max" :value="modelValue ?? ''" :aria-describedby="`${id}-ayuda`"
             @input="$emit('update:modelValue', $event.target.value)" />
    </template>

    <template v-else-if="campo.tipo === 'texto_largo'">
      <textarea :id="id" rows="4" class="gw-campo" :maxlength="campo.max" :value="modelValue ?? ''" :aria-describedby="`${id}-ayuda`"
                @input="$emit('update:modelValue', $event.target.value)" />
    </template>

    <div v-else-if="campo.tipo === 'boton'" class="grid gap-3 rounded-xl border border-gray-200 bg-gray-50/60 p-3 sm:grid-cols-2" role="group" :aria-labelledby="`${id}-etiqueta`">
      <div>
        <label :for="id" class="mb-1 block text-xs font-semibold text-texto-secundario">Texto del botón</label>
        <input :id="id" type="text" maxlength="60" class="gw-campo" :value="modelValue?.texto ?? ''"
               @input="$emit('update:modelValue', { ...modelValue, texto: $event.target.value })" />
      </div>
      <div>
        <label :for="`${id}-url`" class="mb-1 block text-xs font-semibold text-texto-secundario">Destino (al pulsar)</label>
        <input :id="`${id}-url`" type="text" maxlength="255" class="gw-campo" :value="modelValue?.url ?? ''" :list="`${id}-rutas`"
               placeholder="/contacto o https://…" @input="$emit('update:modelValue', { ...modelValue, url: $event.target.value })" />
        <datalist :id="`${id}-rutas`">
          <option v-for="r in rutas" :key="r.valor" :value="r.valor">{{ r.nombre }}</option>
        </datalist>
      </div>
      <p class="gw-ayuda sm:col-span-2">Una página de la web (empieza por /), una sección (#servicios), una web externa (https://…), mailto: o tel:.</p>
    </div>

    <CampoGaleria v-else-if="campo.tipo === 'galeria'" :model-value="modelValue ?? []" :max="campo.max ?? 40" :titulo="campo.etiqueta"
                  @update:model-value="v => $emit('update:modelValue', v)" />

    <CampoImagen v-else-if="campo.tipo === 'imagen'" :model-value="modelValue ?? ''" texto-vacio="Foto original del diseño" hay-original
                 :titulo-selector="campo.etiqueta" @update:model-value="v => $emit('update:modelValue', v)" />

    <p :id="`${id}-ayuda`" class="gw-ayuda flex flex-wrap gap-x-3">
      <span v-if="campo.ayuda">{{ campo.ayuda }}</span>
      <span v-if="campo.max && typeof modelValue === 'string' && campo.tipo !== 'galeria'">{{ modelValue.length }} / {{ campo.max }}</span>
    </p>
    <p v-if="error" class="gw-error" role="alert">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import CampoImagen from './CampoImagen.vue'
import CampoGaleria from './CampoGaleria.vue'

const props = defineProps({
  campo: { type: Object, required: true },
  modelValue: { type: [String, Object], default: null },
  error: { type: String, default: '' },
  rutas: { type: Array, default: () => [] },
})
const emit = defineEmits(['update:modelValue'])

const id = `gw-campo-${props.campo.clave.replace(/\W/g, '-')}`

const modificado = computed(() => {
  const d = props.campo.defecto
  const v = props.modelValue
  if (props.campo.tipo === 'boton') return (v?.texto ?? '') !== (d?.texto ?? '') || (v?.url ?? '') !== (d?.url ?? '')
  if (props.campo.tipo === 'imagen') return !!v
  if (props.campo.tipo === 'galeria') return !!v?.length
  return (v ?? '') !== (d ?? '')
})

function restaurar() {
  const d = props.campo.defecto
  emit('update:modelValue', props.campo.tipo === 'boton' ? { ...d } : props.campo.tipo === 'galeria' ? [] : (d ?? ''))
}
</script>
