<!-- Formularios → Mailing y webhook (solo admin): qué pasa automáticamente con cada formulario de la web.
     Mailing (Brevo; Mailchimp como alternativa secundaria) arriba; el webhook (aviso a la app DuaLab y,
     opcional, a un CRM externo) debajo y plegado. Cada parte tiene su explicación en una ventana (icono «i»). -->
<template>
  <CabeceraPanel antetitulo="Formularios" titulo="Mailing y webhook"
                 subtitulo="Qué pasa automáticamente con cada formulario que llega a la web." />
  <p class="-mt-2 mb-5 text-sm text-texto-secundario">
    Los formularios se leen y se contestan en DuaLab Central («Primer contacto»).
    <a :href="URL_PRIMER_CONTACTO" target="_blank" rel="noopener" class="gw-enlace">Abrir DuaLab Central</a>
  </p>

  <div v-if="error" class="gw-tarjeta p-6 text-center">
    <p class="text-sm text-gray-700">{{ error }}</p>
    <button type="button" class="gw-accion-secundaria mt-4" @click="cargar">Reintentar</button>
  </div>
  <div v-else-if="cargando" class="space-y-4" aria-busy="true"><div v-for="n in 2" :key="n" class="gw-tarjeta h-48 animate-pulse" /></div>

  <div v-else class="space-y-6">
    <!-- ── Mailing: Brevo ─────────────────────────────────────────────── -->
    <section class="gw-tarjeta space-y-5 p-5 sm:p-6">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-2">
          <h2 class="font-heading text-lg font-bold text-azul-noche">Mailing con Brevo</h2>
          <button type="button" class="gw-accion-icono !h-9 !w-9" aria-label="Qué es el mailing" title="Qué es el mailing" @click="ayuda = 'mailing'">
            <InformationCircleIcon class="h-5 w-5" aria-hidden="true" />
          </button>
        </div>
        <span class="gw-chip bg-amber-50 text-amber-800">Aún no conectado</span>
      </div>
      <p class="text-sm text-texto-secundario">Añadir a quien rellena un formulario a una lista de Brevo (el grupo de contactos al que se mandan las newsletters). Hoy los datos se pueden dejar guardados, pero <strong>no se añade a nadie</strong>: la conexión está por hacer.</p>

      <AvisoRgpd />

      <CamposMailing v-model:clave="form.brevo_api_key" v-model:lista="form.brevo_lista_id" id-base="brevo"
                     :guardada="secretos.brevo_api_key" etiqueta-lista="ID de la lista" tipo-lista="number"
                     :error-lista="errores['ajustes.brevo_lista_id']" @quitar="borrarSecreto('brevo_api_key')" />
      <button type="button" class="gw-accion-principal" :disabled="guardando" @click="guardar">{{ guardando ? 'Guardando…' : 'Guardar' }}</button>

      <!-- Alternativa secundaria -->
      <details class="rounded-xl border border-gray-200 p-4">
        <summary class="cursor-pointer text-sm text-texto-secundario">¿Prefieres otro servicio? <span class="font-semibold text-azul-noche">Mailchimp</span></summary>
        <div class="mt-4 space-y-4">
          <p class="text-sm text-texto-secundario">Lo mismo, con una audiencia de Mailchimp en vez de una lista de Brevo. También está sin conectar y con la misma condición del consentimiento (RGPD).</p>
          <CamposMailing v-model:clave="form.mailchimp_api_key" v-model:lista="form.mailchimp_audiencia" id-base="mailchimp"
                         :guardada="secretos.mailchimp_api_key" etiqueta-lista="ID de la audiencia"
                         :error-lista="errores['ajustes.mailchimp_audiencia']" @quitar="borrarSecreto('mailchimp_api_key')" />
          <button type="button" class="gw-accion-secundaria" :disabled="guardando" @click="guardar">Guardar</button>
        </div>
      </details>
    </section>

    <!-- ── Webhook (plegado) ──────────────────────────────────────────── -->
    <details class="gw-tarjeta group">
      <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 p-5 sm:p-6">
        <span class="flex min-w-0 items-center gap-2">
          <ChevronRightIcon class="h-5 w-5 shrink-0 text-gray-400 transition-transform group-open:rotate-90" aria-hidden="true" />
          <span class="font-heading text-lg font-bold text-azul-noche">Webhook</span>
          <button type="button" class="gw-accion-icono !h-9 !w-9" aria-label="Qué es el webhook" title="Qué es el webhook" @click.stop.prevent="ayuda = 'webhook'">
            <InformationCircleIcon class="h-5 w-5" aria-hidden="true" />
          </button>
        </span>
        <span class="gw-chip" :class="appConectada ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'">
          DuaLab Central: {{ appConectada ? '● Conectado' : 'sin conectar' }}<template v-if="crmActivo"> · CRM: ● Activo</template>
        </span>
      </summary>

      <div class="space-y-6 border-t border-gray-100 p-5 sm:p-6">
        <!-- Aviso a la app: viene configurado en el servidor; aquí solo se ve y se prueba -->
        <section class="space-y-3">
          <h3 class="font-heading text-base font-bold text-azul-noche">Aviso a DuaLab Central</h3>
          <p class="text-sm text-texto-secundario">Cuando está conectado, cada formulario nuevo de empresa o centro aparece solo en DuaLab Central como <em>posible</em> empresa o centro, para validarlo o descartarlo allí. Se configura en el servidor, no en esta pantalla.</p>
          <p class="flex items-start gap-2 rounded-xl p-3 text-sm" :class="appConectada ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-900'" role="status">
            <CheckCircleIcon v-if="appConectada" class="h-5 w-5 shrink-0" aria-hidden="true" />
            <ExclamationTriangleIcon v-else class="h-5 w-5 shrink-0" aria-hidden="true" />
            <span v-if="appConectada">Conectado con DuaLab Central ({{ guardado.app_dualab.servidor }}).</span>
            <span v-else>
              <strong>Todavía no está conectado.</strong> Los formularios se pueden leer en DuaLab Central, pero hay que pasarlos a mano a posibles empresas o centros.
              Para conectarlo, quien despliega la web tiene que añadir dos líneas a la configuración del servidor (las indica «Detalles técnicos», abajo).
            </span>
          </p>
          <button type="button" class="gw-accion-secundaria" :disabled="probando === 'webhook' || !appConectada" @click="probar('webhook')">
            {{ probando === 'webhook' ? 'Probando…' : 'Probar conexión' }}
          </button>
          <p v-if="resultados.webhook" class="rounded-xl px-4 py-3 text-sm" :class="resultados.webhook.ok ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-700'" role="status">
            {{ resultados.webhook.message }}
          </p>
        </section>

        <DestinoWebhook :destino="CRM" :form="form" :guardada="secretos.crm_secreto"
                        :errores="errores" :guardando="guardando" :probando="probando === 'crm'" :resultado="resultados.crm"
                        :puede-probar="!!guardado.crm_url" @guardar="guardar" @probar="probar('crm')" @quitar="borrarSecreto('crm_secreto')" />

        <details class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
          <summary class="cursor-pointer text-sm font-semibold text-azul-noche">Detalles técnicos (para el equipo de desarrollo informático)</summary>
          <div class="mt-3 space-y-2 text-sm text-gray-700">
            <p>Cada formulario se manda al momento, en segundo plano (no hace esperar a quien lo rellena), con una petición <code>POST</code> en formato JSON, a cada destino activo por separado.
              Si hay clave, lleva la cabecera <code>X-DuaLab-Firma</code>: HMAC-SHA256 del cuerpo con esa clave. Si un destino falla, se reintenta 3 veces sin afectar al otro.</p>
            <p>El aviso a la app se configura en el <code>.env</code> de la web: <code>APP_DUALAB_WEBHOOK_URL</code> y <code>APP_DUALAB_WEBHOOK_SECRETO</code>
              (la misma clave que <code>FRONTOFFICE_WEBHOOK_SECRETO</code> en el <code>.env</code> de la app). El CRM externo, en esta pantalla.</p>
            <p>«Probar conexión» manda un ejemplo con <code>"prueba": true</code>: la app DuaLab lo confirma sin crear ninguna captación.</p>
            <p class="font-semibold text-azul-noche">Ejemplo de lo que se envía:</p>
          </div>
          <pre class="mt-2 overflow-x-auto rounded-xl bg-azul-noche p-4 text-xs text-white">{{ ejemplo }}</pre>
        </details>
      </div>
    </details>
  </div>

  <!-- Explicaciones en lenguaje llano -->
  <VentanaModal :abierto="ayuda === 'mailing'" titulo="¿Qué es el mailing?" @cerrar="ayuda = null">
    <div class="space-y-3 text-sm text-gray-700">
      <p>Es añadir automáticamente a quien rellena un formulario de la web a una <strong>lista de correo</strong> de Brevo: el grupo de contactos al que DuaLab manda sus newsletters y campañas.</p>
      <p>Así nadie tiene que pasar los contactos a mano de la web a Brevo.</p>
      <AvisoRgpd />
      <p>Hoy está sin conectar: se pueden dejar guardados la clave de Brevo (la «API key», que da Brevo en su configuración) y el número de la lista, pero todavía no se añade a nadie.</p>
    </div>
  </VentanaModal>
  <VentanaModal :abierto="ayuda === 'webhook'" titulo="¿Qué es el webhook?" @cerrar="ayuda = null">
    <div class="space-y-3 text-sm text-gray-700">
      <p>Un webhook es un <strong>aviso automático entre programas</strong>. Cuando alguien rellena un formulario en dualab.es, la web avisa al momento a otro sistema y le pasa los datos.</p>
      <p><strong class="text-azul-noche">Aviso a DuaLab Central.</strong> La web y la app DuaLab (la de DuaLab Central, el profesorado y la comercial) son dos sistemas distintos, cada uno con su servidor y su base de datos, aunque estén en el mismo alojamiento y bajo dualab.es. Por eso la web tiene que avisar a la app, por internet y a <code>api.dualab.es</code>, para que cada formulario de empresa o centro aparezca en DuaLab Central como <em>posible</em> empresa o centro, que allí se valida o se descarta.</p>
      <p>Lleva una <strong>clave compartida</strong>, como una contraseña que conocen las dos partes, para que la app sepa que el aviso viene de verdad de vuestra web.
        Esta conexión se configura en el servidor, igual que la que deja a DuaLab Central leer los formularios: aquí solo se ve si está conectada.</p>
      <p><strong class="text-azul-noche">CRM externo (opcional).</strong> Si algún día usáis un CRM (HubSpot, Pipedrive…) o una herramienta de automatización (Zapier, Make, n8n), se le puede mandar también cada formulario. Es un aviso aparte: no cambia nada del de la app.</p>
    </div>
  </VentanaModal>
