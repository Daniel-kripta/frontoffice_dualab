<template>
  <!-- Página para centros educativos (diseño DuaLab_Web_Centros). -->
  <svg width="0" height="0" class="absolute" aria-hidden="true">
    <defs>
      <!-- Misma forma orgánica que la imagen de "Retos académicos" de la home -->
      <clipPath id="centros-blob" clipPathUnits="objectBoundingBox">
        <path d="M0.06,0.42 C0.12,0.2 0.3,0.26 0.46,0.14 C0.62,0.02 0.8,-0.02 0.93,0.07 C1,0.12 1,0.28 1,0.4 L1,0.86 C1,0.95 0.96,1 0.88,1 L0.18,1 C0.07,1 0.01,0.9 0,0.76 C-0.01,0.62 0.02,0.52 0.06,0.42 Z" />
      </clipPath>
    </defs>
  </svg>

  <!-- HERO: mismo lenguaje que el de la home (azul de marca, foto de fondo
       tenue, rótulo en píldora y botones píldora blancos) -->
  <section class="relative overflow-hidden bg-primary-700 py-16 md:py-24 px-6">
    <div class="absolute inset-0 bg-cover bg-center opacity-20" :style="{ backgroundImage: `url('${p.imagen('hero.imagen_fondo', dua13)}')` }" />

    <div class="relative max-w-6xl mx-auto grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-12 items-center">
      <div>
        <span class="hero-badge inline-block bg-white/15 text-white px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest mb-6 border border-white/30">
          {{ p.t('hero.antetitulo', 'Centros educativos') }}
        </span>
        <h1 class="hero-title text-[32px] sm:text-5xl md:text-6xl font-black tracking-tighter text-white leading-[1.1] mb-5">
          {{ p.t('hero.titulo', 'La Formación Dual más conectada,') }}
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-alumnos to-administraciones">{{ p.t('hero.destacado', 'simple y eficaz') }}</span>
        </h1>
        <p v-if="p.tiene('hero.descripcion')" class="hero-desc text-white/75 text-base leading-relaxed mb-8 max-w-lg">{{ p.t('hero.descripcion') }}</p>
        <!-- Sin editar en el panel: el original con sus resaltados -->
        <p v-else class="hero-desc text-white/75 text-base leading-relaxed mb-8 max-w-lg">
          DuaLab es la plataforma que ayuda a tu centro a <strong class="text-white font-bold">gestionar la Formación Dual de forma integral</strong>,
          conectando <strong class="text-[#ffb066] font-bold">alumnado</strong>, <strong class="text-[#8fc46a] font-bold">empresas</strong>
          y tutoría, y facilitando el seguimiento, la comunicación y la
          <strong class="text-[#5fd0d0] font-bold">evaluación</strong> de todo el proceso.
        </p>
        <div class="hero-btns flex flex-wrap gap-3">
          <EnlaceCms :to="heroB1.to"
                      class="inline-flex items-center gap-2 px-6 py-3 bg-white text-primary-700 rounded-full font-black text-sm uppercase tracking-widest shadow-[0_8px_25px_rgba(0,0,0,0.2)] hover:shadow-[0_14px_35px_rgba(0,0,0,0.3)] transition-all duration-300 hover:-translate-y-0.5 active:scale-95">
            {{ heroB1.texto }}
            <ArrowRightIcon class="w-4 h-4 shrink-0" />
          </EnlaceCms>
          <EnlaceCms :to="heroB2.to"
                      class="inline-flex items-center gap-2 px-6 py-3 bg-white/10 text-white border-2 border-white/40 rounded-full font-black text-sm uppercase tracking-widest hover:bg-white/20 hover:border-white/70 transition-all duration-300 hover:-translate-y-0.5 active:scale-95 backdrop-blur-sm">
            {{ heroB2.texto }}
          </EnlaceCms>
        </div>
      </div>

      <!-- Foto desde tablet (debajo del texto; al lado en escritorio). En móvil el hero queda compacto. La rotulación manuscrita, solo en escritorio -->
      <div class="hero-img relative hidden md:block">
        <p v-if="p.tiene('hero.rotulo')" class="hidden lg:block absolute -left-6 top-4 z-10 font-hand font-semibold text-white text-3xl leading-7 -rotate-12 max-w-[11rem]" aria-hidden="true">{{ p.t('hero.rotulo') }}</p>
        <p v-else class="hidden lg:block absolute -left-6 top-4 z-10 font-hand font-semibold text-white text-3xl leading-7 -rotate-12" aria-hidden="true">
          Conectando<br>talento con<br><span class="text-alumnos">oportunidades</span><br>reales
        </p>
        <svg class="hidden lg:block absolute left-16 top-36 w-20 h-16 z-10" viewBox="0 0 100 80" fill="none" aria-hidden="true">
          <path d="M8 6 C 6 40, 40 64, 86 58" stroke="#FF8920" stroke-width="3" stroke-linecap="round" />
          <path d="M76 50 L88 58 L76 66" stroke="#FF8920" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <img :src="p.imagen('hero.imagen', dua17)" alt="Alumnado trabajando en un reto en el aula"
             class="blob-image w-full h-[340px] lg:h-[420px] object-cover" />
      </div>
    </div>
  </section>

  <!-- Franja con los cuatro colores de colectivo (igual que en la home) -->
  <div class="grid grid-cols-4 h-2" aria-hidden="true">
    <span class="bg-centros" /><span class="bg-empresas" /><span class="bg-alumnos" /><span class="bg-administraciones" />
  </div>

  <!-- VENTAJAS -->
  <section class="bg-white py-14 px-6">
    <div v-reveal class="max-w-6xl mx-auto grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-8 lg:gap-0 lg:divide-x lg:divide-gray-100">
      <div v-for="v in ventajasTextos" :key="v.title" class="flex sm:flex-col gap-4 sm:gap-3 lg:px-5 lg:first:pl-0 lg:last:pr-0">
        <span class="w-12 h-12 rounded-full flex items-center justify-center text-white shrink-0" :class="v.bg">
          <component :is="v.icon" class="w-6 h-6" />
        </span>
        <div class="min-w-0">
          <h3 class="font-heading font-bold text-[15px] text-azul-noche leading-snug">{{ v.title }}</h3>
          <p class="text-sm text-gray-500 leading-snug mt-1">{{ v.desc }}</p>
        </div>
      </div>
    </div>
  </section>

  <!-- PLATAFORMA -->
  <section class="bg-superficie py-20 px-6">
    <div class="max-w-6xl mx-auto grid lg:grid-cols-2 gap-12 items-center">
      <div v-reveal class="relative">
        <img :src="dua14" alt="Alumnado y docentes trabajando con portátiles en una sesión de DuaLab"
             class="w-full h-[300px] sm:h-[420px] lg:h-[480px] object-cover rounded-2xl" loading="lazy" />
        <div class="absolute left-4 right-4 sm:right-auto bottom-4 sm:left-6 sm:bottom-6 bg-white rounded-2xl p-4 flex items-center gap-4 shadow-[0_10px_30px_rgba(23,40,62,0.15)]">
          <span class="w-12 h-12 rounded-full bg-administraciones text-white flex items-center justify-center shrink-0">
            <ChartBarIcon class="w-6 h-6" />
          </span>
          <div class="min-w-0">
            <p class="font-heading font-extrabold text-2xl text-azul-noche leading-none">{{ p.t('plataforma.cifra', destacado.valor) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ p.t('plataforma.cifra_texto', destacado.texto) }}</p>
          </div>
        </div>
      </div>

      <div v-reveal="150">
        <p class="section-eyebrow">{{ p.t('plataforma.antetitulo', 'Plataforma DuaLab') }}</p>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">
          {{ p.t('plataforma.titulo', 'Todo lo que tu centro necesita para la') }} <span class="text-primary-500">{{ p.t('plataforma.destacado', 'Formación Dual') }}</span>
        </h2>
        <p class="text-gray-500 leading-relaxed mt-5">
          {{ p.t('plataforma.descripcion', 'Una plataforma intuitiva y completa, diseñada para equipos directivos, tutores y departamentos, que centraliza procesos, documentación y comunicación en un solo lugar.') }}
        </p>
        <ul class="mt-6 bg-white rounded-2xl divide-y divide-gray-100 shadow-[0_2px_12px_rgba(23,40,62,0.05)]">
          <li v-for="item in plataformaTextos" :key="item.label" class="flex items-center gap-4 px-4 py-3">
            <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" :class="item.soft">
              <component :is="item.icon" class="w-5 h-5" />
            </span>
            <span class="flex-1 min-w-0 font-medium text-sm text-azul-noche">{{ item.label }}</span>
            <CheckIcon class="w-4 h-4 text-administraciones shrink-0" aria-hidden="true" />
          </li>
        </ul>
      </div>
    </div>
  </section>

  <!-- CÓMO FUNCIONA -->
  <section class="bg-white py-20 px-6">
    <div class="max-w-6xl mx-auto">
      <div v-reveal class="mb-10">
        <p class="section-eyebrow">{{ p.t('pasos.antetitulo', 'Cómo funciona') }}</p>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">
          {{ p.t('pasos.titulo', 'Implanta') }} <span class="text-primary-500">{{ p.t('pasos.destacado', 'DuaLab') }}</span> {{ p.t('pasos.titulo_final', 'en tu centro en 4 pasos') }}
        </h2>
      </div>
      <ol v-reveal="100" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <li v-for="(paso, i) in pasosTextos" :key="paso.title"
            class="bg-superficie rounded-2xl p-5">
          <div class="flex items-center gap-3">
            <span class="w-11 h-11 rounded-full flex items-center justify-center text-white font-heading font-black text-lg shadow-md shrink-0" :class="paso.bg">
              {{ i + 1 }}
            </span>
            <!-- Conector hacia el siguiente paso (solo en fila completa) -->
            <span v-if="i < pasos.length - 1" class="hidden lg:flex flex-1 items-center" aria-hidden="true">
              <span class="flex-1 h-px bg-primary-200" />
              <ArrowRightIcon class="w-4 h-4 text-primary-400 -ml-1" />
            </span>
          </div>
          <h3 class="font-heading font-bold text-azul-noche mt-4">{{ paso.title }}</h3>
          <p class="text-sm text-gray-500 leading-snug mt-1">{{ paso.desc }}</p>
        </li>
      </ol>
    </div>
  </section>

  <!-- CASOS DE USO -->
  <section class="bg-superficie py-20 px-6">
    <div class="max-w-6xl mx-auto">
      <div v-reveal class="mb-10">
        <p class="section-eyebrow">{{ p.t('casos.antetitulo', 'Casos de uso') }}</p>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">
          {{ p.t('casos.titulo', 'Soluciones para cada') }} <span class="text-primary-500">{{ p.t('casos.destacado', 'realidad educativa') }}</span>
        </h2>
        <p class="text-gray-500 leading-relaxed mt-3">
          {{ p.t('casos.descripcion', 'DuaLab se adapta a las necesidades de cada centro, independientemente de su tamaño o familia profesional.') }}
        </p>
      </div>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <article v-for="(c, i) in casosTextos" :key="c.title" v-reveal="i * 100"
                 class="bg-white rounded-2xl overflow-hidden flex flex-col shadow-[0_2px_12px_rgba(23,40,62,0.05)]">
          <img :src="c.image" :alt="c.alt" class="w-full h-48 object-cover" loading="lazy" />
          <div class="p-5 flex gap-4 flex-1">
            <span class="w-11 h-11 rounded-full flex items-center justify-center shrink-0" :class="c.soft">
              <component :is="c.icon" class="w-5 h-5" />
            </span>
            <div class="min-w-0 flex flex-col">
              <h3 class="font-heading font-bold text-azul-noche">{{ c.title }}</h3>
              <p class="text-sm text-gray-500 leading-snug mt-1 flex-1">{{ c.desc }}</p>
              <EnlaceCms :to="enlaceTarjetas.to"
                          class="inline-flex items-center gap-1.5 self-start mt-2 py-2.5 text-sm font-bold text-primary-600 hover:text-primary-700 group">
                {{ enlaceTarjetas.texto }}
                <ArrowRightIcon class="w-4 h-4 transition-transform group-hover:translate-x-1" />
              </EnlaceCms>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>

  <!-- TESTIMONIO + RESULTADOS -->
  <section class="bg-white py-20 px-6">
    <div class="max-w-6xl mx-auto grid lg:grid-cols-[5fr_7fr] rounded-[2rem] overflow-hidden">
      <figure v-reveal class="relative bg-azul-noche text-white p-8 sm:p-10 flex flex-col justify-between gap-8 overflow-hidden">
        <svg class="w-12 h-10 text-primary-400" viewBox="0 0 48 40" fill="currentColor" aria-hidden="true">
          <path d="M0 40V22C0 10 6 2 18 0l2 6c-6 2-9 6-9 12h9v22H0zm28 0V22c0-12 6-20 18-22l2 6c-6 2-9 6-9 12h9v22H28z" />
        </svg>
        <blockquote class="font-medium italic text-lg leading-relaxed text-white/90 relative">
          {{ p.t('testimonio.cita', testimonio.cita) }}
        </blockquote>
        <figcaption class="flex items-center gap-4 relative z-10 pr-16">
          <span class="w-12 h-12 rounded-full bg-centros text-white font-heading font-bold flex items-center justify-center shrink-0" aria-hidden="true">
            {{ iniciales(p.t('testimonio.nombre', testimonio.nombre)) }}
          </span>
          <span class="min-w-0 text-sm">
            <span class="block font-bold">{{ p.t('testimonio.nombre', testimonio.nombre) }}</span>
            <span class="block text-white/70">{{ p.t('testimonio.cargo', testimonio.cargo) }}</span>
          </span>
        </figcaption>
        <!-- Barrido de colores de marca en la esquina, como en el diseño -->
        <span class="swoosh absolute -right-20 -bottom-20 w-40 h-40 rounded-full" aria-hidden="true" />
      </figure>

      <div v-reveal="150" class="bg-superficie p-8 sm:p-10">
        <p class="section-eyebrow">{{ p.t('resultados.antetitulo', 'Resultados reales') }}</p>
        <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight text-azul-noche leading-tight">
          {{ p.t('resultados.titulo', 'Centros que ya confían en') }} <span class="text-primary-500">{{ p.t('resultados.destacado', 'DuaLab') }}</span>
        </h2>
        <div class="grid sm:grid-cols-3 gap-4 mt-6">
          <div v-for="r in resultadosTextos" :key="r.texto" class="bg-white rounded-2xl p-4">
            <p class="font-heading font-extrabold text-3xl text-empresas leading-none">{{ r.valor }}</p>
            <p class="text-sm text-gray-500 leading-snug mt-2">{{ r.texto }}</p>
          </div>
        </div>
        <EnlaceCms :to="resultadosBoton.to" class="btn-pill-outline mt-8">
          {{ resultadosBoton.texto }}
          <ArrowRightIcon class="w-4 h-4" />
        </EnlaceCms>
      </div>
    </div>
  </section>

  <!-- PREGUNTAS FRECUENTES -->
  <section class="bg-white pb-20 px-6">
    <div class="max-w-6xl mx-auto grid lg:grid-cols-[5fr_7fr] gap-10 items-start">
      <div v-reveal>
        <p class="section-eyebrow">{{ p.t('preguntas.antetitulo', 'Preguntas frecuentes') }}</p>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">{{ p.t('preguntas.titulo', 'Resolvemos tus dudas') }}</h2>
        <p class="text-gray-500 leading-relaxed mt-4 max-w-md">
          {{ p.t('preguntas.descripcion', 'Aquí encontrarás respuestas a las preguntas más habituales sobre el uso de DuaLab en centros educativos.') }}
        </p>
        <EnlaceCms :to="preguntasBoton.to" class="btn-pill-outline mt-8">
          {{ preguntasBoton.texto }}
          <ArrowRightIcon class="w-4 h-4" />
        </EnlaceCms>
      </div>

      <div v-reveal="150" class="space-y-3">
        <div v-for="(f, i) in faqsTextos" :key="f.q" class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_12px_rgba(23,40,62,0.05)]">
          <button type="button"
                  class="w-full flex items-center gap-4 text-left px-5 py-4 font-medium text-sm text-azul-noche focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 rounded-2xl"
                  :aria-expanded="faqAbierta === i" :aria-controls="`faq-${i}`"
                  @click="faqAbierta = faqAbierta === i ? null : i">
            <span class="flex-1 min-w-0">{{ f.q }}</span>
            <PlusIcon class="w-5 h-5 text-primary-600 shrink-0 transition-transform duration-300" :class="{ 'rotate-45': faqAbierta === i }" />
          </button>
          <p v-show="faqAbierta === i" :id="`faq-${i}`" class="px-5 pb-4 -mt-1 text-sm text-gray-500 leading-relaxed">{{ f.a }}</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA FINAL: mismo banner que la home -->
  <CtaTransforma @agendar="router.push(contactoCentro)" />
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import dua11 from '@/assets/dua_11.jpeg'
import dua13 from '@/assets/dua_13.jpg'
import dua14 from '@/assets/dua_14.jpeg'
import dua17 from '@/assets/dua_17.jpg'
import dua19 from '@/assets/dua_19.jpg'
import centroEducativo from '@/assets/centro_educativo.jpg'
import CtaTransforma from '@/components/common/CtaTransforma.vue'
import {
  ArrowRightIcon,
  BuildingLibraryIcon,
  BuildingOffice2Icon,
  ChartBarIcon,
  CheckIcon,
  DocumentTextIcon,
  ClipboardDocumentCheckIcon,
  CalendarDaysIcon,
  FolderOpenIcon,
  AcademicCapIcon,
  HandRaisedIcon,
  LightBulbIcon,
  PlusIcon,
  Squares2X2Icon,
  UserGroupIcon,
  BookOpenIcon,
} from '@heroicons/vue/24/outline'
import EnlaceCms from '@/components/common/EnlaceCms.vue'
import { usePagina } from '@/composables/usePagina'

