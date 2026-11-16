<template>
  <CabeceraPanel antetitulo="Visibilidad" titulo="Analítica" subtitulo="Google Analytics y Tag Manager (lo que hacía Site Kit). La verificación de Search Console está en SEO." />

  <div v-if="cargando" class="gw-tarjeta h-64 animate-pulse" aria-busy="true" />
  <div v-else class="grid gap-6 xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
    <form class="gw-tarjeta space-y-5 p-5 sm:p-6" @submit.prevent="guardar">
      <div>
        <label for="gw-ga4" class="gw-etiqueta">ID de medición de Google Analytics 4</label>
        <input id="gw-ga4" v-model.trim="form.ga4_id" type="text" class="gw-campo" placeholder="G-XXXXXXXXXX" />
        <p class="gw-ayuda">Analytics → Administrar → Flujos de datos → tu web → «ID de medición».</p>
        <p v-if="errores['ajustes.ga4_id']" class="gw-error">Debe tener el formato G-XXXXXXXXXX.</p>
      </div>
      <div>
        <label for="gw-gtm" class="gw-etiqueta">ID de Google Tag Manager <span class="font-normal text-texto-secundario">(opcional)</span></label>
        <input id="gw-gtm" v-model.trim="form.gtm_id" type="text" class="gw-campo" placeholder="GTM-XXXXXXX" />
        <p class="gw-ayuda">Si lo pones, se carga Tag Manager en lugar de Analytics directamente (configura GA4 dentro de Tag Manager).</p>
        <p v-if="errores['ajustes.gtm_id']" class="gw-error">Debe tener el formato GTM-XXXXXXX.</p>
      </div>
      <label class="flex items-start gap-3">
        <input v-model="form.activa" type="checkbox" role="switch" class="gw-interruptor mt-0.5" />
        <span>
          <span class="block text-sm font-semibold text-azul-noche">Activar la medición en la web</span>
          <span class="gw-ayuda block">Los visitantes verán un aviso de cookies; Analytics solo se carga si lo aceptan (RGPD).</span>
        </span>
      </label>
      <button type="submit" class="gw-accion-principal" :disabled="guardando">{{ guardando ? 'Guardando…' : 'Guardar' }}</button>
    </form>

    <div class="space-y-6">
      <section class="gw-tarjeta p-5">
        <h2 class="font-heading text-base font-bold text-azul-noche">Estado</h2>
        <ul class="mt-3 space-y-2 text-sm">
          <li v-for="e in estado" :key="e.texto" class="flex items-center gap-2">
            <CheckCircleIcon v-if="e.ok" class="h-5 w-5 text-emerald-600" aria-hidden="true" />
            <span v-else class="h-5 w-5 rounded-full border-2 border-gray-300" aria-hidden="true" />
            <span :class="e.ok ? 'text-azul-noche' : 'text-texto-secundario'">{{ e.texto }}</span>
          </li>
        </ul>
      </section>
      <section class="gw-tarjeta p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 class="font-heading text-base font-bold text-azul-noche">Métricas en el panel</h2>
          <span class="gw-chip bg-amber-50 text-amber-800">Próximamente</span>
        </div>
        <p class="mt-2 text-sm text-texto-secundario">
          Ver visitas y búsquedas aquí requiere conectar la cuenta de Google (OAuth). Mientras tanto, consúltalas directamente:
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
          <a href="https://analytics.google.com/" target="_blank" rel="noopener" class="gw-accion-secundaria">Google Analytics</a>
          <a href="https://search.google.com/search-console" target="_blank" rel="noopener" class="gw-accion-secundaria">Search Console</a>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { CheckCircleIcon } from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import { ajustesService } from '@/services/admin/ajustesService'
import { mensajeError, erroresCampos } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'

const avisos = useAvisos()
const form = reactive({ ga4_id: '', gtm_id: '', activa: false })
const guardadoDatos = ref({})
const cargando = ref(true)
const guardando = ref(false)
const errores = ref({})

const estado = computed(() => [
  { ok: !!(guardadoDatos.value.ga4_id || guardadoDatos.value.gtm_id), texto: 'ID de Analytics o Tag Manager guardado' },
  { ok: !!guardadoDatos.value.activa, texto: 'Medición activada en la web' },
])

function aplicar(d) {
  guardadoDatos.value = d
  Object.assign(form, { ga4_id: d.ga4_id ?? '', gtm_id: d.gtm_id ?? '', activa: d.activa ?? false })
}

onMounted(async () => {
  try { aplicar(await ajustesService.ver('analitica')) }
  catch (e) { avisos.error(mensajeError(e)) }
  finally { cargando.value = false }
})

async function guardar() {
  guardando.value = true
  errores.value = {}
  try {
    const datos = Object.fromEntries(Object.entries(form).map(([k, v]) => [k, v === '' ? null : v]))
    aplicar(await ajustesService.guardar('analitica', datos))
    avisos.ok('Ajustes de analítica guardados.')
  } catch (e) {
    errores.value = erroresCampos(e)
    avisos.error(mensajeError(e))
  } finally {
    guardando.value = false
  }
}
</script>
