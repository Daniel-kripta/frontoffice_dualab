<template>
  <!-- Header -->
  <section class="bg-primary-700 py-16 px-6 relative overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center opacity-10"
         :style="{ backgroundImage: `url('${p.imagen('hero.imagen_fondo', 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=1200&q=60')}')` }" />
    <div class="relative max-w-4xl mx-auto">
      <RouterLink :to="{ name: 'home' }"
                  class="inline-flex items-center gap-1.5 text-white/70 hover:text-white text-sm mb-6 transition-colors">
        <ChevronLeftIcon class="w-4 h-4" />
        Volver al inicio
      </RouterLink>
      <span class="inline-block bg-white/20 text-white text-xs font-semibold px-3 py-1 rounded-full mb-4 tracking-wider">
        {{ p.t('hero.antetitulo', 'ACTUALIDAD') }}
      </span>
      <h1 class="text-3xl md:text-4xl font-extrabold text-white mb-3">{{ p.t('hero.titulo', 'Noticias') }}</h1>
      <p class="text-white/75 text-base max-w-xl">
        {{ p.t('hero.subtitulo', 'Novedades, casos de éxito y eventos de DuaLab y el ecosistema de la formación profesional dual.') }}
      </p>
    </div>
  </section>

  <section class="bg-gray-50 py-14 px-4 sm:px-6">
    <div class="max-w-4xl mx-auto">

      <!-- Filtro por categoría (solo si hay entradas publicadas en alguna) -->
      <div v-if="categorias.length" class="flex flex-wrap gap-2 mb-6" role="group" aria-label="Filtrar por categoría">
        <button type="button" class="px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider transition-colors"
                :class="!categoria ? 'bg-azul-noche text-white' : 'bg-white text-gray-600 border border-gray-200 hover:border-gray-300'"
                :aria-pressed="!categoria" @click="filtrar(null)">Todas</button>
        <button v-for="c in categorias" :key="c.slug" type="button"
                class="px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider transition-colors"
                :class="categoria === c.slug ? 'bg-azul-noche text-white' : 'bg-white text-gray-600 border border-gray-200 hover:border-gray-300'"
                :aria-pressed="categoria === c.slug" @click="filtrar(c.slug)">{{ c.nombre }}</button>
      </div>

      <!-- Cargando -->
      <div v-if="cargando && !entradas.length" class="space-y-5" aria-busy="true">
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden animate-pulse">
          <div class="h-64 bg-gray-200" /><div class="p-6 space-y-3"><div class="h-4 w-1/3 bg-gray-200 rounded" /><div class="h-6 w-3/4 bg-gray-200 rounded" /></div>
        </div>
      </div>

      <!-- Entradas publicadas desde el panel de gestión web -->
      <template v-else-if="entradas.length">
        <RouterLink v-if="principal" :to="{ name: 'noticia-detalle', params: { slug: principal.slug } }"
                    class="block bg-white rounded-2xl overflow-hidden border border-gray-200 mb-6 hover:shadow-lg transition-shadow">
          <img v-if="principal.imagen_destacada" :src="principal.imagen_destacada" :alt="principal.titulo"
               class="w-full h-48 sm:h-64 object-cover" />
          <div class="p-5 sm:p-6">
            <div class="flex flex-wrap items-center gap-3 mb-3">
              <span v-if="principal.categoria" :class="claseCategoria(principal.categoria.color)"
                    class="text-xs font-bold px-2.5 py-1 rounded-full tracking-wider uppercase">{{ principal.categoria.nombre }}</span>
              <span class="text-xs text-gray-500">{{ fechaCorta(principal.publicada_en) }} · {{ principal.minutos_lectura }} min</span>
            </div>
            <h2 class="text-xl font-bold text-azul-noche mb-2 break-words">{{ principal.titulo }}</h2>
            <p v-if="principal.extracto" class="text-sm text-gray-500 leading-relaxed mb-4">{{ principal.extracto }}</p>
            <span class="btn-primary">Leer noticia →</span>
          </div>
        </RouterLink>

        <div class="grid md:grid-cols-2 gap-5">
          <RouterLink v-for="n in secundarias" :key="n.slug" :to="{ name: 'noticia-detalle', params: { slug: n.slug } }"
                      class="bg-white rounded-2xl overflow-hidden border border-gray-200 hover:shadow-md transition-shadow flex flex-col">
            <img v-if="n.imagen_destacada" :src="n.imagen_destacada" :alt="n.titulo" class="w-full h-44 object-cover" loading="lazy" />
            <div class="p-5 flex flex-col flex-1">
              <div class="flex flex-wrap items-center gap-2 mb-2">
                <span v-if="n.categoria" :class="claseCategoria(n.categoria.color)"
                      class="text-xs font-bold px-2 py-0.5 rounded-full tracking-wider uppercase">{{ n.categoria.nombre }}</span>
                <span class="text-xs text-gray-500">{{ fechaCorta(n.publicada_en) }} · {{ n.minutos_lectura }} min</span>
              </div>
              <h3 class="font-bold text-azul-noche text-sm mb-2 leading-snug flex-1 break-words">{{ n.titulo }}</h3>
              <p v-if="n.extracto" class="text-xs text-gray-500 leading-relaxed mb-4">{{ n.extracto }}</p>
              <span class="btn-outline !text-xs !py-1.5 self-start">Leer más →</span>
            </div>
          </RouterLink>
        </div>

        <div v-if="hayMas" class="text-center mt-8">
          <button type="button" class="btn-pill-outline" :disabled="cargando" @click="cargar(pagina + 1)">
            {{ cargando ? 'Cargando…' : 'Ver más noticias' }}
          </button>
        </div>
      </template>

      <!-- Con un filtro activo y sin resultados -->
      <div v-else-if="categoria" class="bg-white rounded-2xl border border-gray-200 p-8 text-center">
        <p class="text-gray-600">No hay noticias en esta categoría todavía.</p>
        <button type="button" class="btn-pill-outline mt-4" @click="filtrar(null)">Ver todas</button>
      </div>

      <!-- Sin entradas publicadas todavía: se mantienen las noticias de ejemplo del diseño
           hasta que se publique la primera desde el panel (/gestion-web/entradas). -->
      <template v-else>
        <div class="bg-white rounded-2xl overflow-hidden border border-gray-200 mb-6">
          <img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=900&q=70" alt="Noticia destacada" class="w-full h-48 sm:h-64 object-cover" />
          <div class="p-5 sm:p-6">
            <div class="flex items-center gap-3 mb-3">
              <span class="bg-primary-100 text-primary-700 text-xs font-bold px-2.5 py-1 rounded-full tracking-wider uppercase">Novedad</span>
              <span class="text-xs text-gray-500">19 jun 2026 · 4 min</span>
            </div>
            <h2 class="text-xl font-bold text-azul-noche mb-2">DuaLab revoluciona la FP Dual con IA: retos académicos generados automáticamente</h2>
            <p class="text-sm text-gray-500 leading-relaxed">
              Nuestra plataforma transforma necesidades empresariales reales en retos académicos alineados al currículo oficial mediante inteligencia artificial, reduciendo el tiempo de diseño de un reto de días a minutos.
            </p>
          </div>
        </div>
        <div class="grid md:grid-cols-2 gap-5">
          <div v-for="n in noticiasEjemplo" :key="n.id" class="bg-white rounded-2xl overflow-hidden border border-gray-200 flex flex-col">
            <img :src="n.imagen" :alt="n.titulo" class="w-full h-44 object-cover" loading="lazy" />
            <div class="p-5 flex flex-col flex-1">
              <div class="flex items-center gap-2 mb-2">
                <span :class="n.badgeClass" class="text-xs font-bold px-2 py-0.5 rounded-full tracking-wider uppercase">{{ n.categoria }}</span>
                <span class="text-xs text-gray-500">{{ n.fecha }}</span>
              </div>
              <h3 class="font-bold text-azul-noche text-sm mb-2 leading-snug flex-1">{{ n.titulo }}</h3>
              <p class="text-xs text-gray-500 leading-relaxed">{{ n.extracto }}</p>
            </div>
          </div>
        </div>
      </template>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { ChevronLeftIcon } from '@heroicons/vue/24/outline'
