<template>
  <CabeceraPanel antetitulo="Sitio web" titulo="Apariencia" subtitulo="Colores, tipografía, tamaño del texto, logo y favicon. Solo opciones probadas: la maquetación no cambia.">
    <button type="button" class="gw-accion-secundaria" :disabled="guardando || esOriginal" @click="restaurar">Restaurar original</button>
    <button type="button" class="gw-accion-principal" :disabled="guardando || !hayCambios || !sePuedeAplicar" @click="guardar()">
      {{ guardando ? 'Aplicando…' : 'Aplicar cambios' }}
    </button>
  </CabeceraPanel>

  <div v-if="cargando" class="grid gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]" aria-busy="true">
    <div class="gw-tarjeta h-96 animate-pulse" /><div class="gw-tarjeta h-96 animate-pulse" />
  </div>

  <div v-else class="grid items-start gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
    <!-- Controles (compactos) -->
    <div class="gw-tarjeta divide-y divide-gray-100">
      <fieldset class="p-4">
        <legend class="mb-1 font-heading text-sm font-bold text-azul-noche">Paleta de colores</legend>
        <p class="mb-3 text-xs text-texto-secundario">Botones, enlaces, cabeceras, títulos y la franja final. Los colores de cada colectivo no cambian.</p>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-2">
          <label v-for="(p, clave) in PALETAS" :key="clave" class="cursor-pointer">
            <input v-model="form.paleta" type="radio" name="paleta" :value="clave" class="peer sr-only" />
            <span class="block rounded-xl border-2 border-gray-200 p-2 transition peer-checked:border-administraciones-dark peer-checked:bg-administraciones-dark/5 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-administraciones-dark">
              <span class="flex h-5 overflow-hidden rounded-md" aria-hidden="true">
                <span class="flex-1" :style="{ background: p.noche }" />
                <span v-for="n in [700, 600, 400, 100]" :key="n" class="flex-1" :style="{ background: p.principal[n] }" />
              </span>
              <span class="mt-1.5 block truncate text-xs font-semibold text-azul-noche">{{ p.nombre }}</span>
            </span>
          </label>
        </div>

        <!-- Personalizar: desplegable bajo las paletas. Al tocar un color se pasa a «personalizada»;
             elegir una tarjeta de arriba vuelve a la predefinida. -->
        <details class="group mt-3 rounded-xl border border-gray-200" :open="form.paleta === 'personalizada'" @toggle="alAbrirPersonalizar">
          <summary class="flex min-h-11 cursor-pointer list-none items-center gap-2 px-3 py-2 text-sm font-semibold text-azul-noche">
            <span class="flex h-4 w-8 overflow-hidden rounded" aria-hidden="true">
              <template v-if="personalizadaValida">
                <span class="flex-1" :style="{ background: form.color_noche }" />
                <span class="flex-1" :style="{ background: form.color_principal }" />
              </template>
              <span v-else class="flex-1 bg-gradient-to-r from-red-400 via-yellow-300 to-sky-500" />
            </span>
            Personalizar colores
            <span v-if="form.paleta === 'personalizada'" class="gw-chip bg-administraciones-dark/10 text-administraciones-dark">En uso</span>
            <ChevronDownIcon class="ml-auto h-4 w-4 text-gray-400 transition-transform group-open:rotate-180" aria-hidden="true" />
          </summary>
          <div class="space-y-4 border-t border-gray-100 p-3">
            <p class="text-xs text-texto-secundario">Elige tus propios colores con el selector, un código hexadecimal o una muestra rápida.</p>
            <CampoColor :model-value="form.color_principal" etiqueta="Color principal (botones, enlaces y cabeceras)" :muestras="MUESTRAS_PRINCIPAL"
                        @update:model-value="v => cambiarColor('color_principal', v)" />
            <CampoColor :model-value="form.color_titulos" etiqueta="Color de los títulos" uso="texto" :muestras="MUESTRAS_TITULOS"
                        @update:model-value="v => cambiarColor('color_titulos', v)" />
            <CampoColor :model-value="form.color_noche" etiqueta="Color de la franja final (fondo oscuro al pie de la página)" :muestras="MUESTRAS_NOCHE"
                        @update:model-value="v => cambiarColor('color_noche', v)" />
            <div v-if="personalizadaValida">
              <p class="mb-1 text-xs font-semibold text-texto-secundario">Escala calculada a partir del color principal</p>
              <span class="flex h-6 overflow-hidden rounded-lg" aria-hidden="true">
                <span v-for="(hex, n) in escalaPersonalizada" :key="n" class="flex-1" :style="{ background: hex }" :title="`${n}: ${hex}`" />
              </span>
            </div>
            <p v-if="errores['ajustes.color_principal'] || errores['ajustes.color_noche'] || errores['ajustes.color_titulos']" class="gw-error">
              {{ errores['ajustes.color_principal'] || errores['ajustes.color_titulos'] || errores['ajustes.color_noche'] }}
            </p>
          </div>
        </details>
      </fieldset>

      <div class="space-y-3 p-4">
        <p class="font-heading text-sm font-bold text-azul-noche">Tipografía</p>
        <div>
          <SelectorFuente v-model="form.fuente_titulos" :catalogo="FUENTES_TITULOS" etiqueta="Títulos" />
          <p class="mt-1 truncate text-lg font-bold text-azul-noche" :style="{ fontFamily: FUENTES_TITULOS[form.fuente_titulos]?.css }">Retos que generan talento</p>
        </div>
        <div>
          <SelectorFuente v-model="form.fuente_texto" :catalogo="FUENTES_TEXTO" etiqueta="Texto" />
          <p class="mt-1 text-sm text-gray-600" :style="{ fontFamily: FUENTES_TEXTO[form.fuente_texto]?.css }">Conectamos empresas, centros y alumnado.</p>
        </div>
        <p class="text-xs text-texto-secundario">{{ Object.keys(FUENTES_TITULOS).length }} fuentes revisadas de Google Fonts: todas con tildes y ñ, y con los grosores que usa la web.</p>
        <div>
          <p class="mb-1 text-xs font-semibold text-texto-secundario">Tamaño del texto</p>
          <div class="gw-carril" role="radiogroup" aria-label="Tamaño del texto">
            <button v-for="(t, clave) in TAMANOS" :key="clave" type="button" role="radio" class="gw-pestana !h-9 !px-3"
                    :class="{ 'gw-pestana--activa': form.tamano_texto === clave }" :aria-checked="form.tamano_texto === clave"
                    @click="form.tamano_texto = clave">{{ t.nombre }}</button>
          </div>
        </div>
      </div>

      <div class="space-y-4 p-4">
        <div>
          <p class="mb-2 font-heading text-sm font-bold text-azul-noche">Logo</p>
          <CampoImagen v-model="form.logo" :original="logoOriginal" titulo-selector="Logo de la web" compacto />
          <p class="gw-ayuda">PNG transparente, alto mínimo 120 px.</p>
        </div>
        <div>
          <p class="mb-2 font-heading text-sm font-bold text-azul-noche">Favicon</p>
          <CampoImagen v-model="form.favicon" original="/favicon.png" titulo-selector="Favicon" compacto />
          <p class="gw-ayuda">PNG cuadrado de 512 × 512 px.</p>
        </div>
      </div>

      <p class="p-4 text-xs text-texto-secundario">
        Las fotos de cabecera, el carrusel y la galería de inicio se cambian en
        <RouterLink :to="{ name: 'panel-paginas' }" class="gw-enlace">Páginas</RouterLink>.
      </p>
    </div>

    <!-- La web real, en directo -->
    <div class="min-w-0 space-y-2 xl:sticky xl:top-20">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="font-heading text-sm font-bold text-azul-noche">Vista rápida</p>
        <p v-if="hayCambios" class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800" role="status">
          Sin aplicar: la web sigue como estaba
        </p>
      </div>
      <VistaPreviaWeb :apariencia="form" @detalle="verGrande = true" />
    </div>
  </div>

  <!-- «Ver en detalle»: la web a pantalla casi completa con lo elegido, sin aplicar -->
  <VentanaModal :abierto="verGrande" titulo="Ver en detalle" ancho="completo"
                subtitulo="Navega por las páginas y cambia de dispositivo. Nada se publica hasta que apliques los cambios."
                @cerrar="verGrande = false">
    <VistaPreviaWeb v-if="verGrande" :apariencia="form" grande />
    <template #pie>
      <button type="button" class="gw-accion-secundaria" @click="verGrande = false">Seguir editando</button>
      <button type="button" class="gw-accion-principal" :disabled="guardando || !hayCambios || !sePuedeAplicar" @click="guardar().then(() => { verGrande = false })">
        {{ guardando ? 'Aplicando…' : hayCambios ? 'Aplicar cambios' : 'Sin cambios que aplicar' }}
      </button>
    </template>
  </VentanaModal>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import CampoImagen from '../componentes/CampoImagen.vue'
