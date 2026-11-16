<template>
  <form class="max-w-xl mx-auto" @submit.prevent="submit">
    <div class="grid grid-cols-2 gap-4 mb-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nombre completo *</label>
        <input v-model="form.nombre" type="text" placeholder="Juan Pérez" class="form-input" required />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email *</label>
        <input v-model="form.email" type="email" placeholder="juan@ejemplo.com" class="form-input" required />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tipo de organización *</label>
        <select v-model="form.tipo" class="form-input bg-white" required>
          <option value="">Selecciona una opción</option>
          <option value="empresa">Empresa</option>
          <option value="centro">Centro Educativo</option>
          <option value="alumno">Alumno / Particular</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Teléfono</label>
        <input v-model="form.telefono" type="tel" placeholder="+34 600 000 000" class="form-input" />
      </div>
    </div>
    <button
      type="submit"
      class="w-full inline-flex items-center justify-center gap-2.5 py-4 bg-primary-600 text-white rounded-full font-black text-sm uppercase tracking-widest shadow-[0_8px_25px_rgba(48,114,170,0.3)] hover:shadow-[0_14px_35px_rgba(48,114,170,0.4)] hover:bg-primary-700 transition-all duration-300 hover:-translate-y-0.5 active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0"
      :disabled="loading"
    >
      {{ loading ? 'Enviando…' : 'Enviar solicitud' }}
    </button>
    <p v-if="success" class="text-primary-600 text-sm text-center mt-3 font-medium">
      ✓ Solicitud recibida. Nos pondremos en contacto pronto.
    </p>
    <p v-if="errorGeneral" class="text-red-600 text-sm text-center mt-3 font-medium" role="alert">
      {{ errorGeneral }}
    </p>
  </form>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import api, { ensureCsrfCookie } from '@/services/api'
import { paginaOrigen } from '@/utils/paginaOrigen'

// Tipo preseleccionado desde fuera (p. ej. botón "Inscribe tu centro" de la
// home). Solo rellena el select: el usuario puede cambiarlo antes de enviar.
const props = defineProps({ tipo: { type: String, default: '' } })

const form    = reactive({ nombre: '', email: '', tipo: props.tipo, telefono: '' })
watch(() => props.tipo, (t) => { form.tipo = t })
const loading = ref(false)
const success = ref(false)
const errorGeneral = ref('')

async function submit() {
  loading.value = true
  success.value = false
  errorGeneral.value = ''
  try {
    await ensureCsrfCookie()
    // Página de la web desde la que se envía: la bandeja del panel los clasifica por ella
    await api.post('/contacto', { ...form, pagina_origen: paginaOrigen() })
    success.value = true
    Object.assign(form, { nombre: '', email: '', tipo: '', telefono: '' })
  } catch (error) {
    const status = error.response?.status
    if (status === 422) {
      errorGeneral.value = 'Revisa los datos del formulario.'
    } else if (status === 429) {
      // Mismo criterio que ContactoView: throttle por IP o límite de 3 envíos/hora por email
      errorGeneral.value = 'Has enviado varias solicitudes en poco tiempo. Inténtalo de nuevo dentro de una hora.'
    } else {
      errorGeneral.value = 'No hemos podido enviar tu solicitud. Inténtalo de nuevo más tarde.'
    }
  } finally {
    loading.value = false
  }
}
</script>