</template>

<script setup>
import { computed, h, onMounted, reactive, ref } from 'vue'
import { CheckCircleIcon, ChevronRightIcon, ExclamationTriangleIcon, InformationCircleIcon, ShieldExclamationIcon } from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import VentanaModal from '../componentes/VentanaModal.vue'
import { URL_PRIMER_CONTACTO } from '../central'
import { ajustesService } from '@/services/admin/ajustesService'
import { mensajeError, erroresCampos } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'

const avisos = useAvisos()
const SECRETOS = ['crm_secreto', 'brevo_api_key', 'mailchimp_api_key']

// CRM externo: el único destino del webhook que se configura aquí (el de la app va en el servidor)
const CRM = { clave: 'crm', prefijo: 'crm', titulo: 'CRM externo (opcional)', texto: 'Un segundo aviso, aparte, por si usáis un CRM o una herramienta como Zapier, Make o n8n. No hace falta para DuaLab Central.',
  placeholder: 'https://… (te la da el CRM o la herramienta al crear el webhook)',
  ayudaUrl: 'Solo direcciones https://.',
  ayudaClave: 'Opcional. Si el CRM comprueba la firma, pon aquí la misma clave que allí.' }

const form = reactive({
  crm_url: '', crm_activo: false, crm_secreto: '',
  brevo_api_key: '', brevo_lista_id: '', mailchimp_api_key: '', mailchimp_audiencia: '',
})
const secretos = ref({})
const guardado = ref({})
const cargando = ref(true)
const guardando = ref(false)
const probando = ref(null)
const resultados = ref({})
const error = ref('')
const errores = ref({})
const ayuda = ref(null) // 'mailing' | 'webhook': ventana de explicación abierta

