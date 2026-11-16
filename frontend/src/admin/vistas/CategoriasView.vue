<template>
  <CabeceraPanel antetitulo="Entradas" titulo="Categorías" subtitulo="Agrupan las noticias del blog. En la web, el visitante puede filtrar por ellas."
                 :volver="{ name: 'panel-entradas' }" volver-label="Entradas" />

  <div class="grid gap-6 lg:grid-cols-[22rem_1fr]">
    <!-- Formulario: nueva o edición -->
    <form class="gw-tarjeta h-fit space-y-4 p-5" @submit.prevent="guardar">
      <h2 class="font-heading text-base font-bold text-azul-noche">{{ editando ? 'Editar categoría' : 'Nueva categoría' }}</h2>
      <div>
        <label for="gw-cat-nombre" class="gw-etiqueta">Nombre</label>
        <input id="gw-cat-nombre" ref="campoNombre" v-model.trim="form.nombre" type="text" maxlength="80" required class="gw-campo" placeholder="p. ej. Casos de éxito" />
        <p v-if="errores.nombre" class="gw-error">{{ errores.nombre }}</p>
      </div>
      <fieldset>
        <legend class="gw-etiqueta">Color de la etiqueta</legend>
        <div class="flex flex-wrap gap-2">
          <label v-for="(c, clave) in COLORES_CATEGORIA" :key="clave" class="cursor-pointer">
            <input v-model="form.color" type="radio" name="color" :value="clave" class="peer sr-only" />
            <span class="gw-chip border-2 border-transparent peer-checked:border-azul-noche peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-administraciones-dark" :class="c.clase">
              {{ c.nombre }}
            </span>
          </label>
        </div>
      </fieldset>
      <div class="flex flex-wrap gap-2">
        <button type="submit" class="gw-accion-principal" :disabled="guardando || !form.nombre">{{ editando ? 'Guardar' : 'Añadir categoría' }}</button>
        <button v-if="editando" type="button" class="gw-accion-secundaria" @click="nueva">Cancelar</button>
      </div>
    </form>

    <div class="gw-tarjeta overflow-hidden">
      <ul v-if="cargando" class="divide-y divide-gray-100"><li v-for="n in 4" :key="n" class="p-4"><span class="block h-8 animate-pulse rounded bg-gray-100" /></li></ul>
      <p v-else-if="!categorias.length" class="p-8 text-center text-sm text-texto-secundario">Aún no hay categorías. Crea la primera con el formulario.</p>
      <ul v-else class="divide-y divide-gray-100">
        <li v-for="c in categorias" :key="c.id" class="flex flex-wrap items-center gap-3 p-4">
          <span class="gw-chip" :class="claseCategoria(c.color)">{{ c.nombre }}</span>
          <span class="text-xs text-texto-secundario">{{ c.entradas }} {{ c.entradas === 1 ? 'entrada' : 'entradas' }} · /{{ c.slug }}</span>
          <div class="ml-auto flex gap-2">
            <button type="button" class="gw-accion-secundaria !h-9" @click="editar(c)">Editar</button>
            <button type="button" class="gw-accion-icono !h-9 !w-9 hover:!border-red-200 hover:!text-red-600" :aria-label="`Eliminar la categoría ${c.nombre}`" @click="eliminar(c)">
              <TrashIcon class="h-4 w-4" aria-hidden="true" />
            </button>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { TrashIcon } from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import { categoriasService } from '@/services/admin/categoriasService'
import { mensajeError, erroresCampos } from '@/services/admin/panelApi'
import { useAvisos } from '../composables/useAvisos'
import { COLORES_CATEGORIA, claseCategoria } from '@/data/coloresCategoria'

const avisos = useAvisos()
const categorias = ref([])
const cargando = ref(true)
const guardando = ref(false)
const editando = ref(null)
const errores = ref({})
const form = reactive({ nombre: '', color: 'centros' })
const campoNombre = ref(null)

async function cargar() {
  try { categorias.value = (await categoriasService.listar()).data }
  catch (e) { avisos.error(mensajeError(e)) }
  finally { cargando.value = false }
}
onMounted(cargar)

function nueva() { editando.value = null; Object.assign(form, { nombre: '', color: 'centros' }); errores.value = {} }
function editar(c) { editando.value = c; Object.assign(form, { nombre: c.nombre, color: c.color }); campoNombre.value?.focus() }

async function guardar() {
  guardando.value = true
  errores.value = {}
  try {
    if (editando.value) await categoriasService.actualizar(editando.value.id, form)
    else await categoriasService.crear(form)
    avisos.ok(editando.value ? 'Categoría guardada.' : 'Categoría añadida.')
    nueva()
    cargar()
  } catch (e) {
    errores.value = erroresCampos(e)
    avisos.error(mensajeError(e))
  } finally {
    guardando.value = false
  }
}

async function eliminar(c) {
  const aviso = c.entradas ? ` Sus ${c.entradas} entradas se quedarán sin categoría.` : ''
  if (!window.confirm(`¿Eliminar la categoría «${c.nombre}»?${aviso}`)) return
  try {
    await categoriasService.eliminar(c.id)
    avisos.ok('Categoría eliminada.')
    if (editando.value?.id === c.id) nueva()
    cargar()
  } catch (e) { avisos.error(mensajeError(e)) }
}
</script>
