<template>
  <!-- Página para alumnado (diseño DuaLab_Web_Alumnado). Misma estructura que /centros y /empresas-asociadas. -->
  <svg width="0" height="0" class="absolute" aria-hidden="true">
    <defs>
      <!-- Misma forma orgánica que la imagen de "Retos académicos" de la home -->
      <clipPath id="alumnado-blob" clipPathUnits="objectBoundingBox">
        <path d="M0.06,0.42 C0.12,0.2 0.3,0.26 0.46,0.14 C0.62,0.02 0.8,-0.02 0.93,0.07 C1,0.12 1,0.28 1,0.4 L1,0.86 C1,0.95 0.96,1 0.88,1 L0.18,1 C0.07,1 0.01,0.9 0,0.76 C-0.01,0.62 0.02,0.52 0.06,0.42 Z" />
      </clipPath>
    </defs>
  </svg>

  <!-- HERO: mismo lenguaje que el de la home (azul de marca, foto de fondo
       tenue, rótulo en píldora y botones píldora blancos) -->
  <section class="relative overflow-hidden bg-primary-700 py-16 md:py-24 px-6">
    <div class="absolute inset-0 bg-cover bg-center opacity-20" :style="{ backgroundImage: `url('${p.imagen('hero.imagen_fondo', dua27)}')` }" />

    <div class="relative max-w-6xl mx-auto grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-12 items-center">
      <div>
        <span class="hero-badge inline-block bg-white/15 text-white px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest mb-6 border border-white/30">
          {{ p.t('hero.antetitulo', 'Alumnado') }}
        </span>
        <h1 class="hero-title text-[32px] sm:text-5xl md:text-6xl font-black tracking-tighter text-white leading-[1.1] mb-5">
          {{ p.t('hero.titulo', 'Tu talento, conectado con') }}
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#ffb066] to-alumnos">{{ p.t('hero.destacado', 'tu futuro') }}</span>
        </h1>
        <p v-if="p.tiene('hero.descripcion')" class="hero-desc text-white/75 text-base leading-relaxed mb-8 max-w-lg">{{ p.t('hero.descripcion') }}</p>
        <!-- Sin editar en el panel: el original con sus resaltados -->
        <p v-else class="hero-desc text-white/75 text-base leading-relaxed mb-8 max-w-lg">
          Aprende con <strong class="text-[#ffb066] font-bold">retos reales</strong>, conecta con el
          <strong class="text-[#8fc46a] font-bold">mundo profesional</strong> y sigue tu
          <strong class="text-[#5fd0d0] font-bold">Formación Dual</strong> en un mismo lugar.
        </p>
        <!-- El registro de alumnado aún no está abierto (contactoConfig.js: alumno bloqueado):
             la acción principal lleva a explorar los retos publicados. -->
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
          Aprende,<br>conecta<br><span class="text-alumnos">y crece</span>
        </p>
        <svg class="hidden lg:block absolute left-16 top-28 w-20 h-16 z-10" viewBox="0 0 100 80" fill="none" aria-hidden="true">
          <path d="M8 6 C 6 40, 40 64, 86 58" stroke="#FF8920" stroke-width="3" stroke-linecap="round" />
          <path d="M76 50 L88 58 L76 66" stroke="#FF8920" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <!-- Recorte en el contenedor para que el degradado siga la misma forma que la foto -->
        <div class="blob-image relative h-[340px] lg:h-[420px]">
          <img :src="p.imagen('hero.imagen', dua12)" alt="Alumnado resolviendo un reto en el aula"
               class="w-full h-full object-cover" />
          <!-- Degradado y desenfoque en la parte inferior: la hoja de la mesa no debe poder leerse -->
          <div class="absolute inset-x-0 bottom-0 h-1/2 backdrop-blur-[2px] bg-gradient-to-t from-primary-700/40 via-primary-700/15 to-transparent fade-mask" aria-hidden="true" />
        </div>
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
      <div v-for="v in ventajas" :key="v.title" class="flex sm:flex-col gap-4 sm:gap-3 lg:px-5 lg:first:pl-0 lg:last:pr-0">
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
      <div v-reveal>
        <img :src="dua25" alt="Profesional conversando con alumnado durante una sesión de DuaLab"
             class="w-full h-[300px] sm:h-[420px] lg:h-[480px] object-cover rounded-2xl" loading="lazy" />
      </div>

      <div v-reveal="150">
        <p class="section-eyebrow">Plataforma DuaLab</p>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">
          Tu Formación Dual, más <span class="text-primary-500">clara y conectada</span>
        </h2>
        <p class="text-gray-500 leading-relaxed mt-5">
          Un espacio para organizar tu aprendizaje y colaborar con tu centro, tus tutores y tu equipo.
        </p>
        <ul class="mt-6 bg-white rounded-2xl divide-y divide-gray-100 shadow-[0_2px_12px_rgba(23,40,62,0.05)]">
          <li v-for="p in plataforma" :key="p.label" class="flex items-center gap-4 px-4 py-3">
            <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" :class="p.soft">
              <component :is="p.icon" class="w-5 h-5" />
            </span>
            <span class="flex-1 min-w-0 font-medium text-sm text-azul-noche">{{ p.label }}</span>
            <CheckIcon class="w-4 h-4 text-alumnos shrink-0" aria-hidden="true" />
          </li>
        </ul>
      </div>
    </div>
  </section>

  <!-- CÓMO FUNCIONA -->
  <section class="bg-white py-20 px-6">
    <div class="max-w-6xl mx-auto">
      <div v-reveal class="mb-10">
        <p class="section-eyebrow">Cómo funciona</p>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">
          Empieza tu camino en <span class="text-primary-500">4 pasos</span>
        </h2>
      </div>
      <ol v-reveal="100" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <li v-for="(p, i) in pasos" :key="p.title"
            class="bg-superficie rounded-2xl p-5">
          <div class="flex items-center gap-3">
            <span class="w-11 h-11 rounded-full flex items-center justify-center text-white font-heading font-black text-lg shadow-md shrink-0" :class="p.bg">
              {{ i + 1 }}
            </span>
            <!-- Conector hacia el siguiente paso (solo en fila completa) -->
            <span v-if="i < pasos.length - 1" class="hidden lg:flex flex-1 items-center" aria-hidden="true">
              <span class="flex-1 h-px bg-primary-200" />
              <ArrowRightIcon class="w-4 h-4 text-primary-400 -ml-1" />
            </span>
          </div>
          <h3 class="font-heading font-bold text-azul-noche mt-4">{{ p.title }}</h3>
          <p class="text-sm text-gray-500 leading-snug mt-1">{{ p.desc }}</p>
        </li>
      </ol>
    </div>
  </section>

  <!-- APRENDE HACIENDO -->
  <section class="bg-superficie py-20 px-6">
    <div class="max-w-6xl mx-auto">
      <div v-reveal class="mb-10">
        <p class="section-eyebrow">Aprende haciendo</p>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">
          Experiencias que te acercan al <span class="text-primary-500">mundo profesional</span>
        </h2>
        <p class="text-gray-500 leading-relaxed mt-3">
          Conecta lo que estudias con lo que puedes aportar.
        </p>
      </div>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <article v-for="(c, i) in experiencias" :key="c.title" v-reveal="i * 100"
                 class="sm:last:col-span-2 lg:last:col-span-1 bg-white rounded-2xl overflow-hidden flex flex-col shadow-[0_2px_12px_rgba(23,40,62,0.05)]">
          <img :src="c.image" :alt="c.alt" class="w-full h-48 object-cover" loading="lazy" />
          <div class="p-5 flex gap-4 flex-1">
            <span class="w-11 h-11 rounded-full flex items-center justify-center shrink-0" :class="c.soft">
              <component :is="c.icon" class="w-5 h-5" />
            </span>
            <div class="min-w-0 flex flex-col">
              <h3 class="font-heading font-bold text-azul-noche">{{ c.title }}</h3>
              <p class="text-sm text-gray-500 leading-snug mt-1 flex-1">{{ c.desc }}</p>
              <RouterLink :to="{ name: 'familias' }"
                          class="inline-flex items-center gap-1.5 self-start mt-2 py-2.5 text-sm font-bold text-primary-600 hover:text-primary-700 group">
                Descubrir más
                <ArrowRightIcon class="w-4 h-4 transition-transform group-hover:translate-x-1" />
              </RouterLink>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>

  <!-- VALOR PARA TU FUTURO -->
  <section class="bg-white py-20 px-6">
    <div class="max-w-6xl mx-auto grid lg:grid-cols-[5fr_7fr] rounded-[2rem] overflow-hidden">
      <div v-reveal class="relative bg-azul-noche text-white p-8 pb-24 sm:p-10 sm:pb-24 lg:pb-10 flex flex-col justify-center gap-5 overflow-hidden">
        <h2 class="relative z-10 text-3xl md:text-4xl font-extrabold tracking-tight leading-tight">
          Tu aprendizaje tiene <span class="text-primary-400">mucho que aportar</span>
        </h2>
        <p class="relative z-10 text-white/85 leading-relaxed">
          Cada proyecto es una oportunidad para desarrollar <strong class="text-[#ffb066] font-semibold">habilidades</strong>,
          compartir <strong class="text-[#8fc46a] font-semibold">ideas</strong> y descubrir tu
          <strong class="text-[#5fd0d0] font-semibold">potencial</strong>.
        </p>
        <!-- Barrido de colores de marca en la esquina, como en el diseño -->
        <span class="swoosh absolute -right-20 -bottom-20 w-40 h-40 rounded-full" aria-hidden="true" />
      </div>

      <div v-reveal="150" class="bg-superficie p-8 sm:p-10">
        <p class="section-eyebrow">Valor para tu futuro</p>
        <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight text-azul-noche leading-tight">
          Crece con <span class="text-primary-500">cada experiencia</span>
        </h2>
        <div class="grid sm:grid-cols-3 gap-4 mt-6">
          <div v-for="v in valores" :key="v.title" class="bg-white rounded-2xl p-4">
            <component :is="v.icon" class="w-8 h-8" :class="v.text" />
            <p class="font-heading font-bold text-azul-noche mt-3">{{ v.title }}</p>
            <p class="text-sm text-gray-500 leading-snug mt-1">{{ v.desc }}</p>
          </div>
        </div>
        <RouterLink :to="contacto" class="btn-pill-outline mt-8">
          Quiero formar parte
          <ArrowRightIcon class="w-4 h-4" />
        </RouterLink>
      </div>
    </div>
  </section>

  <!-- PREGUNTAS FRECUENTES -->
  <section class="bg-white pb-20 px-6">
    <div class="max-w-6xl mx-auto grid lg:grid-cols-[5fr_7fr] gap-10 items-start">
      <div v-reveal>
        <p class="section-eyebrow">Preguntas frecuentes</p>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">Te ayudamos a empezar</h2>
        <p class="text-gray-500 leading-relaxed mt-4 max-w-md">
          Resuelve tus dudas sobre el registro y la participación en DuaLab.
        </p>
        <RouterLink :to="contacto" class="btn-pill-outline mt-8">
          Hacer otra pregunta
          <ArrowRightIcon class="w-4 h-4" />
        </RouterLink>
      </div>

      <div v-reveal="150" class="space-y-3">
        <div v-for="(f, i) in faqs" :key="f.q" class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_12px_rgba(23,40,62,0.05)]">
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
  <CtaTransforma @agendar="router.push(contacto)" />
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import dua8 from '@/assets/8_dua.jpg'
import dua12 from '@/assets/dua_12.jpeg'
import dua18 from '@/assets/dua_18.jpg'
import dua24 from '@/assets/dua_24.jpg'
import dua27 from '@/assets/dua_27.jpg'
import dua25 from '@/assets/dua_25.jpg'
import CtaTransforma from '@/components/common/CtaTransforma.vue'
import {
  ArrowRightIcon,
  BookOpenIcon,
  BuildingOffice2Icon,
  CalendarDaysIcon,
  ChartBarIcon,
  ChatBubbleLeftRightIcon,
  CheckIcon,
  LightBulbIcon,
  MapIcon,
  PlusIcon,
  PuzzlePieceIcon,
  StarIcon,
  UserCircleIcon,
  UserGroupIcon,
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
// El formulario de alumnado aún está bloqueado (contactoConfig.js): las
// acciones de contacto llevan al formulario general.
const contacto = { name: 'contacto' }

// Textos de la cabecera editables desde el panel de gestión web (config/cms_paginas.php → alumnado)
const p = usePagina('alumnado', { titulo: 'Alumnado' })
const heroB1 = computed(() => p.boton('hero.boton_principal', { texto: 'Explorar retos', to: { name: 'familias' } }))
const heroB2 = computed(() => p.boton('hero.boton_secundario', { texto: 'Solicitar información', to: contacto }))

const faqAbierta = ref(null)

// ── Data ───────────────────────────────────────────────────────────────────────
const ventajas = [
  { title: 'Aprendizaje real', icon: LightBulbIcon, bg: 'bg-centros',
    desc: 'Aplica lo que aprendes a retos profesionales.' },
  { title: 'Conexión con empresas', icon: BuildingOffice2Icon, bg: 'bg-empresas',
    desc: 'Conoce el entorno de tu futuro sector.' },
  { title: 'Tu progreso, visible', icon: ChartBarIcon, bg: 'bg-alumnos',
    desc: 'Organiza tus actividades y avances.' },
  { title: 'Acompañamiento', icon: UserGroupIcon, bg: 'bg-administraciones',
    desc: 'Mantén el contacto con tus tutores.' },
  { title: 'Trabajo en equipo', icon: PuzzlePieceIcon, bg: 'bg-azul-noche',
    desc: 'Comparte ideas y construye soluciones.' },
]

const plataforma = [
  { label: 'Perfil y recorrido formativo',      icon: UserCircleIcon,          soft: 'bg-alumnos/10 text-alumnos' },
  { label: 'Retos y microproyectos',            icon: LightBulbIcon,           soft: 'bg-empresas/10 text-empresas' },
  { label: 'Actividades y entregas',            icon: CalendarDaysIcon,        soft: 'bg-administraciones/10 text-administraciones' },
  { label: 'Seguimiento de tu aprendizaje',     icon: ChartBarIcon,            soft: 'bg-alumnos/10 text-alumnos' },
  { label: 'Comunicación con tus tutores',      icon: ChatBubbleLeftRightIcon, soft: 'bg-centros/10 text-centros' },
  { label: 'Banco de conocimiento y recursos',  icon: BookOpenIcon,            soft: 'bg-empresas/10 text-empresas' },
]

const pasos = [
  { title: 'Solicita tu registro',  bg: 'bg-centros',          desc: 'Indica tu centro y tu formación.' },
  { title: 'Completa tu perfil',    bg: 'bg-empresas',         desc: 'Comparte tus intereses y habilidades.' },
  { title: 'Participa y aprende',   bg: 'bg-alumnos',          desc: 'Colabora en retos y actividades.' },
  { title: 'Sigue tu progreso',     bg: 'bg-administraciones', desc: 'Consulta tus avances con tus tutores.' },
]

const experiencias = [
  { title: 'Resuelve retos reales', icon: LightBulbIcon, soft: 'bg-alumnos/10 text-alumnos',
    image: dua18, alt: 'Alumnado trabajando en equipo en un reto',
    desc: 'Pon tus conocimientos en práctica con desafíos del entorno profesional.' },
  { title: 'Colabora con tu equipo', icon: UserGroupIcon, soft: 'bg-empresas/10 text-empresas',
    image: dua8, alt: 'Equipo de alumnado celebrando un trabajo en grupo',
    desc: 'Comparte ideas y desarrolla soluciones con otros estudiantes.' },
  { title: 'Descubre tu sector', icon: BuildingOffice2Icon, soft: 'bg-centros/10 text-centros',
    image: dua24, alt: 'Alumna presentando un proyecto de marketing y publicidad',
    desc: 'Acércate a empresas y profesionales de tu ámbito.' },
]

const valores = [
  { title: 'Autonomía',     icon: MapIcon,       text: 'text-alumnos',  desc: 'Organiza tus tareas y objetivos.' },
  { title: 'Colaboración',  icon: UserGroupIcon, text: 'text-empresas', desc: 'Aprende con otras personas.' },
  { title: 'Confianza',     icon: StarIcon,      text: 'text-centros',  desc: 'Reconoce y muestra tus avances.' },
]

// PENDIENTE DE REVISAR: respuestas redactadas para la maqueta (las preguntas
// vienen del diseño DuaLab_Web_Alumnado).
const faqs = [
  { q: '¿Qué necesito para registrarme?',
    a: 'El acceso del alumnado se hace a través de tu centro educativo: si tu centro trabaja con DuaLab, tu docente te dará acceso a los retos en los que participa tu grupo.' },
  { q: '¿Debe participar mi centro en DuaLab?',
    a: 'Sí. Los retos se trabajan desde tu centro, con tus docentes. Si tu centro todavía no está en DuaLab, cuéntaselo a tu profesorado o escríbenos y le haremos llegar la información.' },
  { q: '¿Cómo accedo a los retos y proyectos?',
    a: 'Tu docente te da acceso a los retos de tu grupo. Además, en esta web puedes explorar los retos destacados de cada familia profesional.' },
  { q: '¿Quién me acompaña durante el proceso?',
    a: 'Tus docentes te acompañan en todo el recorrido y, cuando el reto lo plantea una empresa, también puedes contar con la visión de sus profesionales.' },
  { q: '¿Dónde puedo consultar mis avances?',
    a: 'En tu espacio de DuaLab, donde ves las actividades, las entregas y la valoración de tu trabajo en cada reto.' },
]
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
  clip-path: url(#alumnado-blob);
}

/* El desenfoque entra poco a poco (sin corte recto en el borde superior) */
.fade-mask {
  -webkit-mask-image: linear-gradient(to top, black 55%, transparent);
  mask-image: linear-gradient(to top, black 55%, transparent);
}

/* Barrido con los colores de colectivo en la esquina del bloque oscuro */
.swoosh {
  background: conic-gradient(from 200deg, #3072AA, #19A7A8, #509928, #FF8920, #3072AA);
  opacity: 0.85;
}
</style>
