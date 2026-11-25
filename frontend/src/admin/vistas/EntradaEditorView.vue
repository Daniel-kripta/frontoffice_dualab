<template>
  <CabeceraPanel antetitulo="Entradas" :titulo="esNueva ? 'Añadir nueva entrada' : 'Editar entrada'" :volver="{ name: 'panel-entradas' }" volver-label="Todas las entradas">
    <a v-if="entrada?.estado === 'publicada' && !hayCambios" :href="`/noticias/${entrada.slug}`" target="_blank" rel="noopener" class="gw-accion-secundaria">
      <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" /> Ver en la web
    </a>
    <ComoNosVen v-if="entrada?.estado === 'publicada' && !hayCambios" :ruta="`/noticias/${entrada.slug}`" :idioma="entrada.idioma" />
  </CabeceraPanel>

  <div v-if="error" class="gw-tarjeta p-6 text-center">
    <p class="text-sm text-gray-700">{{ error }}</p>
    <button type="button" class="gw-accion-secundaria mt-4" @click="cargar">Reintentar</button>
  </div>

  <div v-else-if="cargando" class="grid gap-6 lg:grid-cols-[1fr_20rem]" aria-busy="true">
    <div class="gw-tarjeta h-96 animate-pulse" /><div class="gw-tarjeta h-64 animate-pulse" />
  </div>

  <form v-else class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]" @submit.prevent="guardar()">
    <!-- Columna principal (deshabilitada si la entrada publicada no se puede editar) -->
    <fieldset :disabled="soloLectura" class="min-w-0 space-y-5">
      <div class="gw-tarjeta space-y-4 p-5">
        <div>
          <label for="gw-titulo" class="sr-only">Título</label>
          <input id="gw-titulo" v-model="form.titulo" type="text" maxlength="200" required placeholder="Añade el título"
                 class="block w-full rounded-xl border border-transparent bg-transparent px-1 py-1 font-heading text-2xl font-bold text-azul-noche placeholder:text-gray-400 focus:border-[#7D8CA1] focus:outline-none sm:text-3xl" />
          <p v-if="errores.titulo" class="gw-error">{{ errores.titulo }}</p>
        </div>
        <!-- Enlace permanente -->
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
          <span class="font-semibold text-azul-noche">Enlace:</span>
          <span class="min-w-0 break-all text-texto-secundario">{{ urlBase }}/noticias/</span>
          <template v-if="editandoSlug">
            <label for="gw-slug" class="sr-only">Final de la dirección</label>
            <input id="gw-slug" v-model="form.slug" type="text" maxlength="200" class="gw-campo !w-auto min-w-0 flex-1 !py-1.5" @blur="form.slug = slugificar(form.slug)" />
            <button type="button" class="gw-enlace" @click="editandoSlug = false">Hecho</button>
          </template>
          <template v-else>
            <strong class="break-all text-azul-noche">{{ slugVisible || '…' }}</strong>
            <button type="button" class="gw-enlace" @click="editandoSlug = true; form.slug = slugVisible">Editar</button>
          </template>
          <p v-if="errores.slug" class="gw-error w-full">{{ errores.slug }}</p>
        </div>
      </div>

      <div>
        <p class="gw-etiqueta">Contenido</p>
        <EditorRico v-model="form.contenido" :disabled="soloLectura" />
        <p class="gw-ayuda">El texto se limpia al guardar: solo se conservan títulos, negritas, listas, enlaces, tablas e imágenes.</p>
      </div>

      <div class="gw-tarjeta p-5">
        <label for="gw-extracto" class="gw-etiqueta">Extracto</label>
        <textarea id="gw-extracto" v-model="form.extracto" rows="3" maxlength="500" class="gw-campo" placeholder="Resumen breve que se ve en el listado de noticias" />
        <p class="gw-ayuda">{{ (form.extracto || '').length }} / 500 · Si lo dejas vacío, el listado solo muestra el título.</p>
      </div>

      <section class="gw-tarjeta p-5 sm:p-6">
        <h2 class="mb-4 font-heading text-base font-bold text-azul-noche">SEO <span class="text-sm font-medium text-texto-secundario">(antes: All in One SEO)</span></h2>
        <CajaSeo v-model="form.seo" :titulo-por-defecto="`${form.titulo || 'Título de la entrada'} | DuaLab`" :descripcion-por-defecto="form.extracto"
                 :imagen-por-defecto="form.imagen_destacada" :url-visible="`${urlBase.replace(/^https?:\/\//, '')}/noticias/${slugVisible}`"
                 :slug="slugVisible" :texto="textoPlano" :errores="erroresSeo" />
      </section>
    </fieldset>

    <!-- Columna lateral (en móvil, debajo) -->
    <aside class="space-y-5 lg:sticky lg:top-20">
      <section class="gw-tarjeta p-5">
        <h2 class="mb-3 font-heading text-base font-bold text-azul-noche">Publicación</h2>

        <!-- Avisos del flujo de aprobación -->
        <p v-if="form.estado === 'borrador' && entrada?.nota_revision" class="mb-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900" role="status">
          <strong class="block">Devuelta para revisar:</strong> {{ entrada.nota_revision }}
        </p>
        <p v-if="soloLectura" class="mb-3 rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700">
          Ya está publicada: solo un administrador puede modificarla o retirarla.
        </p>
        <p v-else-if="form.estado === 'pendiente' && !puedeAprobar" class="mb-3 rounded-xl border border-sky-200 bg-sky-50 p-3 text-sm text-sky-900">
          Enviada a revisión. Se verá en la web cuando un administrador la apruebe.
        </p>

        <dl class="space-y-2 text-sm">
          <div class="flex justify-between gap-2"><dt class="text-texto-secundario">Estado</dt><dd><span class="gw-chip" :class="ESTADOS_ENTRADA[estadoVisible].clase">{{ ESTADOS_ENTRADA[estadoVisible].nombre }}</span></dd></div>
          <div v-if="idiomas.length > 1" class="flex justify-between gap-2"><dt class="text-texto-secundario">Idioma</dt><dd class="font-semibold text-azul-noche">{{ nombreIdioma(form.idioma) }}</dd></div>
          <div v-if="entrada?.autor" class="flex justify-between gap-2"><dt class="text-texto-secundario">Autor</dt><dd class="text-right text-azul-noche">{{ entrada.autor }}</dd></div>
          <div v-if="entrada?.enviada_revision_en && form.estado !== 'borrador'" class="flex justify-between gap-2"><dt class="text-texto-secundario">Enviada</dt><dd class="text-right text-azul-noche">{{ fechaHora(entrada.enviada_revision_en) }}</dd></div>
          <div v-if="entrada?.aprobada_en && form.estado === 'publicada'" class="flex justify-between gap-2"><dt class="text-texto-secundario">Aprobada</dt><dd class="text-right text-azul-noche">{{ fechaHora(entrada.aprobada_en) }}<template v-if="entrada.aprobada_por"> · {{ entrada.aprobada_por }}</template></dd></div>
        </dl>

        <template v-if="!soloLectura">
          <div class="mt-4">
            <label for="gw-fecha" class="gw-etiqueta">Fecha de publicación</label>
            <input id="gw-fecha" v-model="form.publicada_en" type="datetime-local" class="gw-campo" />
            <p class="gw-ayuda">Vacío = en cuanto se apruebe. Una fecha futura la deja <strong>programada</strong>.</p>
          </div>
          <label class="mt-4 flex items-center gap-3">
            <input v-model="form.destacada" type="checkbox" role="switch" class="gw-interruptor" />
            <span class="text-sm font-semibold text-azul-noche">Destacar arriba en Noticias</span>
          </label>

          <!-- Acciones: el editor envía a revisión; el administrador aprueba -->
          <div class="mt-5 flex flex-wrap gap-2">
            <template v-if="form.estado === 'publicada'">
              <button type="button" class="gw-accion-principal" :disabled="guardando || !form.titulo" @click="guardar('publicada')">
                {{ guardando ? 'Guardando…' : 'Actualizar' }}
              </button>
              <button type="button" class="gw-accion-peligro" :disabled="guardando" @click="retirar">Retirar de la web</button>
            </template>
            <template v-else>
              <button type="button" class="gw-accion-secundaria" :disabled="guardando || !form.titulo" @click="guardar('borrador')">
                {{ form.estado === 'pendiente' ? 'Volver a borrador' : 'Guardar borrador' }}
              </button>
              <button v-if="puedeAprobar" type="button" class="gw-accion-principal" :disabled="guardando || !form.titulo" @click="guardar('publicada')">
                <CheckIcon class="h-4 w-4" aria-hidden="true" />
                {{ guardando ? 'Guardando…' : programada ? 'Aprobar y programar' : 'Aprobar y publicar' }}
              </button>
              <button v-else type="button" class="gw-accion-principal" :disabled="guardando || !form.titulo" @click="guardar('pendiente')">
                {{ guardando ? 'Guardando…' : form.estado === 'pendiente' ? 'Guardar cambios' : 'Enviar a revisión' }}
              </button>
              <button v-if="puedeAprobar && form.estado === 'pendiente'" type="button" class="gw-accion-peligro" :disabled="guardando" @click="devolver">
                Devolver al autor
              </button>
            </template>
          </div>
          <button v-if="!esNueva" type="button" class="mt-4 inline-flex min-h-9 items-center text-sm font-semibold text-red-600 underline-offset-2 hover:underline" @click="aPapelera">
            Mover a la papelera
          </button>
        </template>
      </section>

      <section class="gw-tarjeta p-5">
        <label for="gw-categoria" class="mb-3 block font-heading text-base font-bold text-azul-noche">Categoría</label>
        <select id="gw-categoria" v-model="form.categoria_id" class="gw-campo">
          <option :value="null">Sin categoría</option>
          <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
        </select>
        <RouterLink :to="{ name: 'panel-categorias' }" class="gw-enlace mt-2 inline-block text-sm">Gestionar categorías</RouterLink>
      </section>

      <section class="gw-tarjeta p-5">
        <h2 class="mb-3 font-heading text-base font-bold text-azul-noche">Imagen destacada</h2>
        <CampoImagen v-model="form.imagen_destacada" titulo-selector="Imagen destacada" texto-vacio="Sin imagen destacada" />
        <p v-if="errores.imagen_destacada" class="gw-error">{{ errores.imagen_destacada }}</p>
      </section>

      <section v-if="idiomas.length > 1 && !esNueva" class="gw-tarjeta p-5">
        <h2 class="mb-3 font-heading text-base font-bold text-azul-noche">Traducciones</h2>
        <ul class="space-y-2 text-sm">
          <li v-for="i in idiomas.filter(x => x.codigo !== form.idioma)" :key="i.codigo" class="flex items-center justify-between gap-2">
            <span class="font-semibold text-azul-noche">{{ i.nombre }}</span>
            <RouterLink v-if="traduccion(i.codigo)" :to="{ name: 'panel-entrada', params: { id: traduccion(i.codigo).id } }" class="gw-enlace">Editar</RouterLink>
            <button v-else type="button" class="gw-enlace" @click="traducir(i.codigo)">Crear traducción</button>
          </li>
        </ul>
      </section>

      <section v-if="!esNueva && entrada" class="gw-tarjeta p-5">
        <h2 class="mb-3 font-heading text-base font-bold text-azul-noche">Historial</h2>
        <HistorialActividad :key="versionHistorial" tipo="entrada" :referencia="entrada.id" />
      </section>
    </aside>
  </form>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowTopRightOnSquareIcon, CheckIcon } from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import EditorRico from '../componentes/EditorRico.vue'
