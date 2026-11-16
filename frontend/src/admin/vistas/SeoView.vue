<template>
  <CabeceraPanel antetitulo="Visibilidad" titulo="SEO" subtitulo="Ajustes generales de buscadores y redes sociales (lo que hacía All in One SEO). El SEO de cada página o entrada se edita en ella." />

  <div v-if="cargando" class="gw-tarjeta h-64 animate-pulse" aria-busy="true" />
  <div v-else class="grid gap-6 xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
    <form class="gw-tarjeta space-y-5 p-5 sm:p-6" @submit.prevent="guardar">
      <div class="grid gap-4 sm:grid-cols-[1fr_8rem]">
        <div>
          <label for="gw-seo-sitio" class="gw-etiqueta">Nombre del sitio</label>
          <input id="gw-seo-sitio" v-model="form.titulo_sitio" type="text" maxlength="80" class="gw-campo" placeholder="DuaLab" />
          <p class="gw-ayuda">Se añade al final del título de cada página: «Centros {{ form.separador || '|' }} {{ form.titulo_sitio || 'DuaLab' }}».</p>
        </div>
        <div>
          <label for="gw-seo-sep" class="gw-etiqueta">Separador</label>
          <select id="gw-seo-sep" v-model="form.separador" class="gw-campo">
            <option v-for="s in ['|', '-', '–', '·', '•']" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
      </div>
      <div>
        <label for="gw-seo-desc" class="gw-etiqueta">Descripción por defecto</label>
        <textarea id="gw-seo-desc" v-model="form.descripcion" rows="3" maxlength="320" class="gw-campo" />
        <p class="gw-ayuda">Para las páginas que no tengan la suya. {{ (form.descripcion || '').length }} caracteres (ideal 120–160).</p>
        <p v-if="errores['ajustes.descripcion']" class="gw-error">{{ errores['ajustes.descripcion'] }}</p>
      </div>
      <div>
        <p class="gw-etiqueta">Imagen por defecto al compartir en redes</p>
        <CampoImagen v-model="form.imagen_og" titulo-selector="Imagen para redes sociales" />
        <p class="gw-ayuda">1200 × 630 px. Se usa cuando la página o entrada no tiene una propia.</p>
      </div>
      <label class="flex items-start gap-3 rounded-xl border p-4" :class="form.indexar ? 'border-gray-200' : 'border-amber-300 bg-amber-50'">
        <input v-model="form.indexar" type="checkbox" role="switch" class="gw-interruptor mt-0.5" />
        <span>
          <span class="block text-sm font-semibold text-azul-noche">Permitir que los buscadores muestren la web</span>
          <span class="gw-ayuda block">{{ form.indexar ? 'Activado: lo normal en producción.' : 'Desactivado: ninguna página aparecerá en Google. Úsalo solo en pruebas.' }}</span>
        </span>
      </label>
      <div v-if="sesion.esAdmin">
        <label for="gw-gsc" class="gw-etiqueta">Código de verificación de Search Console</label>
        <input id="gw-gsc" v-model.trim="form.search_console" type="text" class="gw-campo" placeholder="Solo el valor de content=&quot;…&quot;" />
        <p class="gw-ayuda">Search Console → Añadir propiedad → Prefijo de URL → Etiqueta HTML. Pega solo el código, no toda la etiqueta. Después envía allí el sitemap.</p>
        <p v-if="errores['ajustes.search_console']" class="gw-error">Pega solo el código (letras, números, guiones).</p>
      </div>
      <button type="submit" class="gw-accion-principal" :disabled="guardando">{{ guardando ? 'Guardando…' : 'Guardar ajustes' }}</button>
    </form>

    <div class="space-y-6">
      <section class="gw-tarjeta p-5">
        <h2 class="font-heading text-base font-bold text-azul-noche">Sitemap</h2>
        <p class="mt-1 text-sm text-texto-secundario">Se genera solo con las páginas publicadas y las noticias. Envíalo a Google Search Console.</p>
        <div class="mt-3 flex gap-2">
          <input :value="sitemap" type="text" readonly class="gw-campo bg-gray-50" aria-label="Dirección del sitemap" @focus="$event.target.select()" />
          <a :href="sitemap" target="_blank" rel="noopener" class="gw-accion-icono" aria-label="Abrir sitemap" title="Abrir"><ArrowTopRightOnSquareIcon class="h-5 w-5" aria-hidden="true" /></a>
        </div>
      </section>
      <section class="gw-tarjeta p-5">
        <h2 class="font-heading text-base font-bold text-azul-noche">Páginas con el SEO a medias</h2>
        <p v-if="!pendientes.length" class="mt-2 flex items-center gap-2 text-sm text-emerald-700"><CheckCircleIcon class="h-5 w-5" aria-hidden="true" /> Todas tienen título y descripción.</p>
        <ul v-else class="mt-2 divide-y divide-gray-100">
          <li v-for="p in pendientes" :key="p.clave" class="flex items-center justify-between gap-2 py-2 text-sm">
            <span class="min-w-0 truncate text-azul-noche">{{ p.titulo }}</span>
            <RouterLink :to="{ name: 'panel-pagina', params: { clave: p.clave } }" class="gw-enlace shrink-0">Completar</RouterLink>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ArrowTopRightOnSquareIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import CampoImagen from '../componentes/CampoImagen.vue'
import { ajustesService } from '@/services/admin/ajustesService'
import { paginasService } from '@/services/admin/paginasService'
import { mensajeError, erroresCampos } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'
import { usePanelSesion } from '@/stores/panelSesion'

const avisos = useAvisos()
const sesion = usePanelSesion()
// search_console solo lo cambia el admin: el editor no lo envía (el backend lo rechazaría)
const form = reactive({ titulo_sitio: '', separador: '|', descripcion: '', imagen_og: '', indexar: true, search_console: '' })
const paginas = ref([])
const cargando = ref(true)
const guardando = ref(false)
const errores = ref({})

const sitemap = `${(import.meta.env.VITE_API_URL ?? `${window.location.origin}/api`).replace(/\/$/, '')}/web/sitemap.xml`
const pendientes = computed(() => paginas.value.filter(p => p.ruta && !p.ruta.includes(':') && !p.seo_completo))

function aplicar(d) {
  Object.assign(form, { titulo_sitio: d.titulo_sitio ?? '', separador: d.separador ?? '|', descripcion: d.descripcion ?? '', imagen_og: d.imagen_og ?? '', indexar: d.indexar ?? true, search_console: d.search_console ?? '' })
}

onMounted(async () => {
  try {
    const [ajustes, lista] = await Promise.all([ajustesService.ver('seo'), paginasService.listar()])
    aplicar(ajustes)
    paginas.value = lista
  } catch (e) { avisos.error(mensajeError(e)) }
  finally { cargando.value = false }
})

async function guardar() {
  guardando.value = true
  errores.value = {}
  try {
    const { search_console: codigo, ...comunes } = form
    const datos = { ...comunes, imagen_og: form.imagen_og || null }
    if (sesion.esAdmin) datos.search_console = codigo || null
    aplicar(await ajustesService.guardar('seo', datos))
    avisos.ok('Ajustes de SEO guardados.')
  } catch (e) {
    errores.value = erroresCampos(e)
    avisos.error(mensajeError(e))
  } finally {
    guardando.value = false
  }
}
</script>