// ── Directiva local: scroll-reveal (misma que en la home) ─────────────────────
const vReveal = {
  mounted(el, binding) {
    const delay = typeof binding.value === 'number' ? binding.value : 0
    el.classList.add('reveal-init')
    const io = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setTimeout(() => el.classList.add('reveal-in'), delay)
          io.disconnect()
        }
      },
      { threshold: 0.1 }
    )
    io.observe(el)
  },
}

const router = useRouter()
// Todas las acciones de la página llevan al formulario con "Centro educativo" marcado
const contactoCentro = { name: 'contacto', query: { tipo: 'centro' } }

// Textos editables desde el panel de gestión web (config/cms_paginas.php → centros)
const p = usePagina('centros', { titulo: 'Centros Educativos' })
const heroB1 = computed(() => p.boton('hero.boton_principal', { texto: 'Registrar mi centro', to: contactoCentro }))
const heroB2 = computed(() => p.boton('hero.boton_secundario', { texto: 'Solicitar demo', to: contactoCentro }))

const enlaceTarjetas  = computed(() => p.boton('casos.enlace_tarjetas', { texto: 'Ver caso', to: contactoCentro }))
const resultadosBoton = computed(() => p.boton('resultados.boton', { texto: 'Ver más casos de éxito', to: { name: 'noticias' } }))
const preguntasBoton  = computed(() => p.boton('preguntas.boton', { texto: 'Hacer otra pregunta', to: contactoCentro }))

