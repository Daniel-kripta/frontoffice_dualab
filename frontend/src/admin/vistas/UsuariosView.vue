<!-- Usuarios del panel (solo admin), con la misma lógica que la gestión de usuarios de la app DuaLab:
     alta con contraseña inicial, «Activar», editar (también email y contraseña), bloqueo y papelera.
     Además, el alta puede enviar un correo de acceso para que la persona elija su contraseña. -->
<template>
  <CabeceraPanel antetitulo="Equipo" titulo="Usuarios" subtitulo="Quién entra en el panel y qué puede hacer. Lo que aporta cada persona queda en Actividad.">
    <button type="button" class="gw-accion-principal" @click="abrirNuevo">
      <UserPlusIcon class="h-4 w-4" aria-hidden="true" /> Nueva cuenta
    </button>
  </CabeceraPanel>

  <!-- Qué hace cada rol -->
  <details class="gw-tarjeta group mb-4">
    <summary class="flex cursor-pointer list-none items-center gap-2 p-4 text-sm">
      <InformationCircleIcon class="h-5 w-5 shrink-0 text-texto-secundario" aria-hidden="true" />
      <span class="font-semibold text-azul-noche">Qué puede hacer cada rol</span>
      <ChevronDownIcon class="ml-auto h-4 w-4 shrink-0 text-texto-secundario transition-transform group-open:rotate-180" aria-hidden="true" />
    </summary>
    <ul class="grid gap-3 border-t border-gray-100 p-4 sm:grid-cols-3 sm:p-5">
      <li v-for="r in DESCRIPCION_ROLES" :key="r.rol" class="rounded-xl bg-gray-50 p-3">
        <span class="gw-chip" :class="CLASES_ROL[r.rol]">{{ NOMBRES_ROLES[r.rol] }}</span>
        <p class="mt-2 text-sm text-azul-noche">{{ r.texto }}</p>
      </li>
    </ul>
  </details>

  <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
    <div class="gw-carril w-fit" role="tablist" aria-label="Cuentas">
      <button type="button" role="tab" class="gw-pestana" :class="{ 'gw-pestana--activa': !papelera }" :aria-selected="!papelera" @click="verPapelera(false)">Activas</button>
      <button type="button" role="tab" class="gw-pestana" :class="{ 'gw-pestana--activa': papelera }" :aria-selected="papelera" @click="verPapelera(true)">
        Papelera <span v-if="enPapelera" class="gw-contador">{{ enPapelera }}</span>
      </button>
    </div>
    <div class="grid flex-1 gap-3 sm:grid-cols-[1fr_14rem]">
      <div class="relative">
        <label for="gw-buscar-usuario" class="sr-only">Buscar por nombre o email</label>
        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true" />
        <input id="gw-buscar-usuario" v-model="buscar" type="search" class="gw-campo pl-9" placeholder="Nombre o email" @input="buscarConRetardo" />
      </div>
      <label class="sr-only" for="gw-rol-filtro">Rol</label>
      <select id="gw-rol-filtro" v-model="rol" class="gw-campo" @change="cargar(1)">
        <option value="">Todos los roles</option>
        <option v-for="(n, clave) in NOMBRES_ROLES" :key="clave" :value="clave">{{ n }}</option>
      </select>
    </div>
  </div>

  <div class="gw-tarjeta overflow-hidden">
    <ul v-if="cargando" class="divide-y divide-gray-100"><li v-for="n in 4" :key="n" class="p-4"><span class="block h-10 animate-pulse rounded bg-gray-100" /></li></ul>
    <p v-else-if="!usuarios.length" class="p-8 text-center text-sm text-texto-secundario">
      {{ papelera ? 'La papelera está vacía.' : 'No hay cuentas con esos filtros.' }}
    </p>
    <ul v-else class="divide-y divide-gray-100">
      <li v-for="u in usuarios" :key="u.id" class="flex flex-col gap-3 p-4 md:flex-row md:items-center">
        <div class="flex min-w-0 flex-1 items-center gap-3">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-azul-noche text-sm font-bold text-white" aria-hidden="true">{{ inicial(u.name) }}</span>
          <div class="min-w-0">
            <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm font-semibold text-azul-noche">
              <span class="min-w-0 break-words">{{ u.name }}</span>
              <span v-if="u.eres_tu" class="gw-chip bg-gray-100 text-texto-secundario">Tú</span>
              <span class="gw-chip" :class="CLASES_ROL[u.role]">{{ NOMBRES_ROLES[u.role] ?? u.role }}</span>
              <span v-if="u.pendiente_activar" class="gw-chip bg-amber-50 text-amber-800">Pendiente de activar</span>
              <span v-if="u.bloqueado" class="gw-chip bg-red-50 text-red-700">Bloqueada</span>
            </p>
            <p class="truncate text-xs text-texto-secundario">{{ u.email }}</p>
            <p class="text-xs text-texto-secundario">
              <template v-if="papelera">En la papelera desde {{ fecha(u.en_papelera_desde) }}</template>
              <template v-else>Último acceso: {{ u.ultimo_acceso_en ? haceCuanto(u.ultimo_acceso_en) : 'nunca' }}</template>
            </p>
          </div>
        </div>

        <div class="flex flex-wrap gap-2 md:justify-end">
          <template v-if="papelera">
            <button type="button" class="gw-accion-secundaria !h-9" @click="accion(u, 'restaurar')">Restaurar</button>
            <button type="button" class="gw-accion-peligro !h-9" @click="accion(u, 'destruir')">Eliminar del todo</button>
          </template>
          <template v-else>
            <RouterLink :to="{ name: 'panel-actividad', query: { user_id: u.id } }" class="gw-accion-secundaria !h-9">Historial</RouterLink>
            <button type="button" class="gw-accion-secundaria !h-9" @click="abrirEdicion(u)">Editar</button>
            <template v-if="!u.eres_tu">
              <template v-if="u.pendiente_activar">
                <button type="button" class="gw-accion-principal !h-9" @click="accion(u, 'activar')">Activar</button>
                <button type="button" class="gw-accion-secundaria !h-9" @click="accion(u, 'reenviarCorreo')">Reenviar correo</button>
              </template>
              <button type="button" class="gw-accion-secundaria !h-9" @click="accion(u, u.bloqueado ? 'desbloquear' : 'bloquear')">
                {{ u.bloqueado ? 'Desbloquear' : 'Bloquear' }}
              </button>
              <button type="button" class="gw-accion-icono !h-9 !w-9 hover:!border-red-200 hover:!text-red-600" :aria-label="`Enviar a la papelera la cuenta de ${u.name}`" @click="accion(u, 'eliminar')">
                <TrashIcon class="h-4 w-4" aria-hidden="true" />
              </button>
            </template>
          </template>
        </div>
      </li>
    </ul>
  </div>
  <Paginacion class="mt-4" :meta="meta" @ir="cargar" />

  <!-- Nueva cuenta / editar -->
  <VentanaModal :abierto="modal.abierto" :titulo="modal.usuario ? 'Editar cuenta' : 'Nueva cuenta'"
                :subtitulo="modal.usuario ? modal.usuario.email : 'Como en la app DuaLab: la cuenta queda pendiente de activar hasta que la actives o la persona use el enlace del correo.'" @cerrar="modal.abierto = false">
    <form id="gw-form-usuario" class="space-y-4" novalidate @submit.prevent="guardar">
      <div>
        <label for="gw-usuario-nombre" class="gw-etiqueta">Nombre</label>
        <input id="gw-usuario-nombre" v-model.trim="form.name" type="text" maxlength="120" required class="gw-campo" autocomplete="off" />
        <p v-if="errores.name" class="gw-error">{{ errores.name }}</p>
      </div>
      <div>
        <label for="gw-usuario-email" class="gw-etiqueta">Email</label>
        <input id="gw-usuario-email" v-model.trim="form.email" type="email" maxlength="190" required class="gw-campo" autocomplete="off" />
        <p v-if="errores.email" class="gw-error">{{ errores.email }}</p>
      </div>
      <fieldset>
        <legend class="gw-etiqueta">Rol</legend>
        <p v-if="modal.usuario?.eres_tu" class="gw-ayuda !mt-0 mb-2">No puedes cambiar tu propio rol.</p>
        <div class="grid gap-2">
          <label v-for="r in DESCRIPCION_ROLES" :key="r.rol"
                 class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 has-[:checked]:border-administraciones-dark has-[:checked]:bg-administraciones-dark/5"
                 :class="{ 'pointer-events-none opacity-60': modal.usuario?.eres_tu }">
            <input v-model="form.role" type="radio" name="rol" :value="r.rol" class="mt-1 h-4 w-4 accent-administraciones-dark" :disabled="modal.usuario?.eres_tu" />
            <span class="min-w-0">
              <span class="block text-sm font-semibold text-azul-noche">{{ NOMBRES_ROLES[r.rol] }}</span>
              <span class="block text-xs text-texto-secundario">{{ r.texto }}</span>
            </span>
          </label>
        </div>
        <p v-if="errores.role || errores.usuario" class="gw-error">{{ errores.role || errores.usuario }}</p>
      </fieldset>

      <!-- Contraseña: obligatoria al crear; al editar, solo si se cambia (la propia, en Mi perfil) -->
      <fieldset v-if="!modal.usuario?.eres_tu">
        <legend class="gw-etiqueta">{{ modal.usuario ? 'Contraseña nueva (opcional)' : 'Contraseña inicial' }}</legend>
        <div class="grid gap-3 sm:grid-cols-2">
          <div class="relative">
            <label for="gw-usuario-pass" class="sr-only">Contraseña</label>
            <input id="gw-usuario-pass" v-model="form.password" :type="verPassword ? 'text' : 'password'" maxlength="128" autocomplete="new-password"
                   class="gw-campo pr-12" placeholder="Contraseña" />
            <button type="button" class="absolute right-1 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100"
                    :aria-label="verPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'" @click="verPassword = !verPassword">
              <EyeSlashIcon v-if="verPassword" class="h-5 w-5" aria-hidden="true" />
              <EyeIcon v-else class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
          <div>
            <label for="gw-usuario-pass2" class="sr-only">Repite la contraseña</label>
            <input id="gw-usuario-pass2" v-model="form.password_confirmation" :type="verPassword ? 'text' : 'password'" maxlength="128" autocomplete="new-password"
                   class="gw-campo" placeholder="Repite la contraseña" />
          </div>
        </div>
        <p class="gw-ayuda">Mínimo 8 caracteres con mayúscula, minúscula, número y símbolo.<template v-if="modal.usuario"> Al cambiarla se cierran sus sesiones abiertas.</template></p>
        <p v-if="errores.password" class="gw-error">{{ errores.password }}</p>
      </fieldset>

      <label v-if="!modal.usuario" class="flex items-start gap-3">
        <input v-model="form.enviar_correo" type="checkbox" class="mt-1 h-4 w-4 accent-administraciones-dark" />
        <span class="text-sm text-azul-noche">
          Enviarle un correo de acceso
          <span class="gw-ayuda block !mt-0">Con un enlace para elegir su propia contraseña (dura 3 días). Al usarlo, la cuenta queda activada.</span>
        </span>
      </label>
    </form>
    <template #pie>
      <button type="button" class="gw-accion-secundaria" @click="modal.abierto = false">Cancelar</button>
      <button type="submit" form="gw-form-usuario" class="gw-accion-principal" :disabled="guardando || !form.name || !form.email || (!modal.usuario && !form.password)">
        {{ guardando ? 'Guardando…' : modal.usuario ? 'Guardar' : 'Crear cuenta' }}
      </button>
    </template>
  </VentanaModal>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import {
  ChevronDownIcon, EyeIcon, EyeSlashIcon, InformationCircleIcon, MagnifyingGlassIcon, TrashIcon, UserPlusIcon,
} from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import Paginacion from '../componentes/Paginacion.vue'
import VentanaModal from '../componentes/VentanaModal.vue'
import { usuariosService } from '@/services/admin/usuariosService'
import { mensajeError, erroresCampos } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'
import { fecha, haceCuanto } from '../utilidades'
import { NOMBRES_ROLES } from '../roles'

