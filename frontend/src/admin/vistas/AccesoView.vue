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

      <form class="gw-tarjeta p-6 sm:p-8" novalidate @submit.prevent="entrar">
        <h1 class="font-heading text-2xl font-bold text-azul-noche">Acceso al panel</h1>
        <p class="mt-1 text-sm text-texto-secundario">Páginas, noticias, formularios y medios de dualab.es</p>
        <span aria-hidden="true" class="mt-3 flex h-1 w-40 gap-1.5">
          <span class="flex-1 rounded-full bg-centros" /><span class="flex-1 rounded-full bg-empresas" /><span class="flex-1 rounded-full bg-alumnos" /><span class="flex-1 rounded-full bg-administraciones" />
        </span>

        <div v-if="route.query.contrasenaCambiada && !error" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
          Contraseña cambiada. Entra con la nueva.
        </div>
        <div v-if="error" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ error }}</div>

        <div class="mt-5">
          <label for="gw-email" class="gw-etiqueta">Email</label>
          <input id="gw-email" v-model.trim="email" type="email" autocomplete="username" required class="gw-campo" />
        </div>
        <div class="mt-4">
          <label for="gw-password" class="gw-etiqueta">Contraseña</label>
          <div class="relative">
            <input id="gw-password" v-model="password" :type="verPassword ? 'text' : 'password'" autocomplete="current-password" required class="gw-campo pr-12" />
            <button type="button" class="absolute right-1 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100"
                    :aria-label="verPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'" @click="verPassword = !verPassword">
              <EyeSlashIcon v-if="verPassword" class="h-5 w-5" aria-hidden="true" />
              <EyeIcon v-else class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
        </div>

        <button type="submit" class="gw-accion-principal mt-6 w-full !h-11" :disabled="enviando || !email || !password">
          {{ enviando ? 'Entrando…' : 'Entrar' }}
        </button>
        <div class="mt-4 text-center">
          <button type="button" class="gw-enlace text-sm" @click="abrirOlvido">¿Has olvidado tu contraseña?</button>
        </div>
        <p class="mt-4 text-center text-xs text-texto-secundario">
          ¿No tienes acceso? Pídelo a quien administra la web de DuaLab.
        </p>
      </form>

      <!-- ¿Has olvidado tu contraseña? (mismo mensaje exista o no la cuenta) -->
      <form v-if="olvido.abierto" class="gw-tarjeta mt-4 p-6 sm:p-8" novalidate @submit.prevent="pedirEnlace">
        <h2 class="font-heading text-lg font-bold text-azul-noche">Restablecer la contraseña</h2>
        <p v-if="olvido.mensaje" class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ olvido.mensaje }}</p>
        <template v-else>
          <p class="mt-1 text-sm text-texto-secundario">Escribe el email de tu cuenta del panel y te enviaremos un enlace para elegir una nueva.</p>
          <label for="gw-email-olvido" class="gw-etiqueta mt-4">Email</label>
          <input id="gw-email-olvido" v-model.trim="olvido.email" type="email" autocomplete="username" required class="gw-campo" />
          <p v-if="olvido.error" class="gw-error" role="alert">{{ olvido.error }}</p>
          <button type="submit" class="gw-accion-principal mt-4" :disabled="olvido.enviando || !olvido.email">
            {{ olvido.enviando ? 'Enviando…' : 'Enviarme el enlace' }}
          </button>
        </template>
      </form>

      <a href="/" class="mt-6 block text-center text-sm font-semibold text-white/70 hover:text-white">← Volver a dualab.es</a>
    </div>
  </div>
</template>

<script setup>
import { nextTick, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'
import '../panel.css'
import logo from '@/assets/logo_colores.png'
import { usePanelSesion } from '@/stores/panelSesion'
import { sesionService } from '@/services/admin/sesionService'

const route = useRoute()
const router = useRouter()
const sesion = usePanelSesion()

const email = ref('')
const password = ref('')
const verPassword = ref(false)
const enviando = ref(false)
const error = ref(route.query.sinPermiso ? 'Esta cuenta no tiene acceso al panel de gestión web.' : '')

async function entrar() {
  error.value = ''
  enviando.value = true
  try {
    await sesion.entrar(email.value, password.value)
    if (!sesion.puedeEntrar) {
      await sesion.salir()
      error.value = 'Esta cuenta no tiene acceso al panel de gestión web.'
      return
    }
    const destino = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/gestion-web')
      ? route.query.redirect : sesion.inicio
    router.replace(destino)
  } catch (e) {
    // Mismo mensaje exista o no la cuenta (no se distingue usuario de contraseña)
    error.value = e.response?.status === 429
      ? 'Demasiados intentos. Espera un minuto antes de volver a probar.'
      : 'Email o contraseña incorrectos.'
  } finally {
    enviando.value = false
    password.value = ''
  }
}

const olvido = reactive({ abierto: false, email: '', enviando: false, mensaje: '', error: '' })

async function abrirOlvido() {
  Object.assign(olvido, { abierto: true, email: email.value, mensaje: '', error: '' })
  await nextTick()
  document.getElementById('gw-email-olvido')?.focus()
}

async function pedirEnlace() {
  olvido.enviando = true
  olvido.error = ''
  try {
    olvido.mensaje = (await sesionService.olvide(olvido.email)).message
  } catch (e) {
    olvido.error = e.response?.status === 429
      ? 'Demasiadas solicitudes. Espera unos minutos antes de volver a probar.'
      : 'No se ha podido enviar. Revisa el email e inténtalo de nuevo.'
  } finally {
    olvido.enviando = false
  }
}
</script>
