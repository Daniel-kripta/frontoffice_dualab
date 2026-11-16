<!-- Enlace del correo: crear la contraseña (invitación) o elegir una nueva (la he olvidado).
     El token y el email vienen en la URL; el backend los comprueba y caducan. -->
<template>
  <div class="gw-panel flex min-h-dvh items-center justify-center bg-azul-noche px-4 py-10">
    <div class="w-full max-w-md">
      <div class="mb-6 flex items-center justify-center gap-3 text-white">
        <img :src="logo" alt="" class="h-12 w-auto" />
        <div>
          <p class="font-heading text-xl font-bold leading-tight">DuaLab</p>
          <p class="text-sm text-white/70">Gestión de la web</p>
        </div>
      </div>

      <div v-if="!enlaceCompleto" class="gw-tarjeta p-6 sm:p-8">
        <h1 class="font-heading text-2xl font-bold text-azul-noche">Enlace incompleto</h1>
        <p class="mt-2 text-sm text-texto-secundario">Abre el enlace tal como llegó en el correo, o pide uno nuevo desde el acceso al panel.</p>
        <RouterLink :to="{ name: 'panel-acceso' }" class="gw-accion-principal mt-6">Ir al acceso</RouterLink>
      </div>

      <form v-else class="gw-tarjeta p-6 sm:p-8" novalidate @submit.prevent="guardar">
        <h1 class="font-heading text-2xl font-bold text-azul-noche">{{ esInvitacion ? 'Elige tu contraseña' : 'Elige una contraseña nueva' }}</h1>
        <p class="mt-1 break-words text-sm text-texto-secundario">
          {{ esInvitacion ? 'Para entrar en el panel con' : 'Para la cuenta' }} <strong class="text-azul-noche">{{ email }}</strong>
        </p>

        <div v-if="error" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
          {{ error }}
          <RouterLink v-if="caducado" :to="{ name: 'panel-acceso' }" class="gw-enlace mt-1 block">Pedir un enlace nuevo</RouterLink>
        </div>

        <div class="mt-5">
          <label for="gw-nueva" class="gw-etiqueta">Contraseña</label>
          <div class="relative">
            <input id="gw-nueva" v-model="password" :type="ver ? 'text' : 'password'" autocomplete="new-password" required maxlength="128"
                   class="gw-campo pr-12" aria-describedby="gw-requisitos" />
            <button type="button" class="absolute right-1 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100"
                    :aria-label="ver ? 'Ocultar contraseña' : 'Mostrar contraseña'" @click="ver = !ver">
              <EyeSlashIcon v-if="ver" class="h-5 w-5" aria-hidden="true" />
              <EyeIcon v-else class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
          <ul id="gw-requisitos" class="mt-2 grid grid-cols-1 gap-1 text-xs sm:grid-cols-2">
            <li v-for="r in requisitos" :key="r.texto" class="flex items-center gap-1.5" :class="r.ok ? 'text-emerald-700' : 'text-texto-secundario'">
              <CheckCircleIcon v-if="r.ok" class="h-4 w-4 shrink-0" aria-hidden="true" />
              <span v-else class="h-4 w-4 shrink-0 rounded-full border border-gray-300" aria-hidden="true" />
              {{ r.texto }}
            </li>
          </ul>
        </div>
        <div class="mt-4">
          <label for="gw-repetir" class="gw-etiqueta">Repite la contraseña</label>
          <input id="gw-repetir" v-model="repetir" :type="ver ? 'text' : 'password'" autocomplete="new-password" required maxlength="128" class="gw-campo" />
          <p v-if="repetir && repetir !== password" class="gw-error">Las contraseñas no coinciden.</p>
        </div>

        <button type="submit" class="gw-accion-principal mt-6 w-full !h-11" :disabled="enviando || !valida">
          {{ enviando ? 'Guardando…' : 'Guardar y entrar' }}
        </button>
      </form>

      <RouterLink :to="{ name: 'panel-acceso' }" class="mt-6 block text-center text-sm font-semibold text-white/70 hover:text-white">← Volver al acceso</RouterLink>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { CheckCircleIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'
import '../panel.css'
import logo from '@/assets/logo_colores.png'
import { sesionService } from '@/services/admin/sesionService'
import { mensajeError } from '@/services/admin/panelApi'

const route = useRoute()
const router = useRouter()

const token = typeof route.query.token === 'string' ? route.query.token : ''
const email = typeof route.query.email === 'string' ? route.query.email : ''
const esInvitacion = route.query.modo !== 'restablecer'
const enlaceCompleto = !!(token && email)

const password = ref('')
const repetir = ref('')
const ver = ref(false)
const enviando = ref(false)
const error = ref('')
const caducado = ref(false)

// Misma política que el backend (8+, mayúscula, minúscula, número y símbolo)
const requisitos = computed(() => [
  { texto: 'Al menos 8 caracteres', ok: password.value.length >= 8 },
  { texto: 'Mayúscula y minúscula', ok: /[a-z]/.test(password.value) && /[A-Z]/.test(password.value) },
  { texto: 'Un número', ok: /\d/.test(password.value) },
  { texto: 'Un símbolo (!, ?, -…)', ok: /[^A-Za-z0-9]/.test(password.value) },
])
const valida = computed(() => requisitos.value.every(r => r.ok) && password.value === repetir.value)

async function guardar() {
  enviando.value = true
  error.value = ''
  caducado.value = false
  try {
    await sesionService.crearContrasena({
      token, email, modo: esInvitacion ? 'invitacion' : 'restablecer',
      password: password.value, password_confirmation: repetir.value,
    })
    router.replace({ name: 'panel-acceso', query: { contrasenaCambiada: '1' } })
  } catch (e) {
    caducado.value = e.response?.status === 422 && !e.response?.data?.errors
    error.value = e.response?.status === 429 ? 'Demasiados intentos. Espera un minuto.' : mensajeError(e)
  } finally {
    enviando.value = false
    password.value = ''
    repetir.value = ''
  }
}
</script>
