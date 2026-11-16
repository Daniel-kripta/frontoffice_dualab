<template>
  <article>
    <section class="bg-primary-700 py-12 sm:py-16 px-4 sm:px-6 relative overflow-hidden">
      <div v-if="entrada?.imagen_destacada" class="absolute inset-0 bg-cover bg-center opacity-15"
           :style="{ backgroundImage: `url('${entrada.imagen_destacada}')` }" />
      <div class="relative max-w-3xl mx-auto">
        <RouterLink :to="{ name: 'noticias' }"
                    class="inline-flex items-center gap-1.5 text-white/70 hover:text-white text-sm mb-6 transition-colors">
          <ChevronLeftIcon class="w-4 h-4" />
          Todas las noticias
        </RouterLink>
        <template v-if="entrada">
          <div class="flex flex-wrap items-center gap-3 mb-4">
            <span v-if="entrada.categoria" class="bg-white/20 text-white text-xs font-bold px-3 py-1 rounded-full tracking-wider uppercase">
              {{ entrada.categoria.nombre }}
            </span>
            <span class="text-sm text-white/75">{{ fechaCorta(entrada.publicada_en) }} · {{ entrada.minutos_lectura }} min de lectura</span>
          </div>
          <h1 class="text-2xl sm:text-4xl font-extrabold text-white leading-tight break-words">{{ entrada.titulo }}</h1>
          <p v-if="entrada.extracto" class="text-white/80 text-base sm:text-lg mt-4">{{ entrada.extracto }}</p>
        </template>
        <div v-else-if="cargando" class="space-y-3 animate-pulse" aria-busy="true">
          <div class="h-4 w-40 bg-white/20 rounded" /><div class="h-9 w-3/4 bg-white/20 rounded" />
        </div>
      </div>
    </section>

    <section class="bg-white py-10 sm:py-14 px-4 sm:px-6">
      <div class="max-w-3xl mx-auto">
        <div v-if="noEncontrada" class="text-center py-10">
          <p class="text-gray-600">Esta noticia no existe o ya no está publicada.</p>
          <RouterLink :to="{ name: 'noticias' }" class="btn-pill-outline mt-6">Ver todas las noticias</RouterLink>
        </div>
        <template v-else-if="entrada">
          <img v-if="entrada.imagen_destacada" :src="entrada.imagen_destacada" :alt="entrada.titulo"
               class="w-full max-h-[28rem] object-cover rounded-2xl mb-8" />
          <!-- El backend ya purifica el HTML (HTMLPurifier); DOMPurify es la segunda barrera -->
          <div class="contenido-entrada" v-html="contenidoSeguro" />
        </template>
      </div>
    </section>

    <CtaTransforma @agendar="$router.push({ name: 'contacto' })" />
  </article>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import DOMPurify from 'dompurify'
import { ChevronLeftIcon } from '@heroicons/vue/24/outline'
import CtaTransforma from '@/components/common/CtaTransforma.vue'
import { webService } from '@/services/webService'
import { useConfiguracionWeb, cargarConfiguracionWeb } from '@/composables/useConfiguracionWeb'
import { useSeo } from '@/composables/useSeo'
import { fechaCorta } from '@/utils/fechas'

const route = useRoute()
const { idioma } = useConfiguracionWeb()
const { aplicar } = useSeo()

const entrada = ref(null)
const cargando = ref(true)
const noEncontrada = ref(false)

const contenidoSeguro = computed(() => DOMPurify.sanitize(entrada.value?.contenido ?? '', {
  ALLOWED_URI_REGEXP: /^(?:https?:|mailto:|tel:|\/|#)/i,
}))

async function cargar() {
  cargando.value = true
  noEncontrada.value = false
  await cargarConfiguracionWeb()
  try {
    entrada.value = await webService.entrada(route.params.slug, route.query.idioma || idioma.value)
    aplicar({
      titulo: entrada.value.titulo,
      seo: {
        ...entrada.value.seo,
        meta_descripcion: entrada.value.seo?.meta_descripcion || entrada.value.extracto,
        imagen_og: entrada.value.seo?.imagen_og || entrada.value.imagen_destacada,
        tipo: 'article',
      },
    })
  } catch {
    entrada.value = null
    noEncontrada.value = true
    aplicar({ titulo: 'Noticia no encontrada', seo: { noindex: true } })
  } finally {
    cargando.value = false
  }
}

watch(() => route.params.slug, cargar, { immediate: true })
</script>

<style scoped>
/* Estilos del HTML que produce CKEditor (sin plugin de tipografía en el proyecto) */
.contenido-entrada { color: #374151; line-height: 1.75; overflow-wrap: anywhere; }
.contenido-entrada :deep(h2) { font-family: var(--fuente-titulos); font-weight: 700; font-size: 1.5rem; color: rgb(var(--c-noche)); margin: 2rem 0 0.75rem; }
.contenido-entrada :deep(h3) { font-family: var(--fuente-titulos); font-weight: 700; font-size: 1.25rem; color: rgb(var(--c-noche)); margin: 1.75rem 0 0.5rem; }
.contenido-entrada :deep(h4) { font-weight: 700; color: rgb(var(--c-noche)); margin: 1.5rem 0 0.5rem; }
.contenido-entrada :deep(p) { margin: 0 0 1.1rem; }
.contenido-entrada :deep(a) { color: #3072AA; text-decoration: underline; }
.contenido-entrada :deep(ul) { list-style: disc; padding-left: 1.5rem; margin: 0 0 1.1rem; }
.contenido-entrada :deep(ol) { list-style: decimal; padding-left: 1.5rem; margin: 0 0 1.1rem; }
.contenido-entrada :deep(blockquote) { border-left: 4px solid #FF8920; padding: 0.25rem 0 0.25rem 1rem; color: #4B5563; font-style: italic; margin: 1.5rem 0; }
.contenido-entrada :deep(img) { max-width: 100%; height: auto; border-radius: 1rem; }
.contenido-entrada :deep(figure) { margin: 1.5rem 0; }
.contenido-entrada :deep(figcaption) { font-size: 0.8125rem; color: #6B7280; text-align: center; margin-top: 0.5rem; }
.contenido-entrada :deep(table) { width: 100%; border-collapse: collapse; display: block; overflow-x: auto; margin: 1.5rem 0; }
.contenido-entrada :deep(th), .contenido-entrada :deep(td) { border: 1px solid #E5E7EB; padding: 0.5rem 0.75rem; text-align: left; }
.contenido-entrada :deep(th) { background: #F4F7FA; font-weight: 600; }
.contenido-entrada :deep(pre) { background: #17283E; color: #fff; padding: 1rem; border-radius: 0.75rem; overflow-x: auto; }
</style>