import CajaSeo from '../componentes/CajaSeo.vue'
import ComoNosVen from '../componentes/ComoNosVen.vue'
import HistorialActividad from '../componentes/HistorialActividad.vue'
import CampoImagen from '../componentes/CampoImagen.vue'
import { entradasService } from '@/services/admin/entradasService'
import { categoriasService } from '@/services/admin/categoriasService'
import { idiomasService } from '@/services/admin/idiomasService'
import { mensajeError, erroresCampos } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'
import { useCambiosSinGuardar } from '../composables/useCambiosSinGuardar'
import { ESTADOS_ENTRADA, URL_WEB, fechaHora } from '../utilidades'
import { usePanelSesion } from '@/stores/panelSesion'

const route = useRoute()
const router = useRouter()
const avisos = useAvisos()

const esNueva = computed(() => !route.params.id)
const sesion = usePanelSesion()
const urlBase = URL_WEB
const SEO_VACIO = { meta_titulo: '', meta_descripcion: '', palabra_clave: '', imagen_og: '', canonical: '', noindex: false }

const form = reactive({
  idioma: 'es', titulo: '', slug: '', contenido: '', extracto: '', imagen_destacada: '',
  categoria_id: null, estado: 'borrador', publicada_en: '', destacada: false, seo: { ...SEO_VACIO },
})
const entrada = ref(null)
const traducciones = ref([])
const categorias = ref([])
const idiomas = ref([])
const cargando = ref(!esNueva.value)
const guardando = ref(false)
const error = ref('')
const errores = ref({})
const editandoSlug = ref(false)
const instantanea = ref('')