import CampoColor from '../componentes/CampoColor.vue'
import SelectorFuente from '../componentes/SelectorFuente.vue'
import VistaPreviaWeb from '../componentes/VistaPreviaWeb.vue'
import VentanaModal from '../componentes/VentanaModal.vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import logoOriginal from '@/assets/logo_colores.png'
import { ajustesService } from '@/services/admin/ajustesService'
import { mensajeError } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'
import { useCambiosSinGuardar } from '../composables/useCambiosSinGuardar'
import { APARIENCIA_ORIGINAL, FUENTES_TEXTO, FUENTES_TITULOS, PALETAS, TAMANOS, cargarFuentes, contrasteConBlanco, escalaDesde, esHex } from '@/data/apariencia'
import { erroresCampos } from '@/services/admin/panelApi'

const avisos = useAvisos()
const form = reactive({ ...APARIENCIA_ORIGINAL, logo: '', favicon: '' })
const instantanea = ref('')
const cargando = ref(true)
const guardando = ref(false)
const verGrande = ref(false)
const errores = ref({})

// «Comodines»: muestras rápidas ya comprobadas (todas legibles con texto blanco)
const MUESTRAS_PRINCIPAL = ['#3072AA', '#0F7C7D', '#3D7A1E', '#5B4BB7', '#A9432C', '#B4235A', '#7A1F5C', '#1D4ED8', '#374151', '#8A5A00']
// Títulos: tonos claramente distintos entre sí (todos legibles sobre blanco)
const MUESTRAS_TITULOS = ['#17283E', '#3072AA', '#0F7C7D', '#3D7A1E', '#5B4BB7', '#7A1F5C', '#A9432C', '#111827']
const MUESTRAS_NOCHE = ['#17283E', '#12313A', '#1B2E14', '#211B3F', '#2B1A15', '#111827', '#1E1B2E', '#0B2545']

