<template>
  <CabeceraPanel antetitulo="Formularios" titulo="Integraciones" subtitulo="Envía cada formulario recibido a tu CRM o a tu herramienta de email marketing."
                 :volver="{ name: 'panel-formularios' }" volver-label="Formularios recibidos" />

  <div v-if="error" class="gw-tarjeta p-6 text-center">
    <p class="text-sm text-gray-700">{{ error }}</p>
    <button type="button" class="gw-accion-secundaria mt-4" @click="cargar">Reintentar</button>
  </div>
  <div v-else-if="cargando" class="space-y-4" aria-busy="true"><div v-for="n in 2" :key="n" class="gw-tarjeta h-48 animate-pulse" /></div>

  <div v-else class="grid gap-6 xl:grid-cols-2">
    <!-- Webhook -->
    <form class="gw-tarjeta space-y-5 p-5 sm:p-6 xl:col-span-2" @submit.prevent="guardar">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
          <h2 class="font-heading text-lg font-bold text-azul-noche">Webhook</h2>
          <p class="text-sm text-texto-secundario">Funciona con Zapier, Make, n8n, HubSpot, Pipedrive… Cada envío se manda al momento (en segundo plano) en formato JSON.</p>
        </div>
        <span class="gw-chip" :class="form.webhook_activo && form.webhook_url ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700'">
          {{ form.webhook_activo && form.webhook_url ? '● Activo' : 'Desactivado' }}
        </span>
      </div>
      <div>
        <label for="gw-webhook-url" class="gw-etiqueta">URL del webhook</label>
        <input id="gw-webhook-url" v-model.trim="form.webhook_url" type="url" class="gw-campo" placeholder="https://hooks.zapier.com/…" />
        <p class="gw-ayuda">Solo direcciones https://. Te la da la herramienta de destino al crear el «disparador por webhook».</p>
        <p v-if="errores['ajustes.webhook_url']" class="gw-error">{{ errores['ajustes.webhook_url'] }}</p>
      </div>
      <div>
        <label for="gw-webhook-secreto" class="gw-etiqueta">Clave de firma (opcional)</label>
        <div class="flex flex-col gap-2 sm:flex-row">
          <input id="gw-webhook-secreto" v-model="form.webhook_secreto" type="password" autocomplete="new-password" class="gw-campo"
                 :placeholder="secretos.webhook_secreto ? 'Guardada · escribe otra para cambiarla' : 'Mínimo 16 caracteres'" />
          <button v-if="secretos.webhook_secreto" type="button" class="gw-accion-peligro" @click="borrarSecreto('webhook_secreto')">Quitar clave</button>
        </div>
        <p class="gw-ayuda">Si la pones, cada envío lleva la cabecera <code>X-DuaLab-Firma</code> (HMAC-SHA256 del cuerpo) para que el destino compruebe que viene de DuaLab.</p>
        <p v-if="errores['ajustes.webhook_secreto']" class="gw-error">{{ errores['ajustes.webhook_secreto'] }}</p>
      </div>
      <label class="flex items-center gap-3">
        <input v-model="form.webhook_activo" type="checkbox" role="switch" class="gw-interruptor" />
        <span class="text-sm font-semibold text-azul-noche">Enviar los formularios nuevos a este webhook</span>
      </label>
      <details class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
        <summary class="cursor-pointer text-sm font-semibold text-azul-noche">Ver ejemplo de lo que se envía</summary>
        <pre class="mt-3 overflow-x-auto rounded-xl bg-azul-noche p-4 text-xs text-white">{{ ejemplo }}</pre>
      </details>
      <div class="flex flex-wrap gap-2">
        <button type="submit" class="gw-accion-principal" :disabled="guardando">{{ guardando ? 'Guardando…' : 'Guardar' }}</button>
        <button type="button" class="gw-accion-secundaria" :disabled="probando || !guardado.webhook_url" @click="probar">
          {{ probando ? 'Probando…' : 'Probar conexión' }}
        </button>
      </div>
      <p v-if="resultadoPrueba" class="rounded-xl px-4 py-3 text-sm" :class="resultadoPrueba.ok ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-700'" role="status">
        {{ resultadoPrueba.message }}
      </p>
    </form>

    <!-- Email marketing: estructura preparada -->
    <section v-for="s in servicios" :key="s.clave" class="gw-tarjeta space-y-4 p-5 sm:p-6">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <h2 class="font-heading text-lg font-bold text-azul-noche">{{ s.nombre }}</h2>
        <span class="gw-chip bg-amber-50 text-amber-800">Conexión directa próximamente</span>
      </div>
      <p class="text-sm text-texto-secundario">
        Puedes dejar guardados los datos de acceso. Mientras tanto, conéctalo a través del webhook (Zapier/Make tienen {{ s.nombre }}).
        Antes de añadir contactos a una lista de email hay que tener su consentimiento para comunicaciones comerciales (RGPD).
      </p>
      <div>
        <label :for="`gw-${s.clave}-key`" class="gw-etiqueta">API key</label>
        <div class="flex flex-col gap-2 sm:flex-row">
          <input :id="`gw-${s.clave}-key`" v-model="form[s.secreto]" type="password" autocomplete="new-password" class="gw-campo"
                 :placeholder="secretos[s.secreto] ? 'Guardada · escribe otra para cambiarla' : 'Pega aquí la API key'" />
          <button v-if="secretos[s.secreto]" type="button" class="gw-accion-peligro" @click="borrarSecreto(s.secreto)">Quitar</button>
        </div>
        <p class="gw-ayuda">Se guarda cifrada y no se vuelve a mostrar.</p>
      </div>
      <div>
        <label :for="`gw-${s.clave}-lista`" class="gw-etiqueta">{{ s.etiquetaLista }}</label>
        <input :id="`gw-${s.clave}-lista`" v-model.trim="form[s.lista]" :type="s.clave === 'brevo' ? 'number' : 'text'" class="gw-campo sm:max-w-xs" />
        <p v-if="errores[`ajustes.${s.lista}`]" class="gw-error">{{ errores[`ajustes.${s.lista}`] }}</p>
      </div>
      <button type="button" class="gw-accion-secundaria" :disabled="guardando" @click="guardar">Guardar</button>
    </section>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import { ajustesService } from '@/services/admin/ajustesService'