const hayCambios = computed(() => JSON.stringify(form) !== instantanea.value)
useCambiosSinGuardar(() => hayCambios.value && !guardando.value)

const slugificar = t => (t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
  .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 200)
// Mientras sea borrador sin URL fijada, la URL sigue al título (como en WordPress)
const slugVisible = computed(() => form.slug || slugificar(form.titulo))
const programada = computed(() => form.publicada_en && new Date(form.publicada_en) > new Date())
const estadoVisible = computed(() => (form.estado === 'publicada' && programada.value ? 'programada' : form.estado))
const textoPlano = computed(() => (form.contenido || '').replace(/<[^>]+>/g, ' '))
const erroresSeo = computed(() => Object.fromEntries(Object.entries(errores.value).filter(([k]) => k.startsWith('seo.')).map(([k, v]) => [k.slice(4), v])))
// Permisos que manda el backend (EntradaPolicy); en una entrada nueva, según el rol
const puedeAprobar = computed(() => entrada.value?.permisos?.aprobar ?? sesion.esAdmin)
const soloLectura = computed(() => !!entrada.value && entrada.value.permisos?.editar === false)
const nombreIdioma = c => idiomas.value.find(i => i.codigo === c)?.nombre ?? c
const traduccion = c => traducciones.value.find(t => t.idioma === c)

