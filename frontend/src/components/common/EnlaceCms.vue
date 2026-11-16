<!-- Enlace de un botón editable desde el panel: ruta interna (/contacto?tipo=centro o
     un objeto de ruta) con RouterLink; https://, mailto: y tel: con <a>. El panel solo
     admite esos destinos (backend: Rules\EnlaceSeguro). -->
<template>
  <RouterLink v-if="interno" :to="to"><slot /></RouterLink>
  <a v-else :href="to" v-bind="externo ? { target: '_blank', rel: 'noopener noreferrer' } : {}"><slot /></a>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  to: { type: [String, Object], required: true },
})

const interno = computed(() => typeof props.to === 'object' || /^\/(?!\/)/.test(props.to))
const externo = computed(() => typeof props.to === 'string' && props.to.startsWith('https://'))
</script>