const appConectada = computed(() => !!guardado.value.app_dualab?.conectada)
const crmActivo = computed(() => !!(guardado.value.crm_activo && guardado.value.crm_url))

const ejemplo = JSON.stringify({
  evento: 'formulario.recibido', id: 128, fecha: '2026-11-09T10:30:00+01:00', formulario: 'empresa', accion: 'demo', pagina: '/empresas-asociadas',
  contacto: { nombre: 'Ana', apellidos: 'Pérez', email: 'ana@empresa.com', telefono: '600000000', cargo: 'RR. HH.' },
  datos: { empresa_nombre: 'Empresa S. L.', sector: 'Tecnología', provincia: 'Las Palmas' },
}, null, 2)

// Aviso de RGPD: se repite en Brevo y en su explicación, siempre igual y bien visible
const AvisoRgpd = () => h('div', { class: 'flex gap-3 rounded-xl border-2 border-amber-300 bg-amber-50 p-4 text-sm text-amber-900', role: 'note' }, [
  h(ShieldExclamationIcon, { class: 'h-6 w-6 shrink-0 text-amber-600', 'aria-hidden': 'true' }),
  h('div', { class: 'space-y-1' }, [
    h('p', { class: 'font-bold' }, 'Solo con consentimiento (RGPD)'),
    h('p', 'Solo se puede añadir a una lista de correo a quien haya aceptado expresamente recibir comunicaciones comerciales. ' +
      'El formulario tiene que pedirlo con una casilla propia, sin marcar de antemano y separada de la de privacidad. ' +
      'Sin ese consentimiento, añadir a alguien es ilegal y puede acarrear sanciones.'),
  ]),
])