// datetime-local trabaja en hora local sin zona: «2026-11-09T10:30»
const aLocal = iso => (iso ? new Date(new Date(iso).getTime() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16) : '')

// Sube tras cada guardado o cambio de estado: vuelve a cargar el historial
const versionHistorial = ref(0)

function aplicar(data, extra = {}) {
  entrada.value = { autor: entrada.value?.autor, aprobada_por: entrada.value?.aprobada_por, ...data }
  versionHistorial.value++
  Object.assign(form, {
    idioma: data.idioma, titulo: data.titulo, slug: data.slug, contenido: data.contenido ?? '', extracto: data.extracto ?? '',
    imagen_destacada: data.imagen_destacada ?? '', categoria_id: data.categoria_id, estado: data.estado === 'programada' ? 'publicada' : data.estado,
    publicada_en: aLocal(data.publicada_en), destacada: data.destacada, seo: { ...SEO_VACIO, ...(data.seo ?? {}) },
  })
  if (extra.traducciones) traducciones.value = extra.traducciones
  editandoSlug.value = false
  instantanea.value = JSON.stringify(form)
}

async function cargar() {
  error.value = ''
  cargando.value = true
  try {
    const data = await entradasService.ver(route.params.id)
    aplicar(data, { traducciones: data.traducciones })
  } catch (e) {
    error.value = mensajeError(e, 'No se ha podido cargar la entrada.')
  } finally {
    cargando.value = false
  }
}