const DESCRIPCION_ROLES = [
  { rol: 'editor', texto: 'Crea contenido: páginas, noticias (las envía a revisión), medios y SEO.' },
  { rol: 'admin', texto: 'Todo lo anterior y además aprueba noticias, cambia apariencia, idiomas, analítica e integraciones, y gestiona el equipo.' },
]
const CLASES_ROL = { admin: 'bg-administraciones-dark/10 text-administraciones-dark', editor: 'bg-sky-50 text-sky-800' }

const route = useRoute()
const avisos = useAvisos()
const usuarios = ref([])
const meta = ref(null)
const enPapelera = ref(0)
const cargando = ref(true)
const papelera = ref(false)
const buscar = ref('')
const rol = ref(typeof route.query.rol === 'string' ? route.query.rol : '')
const modal = reactive({ abierto: false, usuario: null })
const VACIO = { name: '', email: '', role: 'editor', password: '', password_confirmation: '', enviar_correo: true }
const form = reactive({ ...VACIO })
const verPassword = ref(false)
const errores = ref({})
const guardando = ref(false)

const inicial = nombre => (nombre || '?').trim().charAt(0).toUpperCase()

async function cargar(pagina = 1) {
  cargando.value = true
  try {
    const r = await usuariosService.listar({ page: pagina, buscar: buscar.value || undefined, rol: rol.value || undefined, papelera: papelera.value ? 1 : undefined })
    usuarios.value = r.data
    meta.value = r.meta
    enPapelera.value = r.papelera
  } catch (e) { avisos.error(mensajeError(e)) }
  finally { cargando.value = false }
}
onMounted(cargar)

