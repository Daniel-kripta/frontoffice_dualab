<!-- SEO de una página o entrada (lo que hacía la caja de All in One SEO):
     semáforo, vista previa en Google, título y descripción con contador, palabra clave con
     comprobaciones, y en «Avanzado» imagen para redes, canonical y noindex. -->
<template>
  <div class="space-y-5">
    <!-- Semáforo (como el de Yoast): se recalcula mientras se escribe -->
    <div class="rounded-xl border p-4" :class="semaforo.caja" role="status">
      <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-azul-noche">
        <span class="h-3 w-3 shrink-0 rounded-full" :class="semaforo.punto" aria-hidden="true" />
        SEO: {{ semaforo.texto }}
        <span class="font-normal text-texto-secundario">· {{ revisiones.filter(r => r.ok).length }} de {{ revisiones.length }} comprobaciones</span>
      </p>
      <ul v-if="pendientes.length" class="mt-2 space-y-1 text-sm text-gray-700">
        <li v-for="r in pendientes" :key="r.texto" class="flex gap-2"><span aria-hidden="true">·</span><span>{{ r.texto }}</span></li>
      </ul>
    </div>

    <!-- Vista previa en Google -->
    <div>
      <p class="gw-etiqueta">Vista previa en Google</p>
      <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="truncate text-xs text-[#4d5156]">{{ urlVisible }}</p>
        <p class="mt-1 line-clamp-2 break-words text-lg leading-snug text-[#1a0dab]">{{ tituloFinal }}</p>
        <p class="mt-1 line-clamp-2 break-words text-sm text-[#4d5156]">{{ descripcionFinal || 'Google mostrará un fragmento del texto de la página.' }}</p>
      </div>
    </div>

    <div>
      <label :for="`${id}-titulo`" class="gw-etiqueta">Título SEO</label>
      <input :id="`${id}-titulo`" :value="modelValue.meta_titulo" type="text" maxlength="120" class="gw-campo"
             :placeholder="tituloPorDefecto" :aria-describedby="`${id}-titulo-ayuda`" @input="cambiar('meta_titulo', $event.target.value)" />
      <Medidor :id="`${id}-titulo-ayuda`" :largo="tituloFinal.length" :min="30" :max="60"
               texto="Entre 30 y 60 caracteres. Vacío = el título de la página y el nombre del sitio." />
      <p v-if="errores.meta_titulo" class="gw-error">{{ errores.meta_titulo }}</p>
    </div>

    <div>
      <label :for="`${id}-descripcion`" class="gw-etiqueta">Meta descripción</label>
      <textarea :id="`${id}-descripcion`" :value="modelValue.meta_descripcion" rows="3" maxlength="320" class="gw-campo"
                :placeholder="descripcionPorDefecto || 'Resumen que aparece bajo el título en Google'"
                :aria-describedby="`${id}-descripcion-ayuda`" @input="cambiar('meta_descripcion', $event.target.value)" />
      <Medidor :id="`${id}-descripcion-ayuda`" :largo="(modelValue.meta_descripcion || '').length" :min="120" :max="160"
               texto="Entre 120 y 160 caracteres." />
      <p v-if="errores.meta_descripcion" class="gw-error">{{ errores.meta_descripcion }}</p>
    </div>

    <div>
      <label :for="`${id}-clave`" class="gw-etiqueta">Palabra clave principal</label>
      <input :id="`${id}-clave`" :value="modelValue.palabra_clave" type="text" maxlength="80" class="gw-campo"
             placeholder="p. ej. formación dual" @input="cambiar('palabra_clave', $event.target.value)" />
      <ul v-if="clave" class="mt-3 space-y-1.5" aria-label="Comprobaciones de la palabra clave">
        <li v-for="c in comprobaciones" :key="c.texto" class="flex items-start gap-2 text-sm">
          <CheckCircleIcon v-if="c.ok" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" aria-hidden="true" />
          <ExclamationTriangleIcon v-else class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" aria-hidden="true" />
          <span :class="c.ok ? 'text-gray-700' : 'text-amber-800'">{{ c.ok ? 'Bien:' : 'Mejorable:' }} {{ c.texto }}</span>
        </li>
      </ul>
      <p v-else class="gw-ayuda">Sirve para revisar si el título, la descripción, la URL y el texto la incluyen. No se publica.</p>
    </div>

    <details class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
      <summary class="cursor-pointer text-sm font-semibold text-azul-noche">Avanzado: redes sociales e indexación</summary>
      <div class="mt-4 space-y-5">
        <div>
          <p class="gw-etiqueta">Imagen al compartir en redes (Open Graph)</p>
          <CampoImagen :model-value="modelValue.imagen_og || ''" :original="imagenPorDefecto" titulo-selector="Imagen para redes sociales"
                       @update:model-value="v => cambiar('imagen_og', v)" />
          <p class="gw-ayuda">Recomendado 1200 × 630 px. Vacío = la imagen por defecto de Ajustes → SEO.</p>
        </div>
        <div>
          <label :for="`${id}-canonical`" class="gw-etiqueta">URL canónica</label>
          <input :id="`${id}-canonical`" :value="modelValue.canonical" type="url" class="gw-campo" placeholder="https://…"
                 @input="cambiar('canonical', $event.target.value)" />
          <p class="gw-ayuda">Solo si este contenido está publicado también en otra dirección. Normalmente vacío.</p>
          <p v-if="errores.canonical" class="gw-error">{{ errores.canonical }}</p>
        </div>
        <label class="flex items-start gap-3">
          <input type="checkbox" role="switch" class="gw-interruptor mt-0.5" :checked="modelValue.noindex"
                 @change="cambiar('noindex', $event.target.checked)" />
          <span>
            <span class="block text-sm font-semibold text-azul-noche">Ocultar en buscadores (noindex)</span>
            <span class="gw-ayuda block">Google no la mostrará en resultados y no saldrá en el sitemap.</span>
          </span>
        </label>
      </div>
    </details>
  </div>