const faqAbierta = ref(null)

function iniciales(nombre) {
  return nombre.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

// ── Data ───────────────────────────────────────────────────────────────────────
const ventajas = [
  { title: 'Gestión centralizada', icon: Squares2X2Icon, bg: 'bg-centros',
    desc: 'Organiza toda la información de la FP Dual en un único espacio.' },
  { title: 'Conexión con empresas', icon: HandRaisedIcon, bg: 'bg-empresas',
    desc: 'Encuentra, coordina y colabora con empresas alineadas con tu oferta formativa.' },
  { title: 'Seguimiento del alumnado', icon: UserGroupIcon, bg: 'bg-alumnos',
    desc: 'Facilita la comunicación y el acompañamiento durante todas las fases.' },
  { title: 'Evaluación e informes', icon: ChartBarIcon, bg: 'bg-administraciones',
    desc: 'Datos y evidencias en tiempo real para la toma de decisiones.' },
  { title: 'Innovación y visibilidad', icon: LightBulbIcon, bg: 'bg-azul-noche',
    desc: 'Da visibilidad a los proyectos y microproyectos de tu centro y conecta con otras iniciativas.' },
]

const plataforma = [
  { label: 'Gestión de alumnado y empresas',     icon: UserGroupIcon,              soft: 'bg-alumnos/10 text-alumnos' },
  { label: 'Planificación de prácticas y proyectos', icon: CalendarDaysIcon,       soft: 'bg-centros/10 text-centros' },
  { label: 'Seguimiento, tutorías y evaluaciones', icon: ClipboardDocumentCheckIcon, soft: 'bg-empresas/10 text-empresas' },
  { label: 'Documentación y convenios',          icon: DocumentTextIcon,           soft: 'bg-administraciones/10 text-administraciones' },
  { label: 'Informes y trazabilidad',            icon: FolderOpenIcon,             soft: 'bg-primary-100 text-primary-700' },
  { label: 'Banco de conocimiento y recursos',   icon: BookOpenIcon,               soft: 'bg-centros/10 text-centros' },
]

const pasos = [
  { title: 'Registro del centro',   bg: 'bg-centros',          desc: 'Crea el perfil de tu centro y configura los datos básicos.' },
  { title: 'Configura tu equipo',   bg: 'bg-empresas',         desc: 'Añade tutores, docentes y departamentos.' },
  { title: 'Conecta con empresas',  bg: 'bg-alumnos',          desc: 'Explora, invita y gestiona empresas colaboradoras.' },
  { title: 'Empieza a trabajar',    bg: 'bg-administraciones', desc: 'Publica retos, asigna prácticas y haz seguimiento en tiempo real.' },
]

const casos = [
  { title: 'Centros de FP', icon: AcademicCapIcon, soft: 'bg-centros/10 text-centros',
    image: centroEducativo, alt: 'Sesión de formación en un centro educativo',
    desc: 'Gestiona ciclos formativos, prácticas y proyectos con empresas del sector.' },
  { title: 'Institutos', icon: BuildingLibraryIcon, soft: 'bg-empresas/10 text-empresas',
    image: dua11, alt: 'Alumnado de instituto trabajando por equipos',
    desc: 'Impulsa microproyectos y colaboración con el entorno.' },
  { title: 'Centros integrados', icon: BuildingOffice2Icon, soft: 'bg-administraciones/10 text-administraciones',
    image: dua19, alt: 'Espacio de trabajo con varios grupos de alumnado',
    desc: 'Coordina múltiples familias profesionales y empresas en un único entorno.' },
]

// PENDIENTE DE CONFIRMAR: cifras y testimonio tomados tal cual de la maqueta
// DuaLab_Web_Centros. Sustituir por datos y un testimonio reales (con permiso
// de la persona) antes de publicar en producción.
const destacado = { valor: '+70%', texto: 'de centros mejoran la coordinación con empresas' }
const resultados = [
  { valor: '+60%', texto: 'Más eficiencia en la gestión administrativa' },
  { valor: '+45%', texto: 'Mayor participación de empresas' },
  { valor: '+70%', texto: 'Mejora en el seguimiento del alumnado' },
]
const testimonio = {
  cita: 'DuaLab nos ha permitido organizar la Formación Dual de manera sencilla y eficiente, mejorando la comunicación con empresas y el seguimiento de nuestro alumnado.',
  nombre: 'Laura Méndez',
  cargo: 'Directora de FP · IES Ana Luisa Benítez',
}

// PENDIENTE DE REVISAR: respuestas redactadas para la maqueta (las preguntas
// vienen del diseño).
const faqs = [
  { q: '¿Qué necesito para registrar mi centro?',
    a: 'Solo tienes que rellenar el formulario de contacto con los datos del centro. Nuestro equipo se pondrá en contacto contigo para dar de alta el centro y acompañarte en la puesta en marcha.' },
  { q: '¿Cuántos docentes pueden acceder?',
    a: 'Todos los que necesite tu centro: el equipo responsable da de alta a docentes, tutores y departamentos, y cada uno accede con su propia cuenta.' },
  { q: '¿Cómo se gestionan las empresas colaboradoras?',
    a: 'Desde la plataforma puedes explorar empresas, invitarlas a colaborar y gestionar los retos, prácticas y proyectos que comparten con tu centro.' },
  { q: '¿Es gratuita la plataforma para centros educativos?',
    a: 'Las condiciones dependen de cada centro y de los programas de Formación Dual de su comunidad autónoma. Escríbenos y te explicamos las opciones disponibles para tu centro.' },
  { q: '¿Podemos utilizar DuaLab en distintos ciclos y familias profesionales?',
    a: 'Sí. DuaLab trabaja con todas las familias profesionales, así que un mismo centro puede gestionar varios ciclos desde un único entorno.' },
]

// Las listas con los textos del panel: iconos, colores e imágenes siguen en los
// arrays de arriba; solo se sustituyen los textos (item_N_titulo / item_N_texto).
const ventajasTextos = computed(() => ventajas.map((v, i) => ({
  ...v,
  title: p.t(`ventajas.item_${i + 1}_titulo`, v.title),
  desc: p.t(`ventajas.item_${i + 1}_texto`, v.desc),
})))
const plataformaTextos = computed(() => plataforma.map((item, i) => ({
  ...item,
  label: p.t(`plataforma.item_${i + 1}_titulo`, item.label),
})))
const pasosTextos = computed(() => pasos.map((paso, i) => ({
  ...paso,
  title: p.t(`pasos.item_${i + 1}_titulo`, paso.title),
  desc: p.t(`pasos.item_${i + 1}_texto`, paso.desc),
})))
const casosTextos = computed(() => casos.map((c, i) => ({
  ...c,
  title: p.t(`casos.item_${i + 1}_titulo`, c.title),
  desc: p.t(`casos.item_${i + 1}_texto`, c.desc),
})))
// En las cifras la «cifra» va en item_N_cifra y la frase en item_N_texto
const resultadosTextos = computed(() => resultados.map((r, i) => ({
  valor: p.t(`resultados.item_${i + 1}_cifra`, r.valor),
  texto: p.t(`resultados.item_${i + 1}_texto`, r.texto),
})))
const faqsTextos = computed(() => faqs.map((f, i) => ({
  q: p.t(`preguntas.item_${i + 1}_titulo`, f.q),
  a: p.t(`preguntas.item_${i + 1}_texto`, f.a),
})))
</script>

<style scoped>
/* ── Entrada escalonada del hero (mismos tiempos que la home) ─────────────── */
@keyframes heroFadeUp {
  from { opacity: 0; transform: translateY(40px); }
  to   { opacity: 1; transform: translateY(0); }
}
.hero-badge { animation: heroFadeUp 1.1s cubic-bezier(0.16, 1, 0.3, 1) both; animation-delay: 0.2s; }
.hero-title { animation: heroFadeUp 1.3s cubic-bezier(0.16, 1, 0.3, 1) both; animation-delay: 0.55s; }
.hero-desc  { animation: heroFadeUp 1.2s cubic-bezier(0.16, 1, 0.3, 1) both; animation-delay: 1.0s; }
.hero-btns  { animation: heroFadeUp 1.1s cubic-bezier(0.16, 1, 0.3, 1) both; animation-delay: 1.45s; }
.hero-img   { animation: heroFadeUp 1.3s cubic-bezier(0.16, 1, 0.3, 1) both; animation-delay: 0.8s; }

.blob-image {
  clip-path: url(#centros-blob);
}

/* Barrido con los colores de colectivo en la esquina del testimonio */
.swoosh {
  background: conic-gradient(from 200deg, #3072AA, #19A7A8, #509928, #FF8920, #3072AA);
  opacity: 0.85;
}
</style>