let retardo
function buscarConRetardo() { clearTimeout(retardo); retardo = setTimeout(() => cargar(1), 300) }
function verPapelera(valor) { papelera.value = valor; cargar(1) }

function abrirNuevo() {
  Object.assign(modal, { abierto: true, usuario: null })
  Object.assign(form, VACIO)
  verPassword.value = false
  errores.value = {}
}
function abrirEdicion(u) {
  Object.assign(modal, { abierto: true, usuario: u })
  Object.assign(form, { ...VACIO, name: u.name, email: u.email, role: u.role })
  verPassword.value = false
  errores.value = {}
}

async function guardar() {
  guardando.value = true
  errores.value = {}
  try {
    if (modal.usuario) {
      const datos = { name: form.name, email: form.email }
      if (!modal.usuario.eres_tu) datos.role = form.role
      if (form.password) Object.assign(datos, { password: form.password, password_confirmation: form.password_confirmation })
      await usuariosService.actualizar(modal.usuario.id, datos)
      avisos.ok('Cuenta guardada.')
    } else {
      await usuariosService.crear({ ...form })
      avisos.ok(form.enviar_correo
        ? `Cuenta creada y correo de acceso enviado a ${form.email}. Actívala si no va a usar el enlace.`
        : 'Cuenta creada. Actívala para que pueda iniciar sesión.')
    }
    modal.abierto = false
    cargar(meta.value?.current_page ?? 1)
  } catch (e) {
    errores.value = erroresCampos(e)
    avisos.error(mensajeError(e))
  } finally {
    // La contraseña no se queda en memoria del formulario
    form.password = ''
    form.password_confirmation = ''
    guardando.value = false
  }
}