import { usePagina } from '@/composables/usePagina'
import { useConfiguracionWeb } from '@/composables/useConfiguracionWeb'
import { webService } from '@/services/webService'
import { claseCategoria } from '@/data/coloresCategoria'
import { fechaCorta } from '@/utils/fechas'

// Cabecera y SEO editables desde el panel (config/cms_paginas.php → noticias)
const p = usePagina('noticias', { titulo: 'Noticias' })
const { idioma } = useConfiguracionWeb()

const entradas = ref([])
const categorias = ref([])
const categoria = ref(null)
const pagina = ref(1)
const hayMas = ref(false)
const cargando = ref(true)

const principal = computed(() => entradas.value[0])
const secundarias = computed(() => entradas.value.slice(1))

async function cargar(n = 1) {
  cargando.value = true
  try {
    const data = await webService.entradas({ idioma: idioma.value, categoria: categoria.value || undefined, page: n, per_page: 9 })
    entradas.value = n === 1 ? data.data : [...entradas.value, ...data.data]
    pagina.value = data.meta.current_page
    hayMas.value = data.meta.current_page < data.meta.last_page
  } catch {
    if (n === 1) entradas.value = []
  } finally {
    cargando.value = false
  }
}

function filtrar(slug) {
  categoria.value = slug
  cargar(1)
}