// API key + lista de un servicio de mailing (Brevo o Mailchimp)
const CamposMailing = (p, { emit }) => h('div', { class: 'grid gap-4 sm:grid-cols-[1fr_14rem]' }, [
  h('div', [
    h('label', { for: `gw-${p.idBase}-key`, class: 'gw-etiqueta' }, 'Clave de acceso (API key)'),
    h('div', { class: 'flex flex-col gap-2 sm:flex-row' }, [
      h('input', { id: `gw-${p.idBase}-key`, type: 'password', autocomplete: 'new-password', class: 'gw-campo', value: p.clave,
        placeholder: p.guardada ? 'Guardada · escribe otra para cambiarla' : 'Pega aquí la API key', onInput: e => emit('update:clave', e.target.value) }),
      p.guardada && h('button', { type: 'button', class: 'gw-accion-peligro', onClick: () => emit('quitar') }, 'Quitar'),
    ]),
    h('p', { class: 'gw-ayuda' }, 'Se guarda cifrada y no se vuelve a mostrar.'),
  ]),
  h('div', [
    h('label', { for: `gw-${p.idBase}-lista`, class: 'gw-etiqueta' }, p.etiquetaLista),
    h('input', { id: `gw-${p.idBase}-lista`, type: p.tipoLista || 'text', class: 'gw-campo', value: p.lista, onInput: e => emit('update:lista', e.target.value.trim()) }),
    p.errorLista && h('p', { class: 'gw-error' }, p.errorLista),
  ]),
])
CamposMailing.props = ['clave', 'lista', 'idBase', 'guardada', 'etiquetaLista', 'tipoLista', 'errorLista']
CamposMailing.emits = ['update:clave', 'update:lista', 'quitar']

