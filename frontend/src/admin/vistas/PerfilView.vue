<template>
  <CabeceraPanel antetitulo="Mi cuenta" titulo="Mi" destacado="perfil" subtitulo="Edita tu nombre o cambia la contraseña de acceso." />

  <div v-if="cargando" class="gw-tarjeta h-80 max-w-2xl animate-pulse" aria-busy="true" />
  <form v-else class="gw-tarjeta max-w-2xl space-y-5 p-5 sm:p-6" novalidate @submit.prevent="guardar">
    <p v-if="mensaje" class="rounded-xl px-4 py-3 text-sm" :class="mensajeOk ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-700'" role="status">{{ mensaje }}</p>

    <div>
      <label for="gw-perfil-nombre" class="gw-etiqueta">Nombre</label>
      <input id="gw-perfil-nombre" v-model.trim="form.name" type="text" maxlength="255" autocomplete="name" required class="gw-campo" />
      <p v-if="errores.name" class="gw-error">{{ errores.name }}</p>
    </div>

    <div>
      <label for="gw-perfil-email" class="gw-etiqueta">Email</label>
      <div class="flex gap-2">
        <input id="gw-perfil-email" :value="email" type="email" readonly class="gw-campo bg-gray-50" aria-describedby="gw-perfil-email-ayuda" />
        <button type="button" class="gw-accion-icono" :aria-label="copiado ? 'Copiado' : 'Copiar email'" :title="copiado ? 'Copiado' : 'Copiar'" @click="copiarEmail">
          <CheckIcon v-if="copiado" class="h-5 w-5 text-emerald-600" aria-hidden="true" />
          <ClipboardDocumentIcon v-else class="h-5 w-5" aria-hidden="true" />
        </button>
      </div>
      <p id="gw-perfil-email-ayuda" class="gw-ayuda">Es tu identificador de acceso y <strong>no cambia</strong>. Rol: {{ sesion.esAdmin ? 'administración' : 'edición' }}.</p>
    </div>

    <fieldset class="space-y-4 border-t border-gray-100 pt-5">
      <legend class="font-heading text-base font-bold text-azul-noche">Cambiar contraseña</legend>
      <p class="text-sm text-texto-secundario">Déjalo vacío si no quieres cambiarla.</p>
      <div>
        <label for="gw-perfil-nueva" class="gw-etiqueta">Nueva contraseña</label>
        <input id="gw-perfil-nueva" v-model="form.password" type="password" maxlength="128" autocomplete="new-password" class="gw-campo" />
        <p v-if="errores.password" class="gw-error">{{ errores.password }}</p>
        <p v-else-if="cambiaContrasena" class="gw-ayuda">Mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo.</p>
      </div>
      <template v-if="cambiaContrasena">
        <div>
          <label for="gw-perfil-repetir" class="gw-etiqueta">Repetir contraseña</label>
          <input id="gw-perfil-repetir" v-model="form.password_confirmation" type="password" maxlength="128" autocomplete="new-password" class="gw-campo" />
          <p v-if="errores.password_confirmation" class="gw-error">{{ errores.password_confirmation }}</p>
        </div>
        <div>
          <label for="gw-perfil-actual" class="gw-etiqueta">Contraseña actual</label>
          <input id="gw-perfil-actual" v-model="form.current_password" type="password" maxlength="128" autocomplete="current-password" class="gw-campo" />
          <p v-if="errores.current_password" class="gw-error">{{ errores.current_password }}</p>
        </div>
        <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
          Al cambiar la contraseña se cerrará la sesión en todos los dispositivos y tendrás que volver a entrar.
        </p>
      </template>
    </fieldset>

    <button type="submit" class="gw-accion-principal" :disabled="guardando || !form.name">{{ guardando ? 'Guardando…' : 'Guardar cambios' }}</button>
  </form>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { CheckIcon, ClipboardDocumentIcon } from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import { perfilService } from '@/services/admin/perfilService'
import { mensajeError, erroresCampos } from '@/services/admin/panelApi'
import { usePanelSesion } from '@/stores/panelSesion'

// Misma lógica que «Mi usuario» de la app DuaLab (MiUsuario.vue): el email no se edita;
// al cambiar la contraseña se cierra la sesión y se vuelve a entrar.
const router = useRouter()
const sesion = usePanelSesion()

const email = ref('')
const form = reactive({ name: '', password: '', password_confirmation: '', current_password: '' })
const errores = ref({})
const mensaje = ref('')
const mensajeOk = ref(false)
const cargando = ref(true)
const guardando = ref(false)
const copiado = ref(false)

const cambiaContrasena = computed(() => form.password.length > 0)

onMounted(async () => {
  try {
    const yo = await perfilService.ver()
    email.value = yo.email
    form.name = yo.name
  } catch (e) {
    mensaje.value = mensajeError(e, 'No se ha podido cargar tu perfil.')
  } finally {
    cargando.value = false
  }
})

async function copiarEmail() {
  try {
    await navigator.clipboard.writeText(email.value)
    copiado.value = true
    setTimeout(() => { copiado.value = false }, 2000)
  } catch { /* sin permiso de portapapeles: el campo sigue seleccionable */ }
}

async function guardar() {
  errores.value = {}
  mensaje.value = ''
  if (cambiaContrasena.value && form.password !== form.password_confirmation) {
    errores.value.password_confirmation = 'Las contraseñas no coinciden.'
    return
  }
  guardando.value = true
  try {
    const datos = { name: form.name }
    if (cambiaContrasena.value) Object.assign(datos, { password: form.password, password_confirmation: form.password_confirmation, current_password: form.current_password })
    const r = await perfilService.guardar(datos)
    if (r.password_changed) {
      await sesion.salir()
      router.push({ name: 'panel-acceso', query: { contrasenaCambiada: '1' } })
      return
    }
    sesion.usuario = { ...sesion.usuario, name: r.user.name }
    mensaje.value = r.message
    mensajeOk.value = true
  } catch (e) {
    errores.value = erroresCampos(e)
    mensaje.value = Object.keys(errores.value).length ? 'Revisa los campos marcados.' : mensajeError(e)
    mensajeOk.value = false
  } finally {
    guardando.value = false
    form.password = form.password_confirmation = form.current_password = ''
  }
}
</script>