onMounted(async () => {
  categoriasService.listar().then(r => { categorias.value = r.data }).catch(() => {})
  try {
    const r = await idiomasService.listar()
    idiomas.value = r.data
    if (esNueva.value) form.idioma = route.query.idioma || r.data.find(i => i.por_defecto)?.codigo || 'es'
  } catch { /* con un solo idioma no hace falta */ }
  if (esNueva.value) instantanea.value = JSON.stringify(form)
  else cargar()
})

async function guardar(estado = form.estado) {
  guardando.value = true
  errores.value = {}
  const datos = {
    ...form,
    estado,
    slug: form.slug ? slugificar(form.slug) : null,
    publicada_en: form.publicada_en ? new Date(form.publicada_en).toISOString() : null,
    imagen_destacada: form.imagen_destacada || null,
  }
  try {
    const data = esNueva.value ? await entradasService.crear(datos) : await entradasService.actualizar(entrada.value.id, datos)
    aplicar(data)
    avisos.ok({
      publicada: data.estado === 'programada' ? 'Aprobada: se publicará en su fecha.' : 'Publicada en la web.',
      pendiente: 'Enviada a revisión: un administrador la aprobará.',
      borrador: 'Borrador guardado.',
    }[estado])
    if (esNueva.value) router.replace({ name: 'panel-entrada', params: { id: data.id } })
  } catch (e) {
    errores.value = erroresCampos(e)
    avisos.error(mensajeError(e, 'No se ha podido guardar la entrada.'))
  } finally {
    guardando.value = false
  }
}

async function devolver() {
  const nota = window.prompt('¿Qué tiene que cambiar el autor? (lo verá en la entrada)')
  if (!nota?.trim()) return
  try {
    aplicar(await entradasService.devolver(entrada.value.id, nota.trim()))
    avisos.ok('Entrada devuelta al autor como borrador.')
  } catch (e) { avisos.error(mensajeError(e)) }
}

async function retirar() {
  if (!window.confirm('¿Retirar esta entrada de la web? No se borra: vuelve a borrador.')) return
  try {
    aplicar(await entradasService.retirar(entrada.value.id))
    avisos.ok('Entrada retirada de la web.')
  } catch (e) { avisos.error(mensajeError(e)) }
}

async function aPapelera() {
  if (!window.confirm('¿Mover esta entrada a la papelera? Podrás restaurarla desde Entradas → Papelera.')) return
  try {
    await entradasService.aPapelera(entrada.value.id)
    instantanea.value = JSON.stringify(form)
    avisos.ok('Entrada movida a la papelera.')
    router.push({ name: 'panel-entradas' })
  } catch (e) { avisos.error(mensajeError(e)) }
}

async function traducir(idioma) {
  if (hayCambios.value && !window.confirm('Tienes cambios sin guardar. ¿Crear la traducción igualmente? (se copia lo último guardado)')) return
  try {
    const copia = await entradasService.traducir(entrada.value.id, idioma)
    instantanea.value = JSON.stringify(form)
    avisos.ok(`Borrador en ${nombreIdioma(idioma)} creado: tradúcelo y publícalo.`)
    router.push({ name: 'panel-entrada', params: { id: copia.id } })
  } catch (e) { avisos.error(mensajeError(e)) }
}
</script>