// Un destino del webhook: dirección, clave, interruptor y prueba
const DestinoWebhook = (p, { emit }) => {
  const d = p.destino
  const f = p.form
  return h('section', { class: 'space-y-4' }, [
    h('div', [
      h('h3', { class: 'font-heading text-base font-bold text-azul-noche' }, d.titulo),
      h('p', { class: 'text-sm text-texto-secundario' }, d.texto),
    ]),
    h('div', [
      h('label', { for: `gw-${d.clave}-url`, class: 'gw-etiqueta' }, 'Dirección a la que se avisa'),
      h('input', { id: `gw-${d.clave}-url`, type: 'url', class: 'gw-campo', value: f[`${d.prefijo}_url`], placeholder: d.placeholder,
        onInput: e => { f[`${d.prefijo}_url`] = e.target.value.trim() } }),
      h('p', { class: 'gw-ayuda' }, d.ayudaUrl),
      p.errores[`ajustes.${d.prefijo}_url`] && h('p', { class: 'gw-error' }, p.errores[`ajustes.${d.prefijo}_url`]),
    ]),
    h('div', [
      h('label', { for: `gw-${d.clave}-secreto`, class: 'gw-etiqueta' }, 'Clave de firma'),
      h('div', { class: 'flex flex-col gap-2 sm:flex-row' }, [
        h('input', { id: `gw-${d.clave}-secreto`, type: 'password', autocomplete: 'new-password', class: 'gw-campo', value: f[`${d.prefijo}_secreto`],
          placeholder: p.guardada ? 'Guardada · escribe otra para cambiarla' : 'Mínimo 16 caracteres', onInput: e => { f[`${d.prefijo}_secreto`] = e.target.value } }),
        p.guardada && h('button', { type: 'button', class: 'gw-accion-peligro', onClick: () => emit('quitar') }, 'Quitar clave'),
      ]),
      h('p', { class: 'gw-ayuda' }, d.ayudaClave),
      p.errores[`ajustes.${d.prefijo}_secreto`] && h('p', { class: 'gw-error' }, p.errores[`ajustes.${d.prefijo}_secreto`]),
    ]),
    h('label', { class: 'flex items-center gap-3' }, [
      h('input', { type: 'checkbox', role: 'switch', class: 'gw-interruptor', checked: f[`${d.prefijo}_activo`], onChange: e => { f[`${d.prefijo}_activo`] = e.target.checked } }),
      h('span', { class: 'text-sm font-semibold text-azul-noche' }, 'Activo: avisar de cada formulario nuevo'),
    ]),
    h('div', { class: 'flex flex-wrap gap-2' }, [
      h('button', { type: 'button', class: 'gw-accion-principal', disabled: p.guardando, onClick: () => emit('guardar') }, p.guardando ? 'Guardando…' : 'Guardar'),
      h('button', { type: 'button', class: 'gw-accion-secundaria', disabled: p.probando || !p.puedeProbar, onClick: () => emit('probar') }, p.probando ? 'Probando…' : 'Probar conexión'),
    ]),
    p.resultado && h('p', { class: ['rounded-xl px-4 py-3 text-sm', p.resultado.ok ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-700'], role: 'status' }, p.resultado.message),
  ])
}
DestinoWebhook.props = ['destino', 'form', 'guardada', 'errores', 'guardando', 'probando', 'resultado', 'puedeProbar']
DestinoWebhook.emits = ['guardar', 'probar', 'quitar']

function aplicar(data) {
  for (const s of SECRETOS) {
    secretos.value[s] = data[s]?.configurado ?? false
    form[s] = ''
  }
  Object.assign(form, {
    crm_url: data.crm_url ?? '', crm_activo: data.crm_activo ?? false,
    brevo_lista_id: data.brevo_lista_id ?? '', mailchimp_audiencia: data.mailchimp_audiencia ?? '',
  })
  guardado.value = { ...data }
}

async function cargar() {
  error.value = ''
  try { aplicar(await ajustesService.ver('integraciones')) }
  catch (e) { error.value = mensajeError(e, 'No se han podido cargar los ajustes.') }
  finally { cargando.value = false }
}
onMounted(cargar)

async function guardar() {
  guardando.value = true
  errores.value = {}
  try {
    const datos = { ...form, brevo_lista_id: form.brevo_lista_id ? Number(form.brevo_lista_id) : null }
    aplicar(await ajustesService.guardar('integraciones', datos))
    avisos.ok('Guardado.')
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

async function probar(destino) {
  probando.value = destino
  resultados.value = { ...resultados.value, [destino]: null }
  try {
    resultados.value[destino] = await ajustesService.probarWebhook(destino)
  } catch (e) {
    resultados.value[destino] = { ok: false, message: e.response?.data?.message ?? mensajeError(e) }
  } finally {
    probando.value = null
  }
}
</script>