</template>

<script setup>
import { computed, h } from 'vue'
import { CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import CampoImagen from './CampoImagen.vue'

const props = defineProps({
  modelValue: { type: Object, required: true },
  tituloPorDefecto: { type: String, default: '' },
  descripcionPorDefecto: { type: String, default: '' },
  imagenPorDefecto: { type: String, default: '' },
  urlVisible: { type: String, default: '' },
  slug: { type: String, default: '' },
  texto: { type: String, default: '' }, // contenido en texto plano, para las comprobaciones
  errores: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['update:modelValue'])
const id = `gw-seo-${Math.random().toString(36).slice(2, 8)}`

const cambiar = (campo, valor) => emit('update:modelValue', { ...props.modelValue, [campo]: valor })

const tituloFinal = computed(() => props.modelValue.meta_titulo || props.tituloPorDefecto)
const descripcionFinal = computed(() => props.modelValue.meta_descripcion || props.descripcionPorDefecto)

const normalizar = t => (t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
const clave = computed(() => normalizar(props.modelValue.palabra_clave).trim())

const comprobaciones = computed(() => {
  const k = clave.value
  const enSlug = normalizar(props.slug).includes(k.replace(/\s+/g, '-'))
  const lista = [
    { ok: normalizar(tituloFinal.value).includes(k), texto: 'la palabra clave aparece en el título SEO' },
    { ok: normalizar(descripcionFinal.value).includes(k), texto: 'la palabra clave aparece en la meta descripción' },
  ]
  if (props.slug) lista.push({ ok: enSlug, texto: 'la palabra clave aparece en la URL' })
  if (props.texto) lista.push({ ok: normalizar(props.texto).includes(k), texto: 'la palabra clave aparece en el texto' })
  return lista
})

// Semáforo: todas las comprobaciones en una nota. Rojo si está oculta o le falta lo básico.
const revisiones = computed(() => {
  const titulo = tituloFinal.value.length
  const descripcion = (props.modelValue.meta_descripcion || '').length
  const lista = [
    { ok: !props.modelValue.noindex, basica: true, texto: 'Está oculta a los buscadores (noindex en Avanzado).' },
    { ok: titulo >= 30 && titulo <= 60, texto: `El título SEO tiene ${titulo} caracteres: mejor entre 30 y 60.` },
    { ok: descripcion > 0 || !!props.descripcionPorDefecto, basica: true, texto: 'Falta la meta descripción.' },
    { ok: descripcion === 0 ? !!props.descripcionPorDefecto : descripcion >= 120 && descripcion <= 160, texto: `La meta descripción tiene ${descripcion} caracteres: mejor entre 120 y 160.` },
    { ok: !!clave.value, texto: 'Elige una palabra clave principal para poder revisarla.' },
    // «la palabra clave aparece en el título SEO» → «La palabra clave no aparece en el título SEO.»
    ...(clave.value ? comprobaciones.value.map(c => ({ ok: c.ok, texto: `${c.texto.replace('la palabra clave aparece', 'La palabra clave no aparece')}.` })) : []),
  ]
  // Si falta la descripción, no repetir también su longitud
  return lista.filter((r, i) => !(i === 3 && !lista[2].ok))
})
const pendientes = computed(() => revisiones.value.filter(r => !r.ok))
const semaforo = computed(() => {
  const malas = pendientes.value.length
  if (pendientes.value.some(r => r.basica)) return { texto: 'falta trabajo', punto: 'bg-red-500', caja: 'border-red-200 bg-red-50/60' }
  if (malas === 0) return { texto: 'bien', punto: 'bg-emerald-500', caja: 'border-emerald-200 bg-emerald-50/60' }
  return { texto: 'mejorable', punto: 'bg-amber-500', caja: 'border-amber-200 bg-amber-50/60' }
})

// Contador con estado: corto / bien / largo (color + texto, no solo color)
const Medidor = (p) => {
  const estado = p.largo === 0 ? null : p.largo < p.min ? 'corto' : p.largo > p.max ? 'largo' : 'bien'
  const clases = { corto: 'text-amber-700', largo: 'text-amber-700', bien: 'text-emerald-700' }
  const textos = { corto: 'algo corto', largo: 'demasiado largo, Google lo cortará', bien: 'buena longitud' }
  return h('p', { id: p.id, class: 'gw-ayuda flex flex-wrap gap-x-2' }, [
    h('span', p.texto),
    estado && h('span', { class: `font-semibold ${clases[estado]}` }, `${p.largo} caracteres · ${textos[estado]}`),
  ])
}
Medidor.props = ['id', 'largo', 'min', 'max', 'texto']
</script>