const CONFIRMAR = {
  bloquear: u => `¿Bloquear la cuenta de ${u.name}? Se cerrarán sus sesiones y no podrá entrar hasta que la desbloquees.`,
  eliminar: u => `¿Enviar a la papelera la cuenta de ${u.name}? No podrá entrar; podrás restaurarla después.`,
  destruir: u => `¿Eliminar del todo la cuenta de ${u.name}? No se puede deshacer. Lo que hizo seguirá en Actividad con su nombre.`,
}
const HECHO = {
  bloquear: 'Cuenta bloqueada.', desbloquear: 'Cuenta desbloqueada.', activar: 'Cuenta activada: ya puede iniciar sesión.',
  reenviarCorreo: 'Correo de acceso enviado de nuevo.',
  eliminar: 'Cuenta enviada a la papelera.', restaurar: 'Cuenta restaurada.', destruir: 'Cuenta eliminada.',
}

async function accion(u, nombre) {
  if (CONFIRMAR[nombre] && !window.confirm(CONFIRMAR[nombre](u))) return
  try {
    await usuariosService[nombre](u.id)
    avisos.ok(HECHO[nombre])
    cargar(meta.value?.current_page ?? 1)
  } catch (e) {
    avisos.error(e.response?.data?.errors?.usuario?.[0] ?? mensajeError(e))
  }
}
</script>
