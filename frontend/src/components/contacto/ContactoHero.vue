<template>
  <!-- Columna izquierda de /contacto: mensaje, ventajas e imagen del agente.
       Escritorio: cada bloque (título, intro, ventajas, foto) apila las versiones de todos
       los agentes en la misma celda y solo se ve la del activo; así cada bloque mide lo del
       agente más largo y nada se desplaza ni cambia de tamaño al cambiar de agente.
       Móvil/tablet: solo la versión del agente activo (sin huecos). -->
  <div class="flex flex-col h-full">
    <p class="flex items-center gap-3 text-xs font-bold uppercase tracking-[0.25em] text-primary-600 mb-4">
      {{ antetitulo }} <span class="w-10 h-px bg-primary-300" aria-hidden="true" />
    </p>

    <div class="pila">
      <h1 v-for="(a, t) in agentes" :key="t" :class="capa(t)" :aria-hidden="t !== activo || undefined"
          class="text-[28px] sm:text-[32px] md:text-[40px] lg:text-[34px] xl:text-[36px] font-extrabold tracking-tight leading-[1.1]">
        <span v-for="(parte, i) in a.titulo" :key="i" class="block" :class="parte.color ? a.text : 'text-azul-noche'">{{ parte.t }}</span>
      </h1>
    </div>

    <div class="pila mt-4">
      <p v-for="(a, t) in agentes" :key="t" :class="capa(t)" :aria-hidden="t !== activo || undefined"
         class="text-sm sm:text-base lg:text-sm text-gray-600 leading-relaxed max-w-md lg:max-w-none">{{ a.intro }}</p>
    </div>

    <!-- Móvil: se omiten (el formulario queda más cerca). Tablet: en fila. Escritorio: en columna. -->
    <div class="pila max-sm:!hidden mt-6 lg:mt-5">
      <ul v-for="(a, t) in agentes" :key="t" :class="capa(t)" :aria-hidden="t !== activo || undefined"
          class="grid grid-cols-3 gap-4 lg:grid-cols-1 lg:gap-3">
        <li v-for="b in a.beneficios" :key="b.titulo" class="flex items-start gap-3">
          <span class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" :class="[a.soft, a.text]">
            <component :is="b.icon" class="w-5 h-5" />
          </span>
          <div>
            <p class="font-heading font-bold text-sm text-azul-noche">{{ b.titulo }}</p>
            <p class="text-xs text-gray-500 leading-snug">{{ b.desc }}</p>
          </div>
        </li>
      </ul>
    </div>

    <!-- Escritorio: foto que ocupa el alto libre hasta el pie de la sección y sangra hasta el
         borde izquierdo de la pantalla, como en los diseños FORMULARIO. Mismo marco para
         todos los agentes: solo cambia la imagen y el rótulo. -->
    <div class="relative hidden lg:block grow min-h-56 mt-8 -mb-12 -ml-[max(1.5rem,calc((100vw-72rem)/2))]" aria-hidden="true">
      <template v-for="(a, t) in agentes" :key="t">
        <img :src="a.imagen" alt="" class="contacto-blob absolute inset-0 w-full h-full object-cover" :class="{ invisible: t !== activo }" />
        <p class="absolute -top-4 left-[max(1.5rem,calc((100vw-72rem)/2))] z-10 font-hand font-semibold text-2xl leading-6 -rotate-12 px-4 py-2 rounded-2xl text-white shadow-lg"
           :class="[a.bg, { invisible: t !== activo }]">
          <template v-for="(l, i) in a.rotulo" :key="i">{{ l }}<br v-if="i < a.rotulo.length - 1"></template>
        </p>
      </template>
    </div>
  </div>
</template>

<script setup>
// agentes: { clave: config } de los agentes elegibles; activo: clave del agente elegido;
// antetitulo: etiqueta superior (la vista la pasa editable desde el panel)
const props = defineProps({
  agentes: { type: Object, required: true },
  activo: { type: String, required: true },
  antetitulo: { type: String, default: 'Contacto' },
})

// Versión inactiva: en escritorio ocupa sitio sin verse; en móvil/tablet no existe
const capa = (t) => (t === props.activo ? '' : 'max-lg:hidden lg:invisible')
</script>

<style scoped>
/* Todas las versiones en la misma celda: el bloque mide lo de la más larga */
.pila {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
}
.pila > * {
  grid-area: 1 / 1;
}
.contacto-blob {
  border-radius: 0 60% 0 0 / 0 45% 0 0;
}
</style>