watch(idioma, () => cargar(1), { immediate: true })
webService.categorias().then(c => { categorias.value = c }).catch(() => {})

const noticiasEjemplo = [
  {
    id: 1,
    imagen: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=600&q=70',
    categoria: 'Caso de éxito',
    badgeClass: 'bg-emerald-100 text-emerald-700',
    fecha: '12 jun 2026 · 3 min',
    titulo: '150 alumnos de FP resuelven retos reales de empresa en una semana',
    extracto: 'Tres centros educativos colaboraron con TechCorp Solutions para que su alumnado de Informática afrontara un reto de automatización real con resultados sorprendentes.',
  },
  {
    id: 2,
    imagen: 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=600&q=70',
    categoria: 'Evento',
    badgeClass: 'bg-amber-100 text-amber-700',
    fecha: '5 jun 2026 · 2 min',
    titulo: 'DuaLab en el Congreso Nacional de FP: ponencia sobre talento emergente',
    extracto: 'Presentamos nuestra metodología de retos B2B ante más de 800 profesionales de la formación profesional en el mayor congreso de FP dual del año.',
  },
  {
    id: 3,
    imagen: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=600&q=70',
    categoria: 'Novedad',
    badgeClass: 'bg-primary-100 text-primary-700',
    fecha: '28 may 2026 · 3 min',
    titulo: 'Nueva familia profesional: Administración y Finanzas se une a la plataforma',
    extracto: 'Ampliamos nuestro catálogo de retos con la incorporación de la familia de Administración y Finanzas, con cinco nuevos microretos diseñados junto a empresas del sector.',
  },
  {
    id: 4,
    imagen: 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&q=70',
    categoria: 'Entrevista',
    badgeClass: 'bg-violet-100 text-violet-700',
    fecha: '20 may 2026 · 5 min',
    titulo: '"DuaLab ha cambiado cómo entendemos la colaboración empresa-centro educativo"',
    extracto: 'Hablamos con la directora de RRHH de InnovaGroup sobre cómo los retos académicos les han ayudado a descubrir perfiles que no habrían encontrado por los canales tradicionales.',
  },
]
</script>
