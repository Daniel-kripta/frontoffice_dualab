<!-- Historial de una página o entrada: quién la ha creado, editado, enviado a revisión o
     publicado (registro de actividad del panel). Se carga al montarse. -->
<template>
  <div>
    <div v-if="cargando" class="space-y-2" aria-busy="true"><span v-for="n in 3" :key="n" class="block h-8 animate-pulse rounded bg-gray-100" /></div>
    <p v-else-if="error" class="text-sm text-red-700">{{ error }}</p>
    <p v-else-if="!items.length" class="text-sm text-texto-secundario">Aún no hay cambios registrados.</p>
    <ol v-else class="space-y-3">
      <li v-for="a in items" :key="a.id">
        <!-- Fila a todo el ancho: se abre el detalle (qué cambió) -->
        <button type="button" class="-mx-2 flex w-[calc(100%+1rem)] gap-3 rounded-xl px-2 py-1 text-left text-sm hover:bg-gray-50" @click="abierta = a">
          <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-administraciones-dark" aria-hidden="true" />
          <span class="min-w-0 flex-1">
            <span class="block break-words text-azul-noche"><strong>{{ a.usuario.nombre }}</strong> {{ a.descripcion }}</span>
            <span class="block text-xs text-texto-secundario">{{ fechaHora(a.fecha) }}<template v-if="a.detalle?.length"> · {{ a.detalle.length }} {{ a.detalle.length === 1 ? 'cambio' : 'cambios' }}</template></span>
          </span>
        </button>
      </li>
    </ol>
    <DetalleActividad :accion="abierta" @cerrar="abierta = null" />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import DetalleActividad from './DetalleActividad.vue'
import { actividadService } from '@/services/admin/actividadService'
import { mensajeError } from '@/services/admin/panelApi'
import { fechaHora } from '../utilidades'

const props = defineProps({
  tipo: { type: String, required: true }, // pagina | entrada
  referencia: { type: [String, Number], required: true },
})

const items = ref([])
const cargando = ref(true)
const abierta = ref(null)
const error = ref('')

onMounted(async () => {
  try { items.value = await actividadService.historial(props.tipo, props.referencia) }
  catch (e) { error.value = mensajeError(e) }
  finally { cargando.value = false }
})
</script>