const personalizadaValida = computed(() => esHex(form.color_principal) && esHex(form.color_noche))
const escalaPersonalizada = computed(() => (esHex(form.color_principal) ? escalaDesde(form.color_principal) : {}))
const sePuedeAplicar = computed(() => form.paleta !== 'personalizada'
  || (personalizadaValida.value && contrasteConBlanco(form.color_principal) >= 4.5 && contrasteConBlanco(form.color_noche) >= 4.5
      && (!form.color_titulos || (esHex(form.color_titulos) && contrasteConBlanco(form.color_titulos) >= 4.5))))

// Al abrir «Personalizar colores» se parte de la paleta elegida (sin cambiarla todavía)
function alAbrirPersonalizar(e) {
  if (!e.target.open || form.paleta === 'personalizada') return
  const base = PALETAS[form.paleta] ?? PALETAS.marca
  form.color_principal = base.principal[600].toUpperCase()
  form.color_noche = base.noche.toUpperCase()
  form.color_titulos = base.noche.toUpperCase()
}

// Tocar un color = usar la paleta personalizada
function cambiarColor(campo, valor) {
  form[campo] = valor
  form.paleta = 'personalizada'
}

// Los colores propios solo cuentan con la paleta personalizada en uso (abrir el desplegable no es un cambio)
const estadoComparable = (f) => JSON.stringify(f.paleta === 'personalizada' ? f : { ...f, color_principal: null, color_noche: null, color_titulos: null })
// Las fuentes elegidas, completas, para la muestra bajo cada desplegable
watch(() => [form.fuente_titulos, form.fuente_texto], () => cargarFuentes(form), { immediate: true })
const hayCambios = computed(() => estadoComparable(form) !== instantanea.value)
const esOriginal = computed(() => !hayCambios.value
  && Object.entries(APARIENCIA_ORIGINAL).every(([k, v]) => (form[k] || null) === v))
useCambiosSinGuardar(() => hayCambios.value && !guardando.value)

function aplicar(d) {
  Object.assign(form, Object.fromEntries(Object.keys(APARIENCIA_ORIGINAL).map(k => [k, d[k] ?? APARIENCIA_ORIGINAL[k]])))
  form.logo ??= ''
  form.favicon ??= ''
  instantanea.value = estadoComparable(form)
}

onMounted(async () => {
  try { aplicar(await ajustesService.ver('apariencia')) }
  catch (e) { avisos.error(mensajeError(e)) }
  finally { cargando.value = false }
})

async function guardar() {
  guardando.value = true
  errores.value = {}
  try {
    const personalizada = form.paleta === 'personalizada'
    aplicar(await ajustesService.guardar('apariencia', {
      ...form,
      color_principal: personalizada ? form.color_principal : null,
      color_noche: personalizada ? form.color_noche : null,
      color_titulos: personalizada ? (form.color_titulos || null) : null,
      logo: form.logo || null,
      favicon: form.favicon || null,
    }))
    avisos.ok('Cambios aplicados: ya se ven en la web.')
  } catch (e) {
    errores.value = erroresCampos(e)
    avisos.error(mensajeError(e, 'No se ha podido guardar la apariencia.'))
  } finally {
    guardando.value = false
  }
}

function restaurar() {
  if (!window.confirm('¿Volver a los colores, fuentes, logo y favicon originales de DuaLab?')) return
  Object.assign(form, { ...APARIENCIA_ORIGINAL, logo: '', favicon: '' })
  return guardar()
}
</script>