import { mensajeError, erroresCampos } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'

const avisos = useAvisos()
const SECRETOS = ['webhook_secreto', 'brevo_api_key', 'mailchimp_api_key']

const form = reactive({ webhook_url: '', webhook_activo: false, webhook_secreto: '', brevo_api_key: '', brevo_lista_id: '', mailchimp_api_key: '', mailchimp_audiencia: '' })
const secretos = ref({})
const guardado = ref({})
const cargando = ref(true)
const guardando = ref(false)
const probando = ref(false)
const resultadoPrueba = ref(null)
const error = ref('')
const errores = ref({})

const servicios = [
  { clave: 'brevo', nombre: 'Brevo (Sendinblue)', secreto: 'brevo_api_key', lista: 'brevo_lista_id', etiquetaLista: 'ID de la lista' },
  { clave: 'mailchimp', nombre: 'Mailchimp', secreto: 'mailchimp_api_key', lista: 'mailchimp_audiencia', etiquetaLista: 'ID de la audiencia' },
]

const ejemplo = JSON.stringify({
  evento: 'formulario.recibido', id: 128, fecha: '2026-11-09T10:30:00+01:00', formulario: 'empresa', accion: 'demo', pagina: '/empresas-asociadas',
  contacto: { nombre: 'Ana', apellidos: 'Pérez', email: 'ana@empresa.com', telefono: '600000000', cargo: 'RR. HH.' },
  datos: { empresa_nombre: 'Empresa S. L.', sector: 'Tecnología', provincia: 'Las Palmas' },
}, null, 2)

function aplicar(data) {
  for (const s of SECRETOS) {
    secretos.value[s] = data[s]?.configurado ?? false
    form[s] = ''
  }
  Object.assign(form, {
    webhook_url: data.webhook_url ?? '', webhook_activo: data.webhook_activo ?? false,
    brevo_lista_id: data.brevo_lista_id ?? '', mailchimp_audiencia: data.mailchimp_audiencia ?? '',
  })
  guardado.value = { ...data }
}

async function cargar() {
  error.value = ''
  try { aplicar(await ajustesService.ver('integraciones')) }
  catch (e) { error.value = mensajeError(e, 'No se han podido cargar las integraciones.') }
  finally { cargando.value = false }
}
onMounted(cargar)

async function guardar() {
  guardando.value = true
  errores.value = {}
  try {
    const datos = { ...form, brevo_lista_id: form.brevo_lista_id ? Number(form.brevo_lista_id) : null }
    aplicar(await ajustesService.guardar('integraciones', datos))
    avisos.ok('Integraciones guardadas.')
  } catch (e) {
    errores.value = erroresCampos(e)
    avisos.error(mensajeError(e))
  } finally {
    guardando.value = false
  }
}

async function borrarSecreto(nombre) {
  if (!window.confirm('¿Quitar esta clave guardada?')) return
  try {
    aplicar(await ajustesService.borrarSecreto('integraciones', nombre))
    avisos.ok('Clave quitada.')
  } catch (e) { avisos.error(mensajeError(e)) }
}

async function probar() {
  probando.value = true
  resultadoPrueba.value = null
  try {
    resultadoPrueba.value = await ajustesService.probarWebhook()
  } catch (e) {
    resultadoPrueba.value = { ok: false, message: e.response?.data?.message ?? mensajeError(e) }
  } finally {
    probando.value = false
  }
}
</script>
