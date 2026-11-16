<template>
  <!-- Página para empresas (diseño DuaLab_Web_Empresas). Misma estructura que /centros. -->
  <svg width="0" height="0" class="absolute" aria-hidden="true">
    <defs>
      <!-- Misma forma orgánica que la imagen de "Retos académicos" de la home -->
      <clipPath id="empresas-blob" clipPathUnits="objectBoundingBox">
        <path d="M0.06,0.42 C0.12,0.2 0.3,0.26 0.46,0.14 C0.62,0.02 0.8,-0.02 0.93,0.07 C1,0.12 1,0.28 1,0.4 L1,0.86 C1,0.95 0.96,1 0.88,1 L0.18,1 C0.07,1 0.01,0.9 0,0.76 C-0.01,0.62 0.02,0.52 0.06,0.42 Z" />
      </clipPath>
    </defs>
  </svg>

  <!-- HERO: mismo lenguaje que el de la home (azul de marca, foto de fondo
       tenue, rótulo en píldora y botones píldora blancos) -->
  <section class="relative overflow-hidden bg-primary-700 py-16 md:py-24 px-6">
    <div class="absolute inset-0 bg-cover bg-center opacity-20" :style="{ backgroundImage: `url('${p.imagen('hero.imagen_fondo', dua28)}')` }" />

    <div class="relative max-w-6xl mx-auto grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-12 items-center">
      <div>
        <span class="hero-badge inline-block bg-white/15 text-white px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest mb-6 border border-white/30">
          {{ p.t('hero.antetitulo', 'Empresas') }}
        </span>
        <h1 class="hero-title text-[32px] sm:text-5xl md:text-6xl font-black tracking-tighter text-white leading-[1.1] mb-5">
          {{ p.t('hero.titulo', 'Conecta tu empresa con el talento') }}
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#8fc46a] to-administraciones">{{ p.t('hero.destacado', 'del futuro') }}</span>
        </h1>
        <p v-if="p.tiene('hero.descripcion')" class="hero-desc text-white/75 text-base leading-relaxed mb-8 max-w-lg">{{ p.t('hero.descripcion') }}</p>
        <!-- Sin editar en el panel: el original con sus resaltados -->
        <p v-else class="hero-desc text-white/75 text-base leading-relaxed mb-8 max-w-lg">
          Colabora con <strong class="text-white font-bold">centros educativos</strong>, participa en la
          <strong class="text-[#8fc46a] font-bold">Formación Dual</strong> y convierte los
          <strong class="text-[#ffb066] font-bold">retos de tu empresa</strong> en
          <strong class="text-[#5fd0d0] font-bold">oportunidades de aprendizaje</strong>.
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
          Talento real<br>para retos<br><span class="text-[#8fc46a]">reales</span>
        </p>
        <svg class="hidden lg:block absolute left-16 top-28 w-20 h-16 z-10" viewBox="0 0 100 80" fill="none" aria-hidden="true">
          <path d="M8 6 C 6 40, 40 64, 86 58" stroke="#FF8920" stroke-width="3" stroke-linecap="round" />
          <path d="M76 50 L88 58 L76 66" stroke="#FF8920" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <img :src="p.imagen('hero.imagen', dua9)" alt="Profesionales de empresa colaborando con alumnado de FP"
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
        <img :src="dua20" alt="Empresa presentando un reto al alumnado en una sesión de DuaLab"
             class="w-full h-[300px] sm:h-[420px] lg:h-[480px] object-cover rounded-2xl" loading="lazy" />
      </div>

      <div v-reveal="150">
        <p class="section-eyebrow">Plataforma DuaLab</p>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">
          Todo lo que necesitas para colaborar con la <span class="text-primary-500">Formación Dual</span>
        </h2>
        <p class="text-gray-500 leading-relaxed mt-5">
          Un espacio para conectar con centros, coordinar a tu equipo y acompañar al alumnado.
        </p>
        <ul class="mt-6 bg-white rounded-2xl divide-y divide-gray-100 shadow-[0_2px_12px_rgba(23,40,62,0.05)]">
          <li v-for="p in plataforma" :key="p.label" class="flex items-center gap-4 px-4 py-3">
            <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" :class="p.soft">
              <component :is="p.icon" class="w-5 h-5" />
            </span>
            <span class="flex-1 min-w-0 font-medium text-sm text-azul-noche">{{ p.label }}</span>
            <CheckIcon class="w-4 h-4 text-empresas shrink-0" aria-hidden="true" />
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
          Empieza con <span class="text-primary-500">DuaLab</span> en 4 pasos
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

  <!-- FORMAS DE COLABORAR -->
  <section class="bg-superficie py-20 px-6">
    <div class="max-w-6xl mx-auto">
      <div v-reveal class="mb-10">
        <p class="section-eyebrow">Formas de colaborar</p>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">
          Una colaboración adaptada a <span class="text-primary-500">tu empresa</span>
        </h2>
        <p class="text-gray-500 leading-relaxed mt-3">
          Diferentes formas de participar, con un objetivo común: conectar formación y realidad profesional.
        </p>
      </div>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <article v-for="(c, i) in colaboraciones" :key="c.title" v-reveal="i * 100"
                 class="sm:last:col-span-2 lg:last:col-span-1 bg-white rounded-2xl overflow-hidden flex flex-col shadow-[0_2px_12px_rgba(23,40,62,0.05)]">
          <img :src="c.image" :alt="c.alt" class="w-full h-48 object-cover" loading="lazy" />
          <div class="p-5 flex gap-4 flex-1">
            <span class="w-11 h-11 rounded-full flex items-center justify-center shrink-0" :class="c.soft">
              <component :is="c.icon" class="w-5 h-5" />
            </span>
            <div class="min-w-0 flex flex-col">
              <h3 class="font-heading font-bold text-azul-noche">{{ c.title }}</h3>
              <p class="text-sm text-gray-500 leading-snug mt-1 flex-1">{{ c.desc }}</p>
              <RouterLink :to="contactoEmpresa"
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

  <!-- VALOR PARA TU EMPRESA Y PARA TU ENTORNO -->
  <section class="bg-white py-20 px-6">
    <div class="max-w-6xl mx-auto grid lg:grid-cols-[5fr_7fr] rounded-[2rem] overflow-hidden">
      <div v-reveal class="relative bg-azul-noche text-white p-8 pb-24 sm:p-10 sm:pb-24 lg:pb-10 flex flex-col justify-center gap-5 overflow-hidden">
        <h2 class="relative z-10 text-3xl md:text-4xl font-extrabold tracking-tight leading-tight">
          Tu experiencia <span class="text-primary-400">también forma</span>
        </h2>
        <p class="relative z-10 text-white/85 leading-relaxed">
          La colaboración entre <strong class="text-[#8fc46a] font-semibold">empresas</strong> y centros ayuda a
          <strong class="text-primary-300 font-semibold">preparar</strong> profesionales que conocen los retos del mundo
          <strong class="text-[#ffb066] font-semibold">laboral</strong>.
        </p>
        <!-- Barrido de colores de marca en la esquina, como en el diseño -->
        <span class="swoosh absolute -right-20 -bottom-20 w-40 h-40 rounded-full" aria-hidden="true" />
      </div>

      <div v-reveal="150" class="bg-superficie p-8 sm:p-10">
        <p class="section-eyebrow">Para tu empresa y tu entorno</p>
        <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight text-azul-noche leading-tight">
          Valor para tu empresa y para <span class="text-primary-500">tu entorno</span>
        </h2>
        <div class="grid sm:grid-cols-3 gap-4 mt-6">
          <div v-for="v in valores" :key="v.title" class="bg-white rounded-2xl p-4">
            <component :is="v.icon" class="w-8 h-8" :class="v.text" />
            <p class="font-heading font-bold text-azul-noche mt-3">{{ v.title }}</p>
            <p class="text-sm text-gray-500 leading-snug mt-1">{{ v.desc }}</p>
          </div>
        </div>
        <RouterLink :to="contactoEmpresa" class="btn-pill-outline mt-8">
          Quiero colaborar
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
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">Resolvemos tus dudas</h2>
        <p class="text-gray-500 leading-relaxed mt-4 max-w-md">
          Conoce cómo puede participar tu empresa en el ecosistema DuaLab.
        </p>
        <RouterLink :to="contactoEmpresa" class="btn-pill-outline mt-8">
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
  <CtaTransforma @agendar="router.push(contactoEmpresa)" />
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import dua6 from '@/assets/6_dua.jpg'
import dua9 from '@/assets/dua_9.jpg'
import dua28 from '@/assets/dua_28.jpg'
import dua20 from '@/assets/dua_20.jpg'
import dua25 from '@/assets/dua_25.jpg'
import dua29 from '@/assets/dua_29.jpg'
import CtaTransforma from '@/components/common/CtaTransforma.vue'
import {
  AcademicCapIcon,
  ArrowRightIcon,
  BuildingLibraryIcon,
  BuildingOffice2Icon,
  CalendarDaysIcon,
  ChatBubbleLeftRightIcon,
  CheckIcon,
  Cog6ToothIcon,
  DocumentTextIcon,
  GlobeEuropeAfricaIcon,
  LightBulbIcon,
  MegaphoneIcon,
  PlusIcon,
  PresentationChartBarIcon,
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
// Todas las acciones de la página llevan al formulario con "Empresa" marcado
const contactoEmpresa = { name: 'contacto', query: { tipo: 'empresa' } }

// Textos de la cabecera editables desde el panel de gestión web (config/cms_paginas.php → empresas)
const p = usePagina('empresas', { titulo: 'Empresas' })
const heroB1 = computed(() => p.boton('hero.boton_principal', { texto: 'Registrar mi empresa', to: contactoEmpresa }))
const heroB2 = computed(() => p.boton('hero.boton_secundario', { texto: 'Solicitar demo', to: contactoEmpresa }))

const faqAbierta = ref(null)

// ── Data ───────────────────────────────────────────────────────────────────────
const ventajas = [
  { title: 'Conexión con centros', icon: BuildingLibraryIcon, bg: 'bg-centros',
    desc: 'Encuentra colaboradores educativos de tu sector.' },
  { title: 'Talento en formación', icon: UserGroupIcon, bg: 'bg-empresas',
    desc: 'Acerca tu empresa a futuros profesionales.' },
  { title: 'Retos reales', icon: LightBulbIcon, bg: 'bg-alumnos',
    desc: 'Comparte necesidades y explora nuevas ideas.' },
  { title: 'Seguimiento compartido', icon: Cog6ToothIcon, bg: 'bg-administraciones',
    desc: 'Coordina el aprendizaje con el centro.' },
  { title: 'Visibilidad empresarial', icon: MegaphoneIcon, bg: 'bg-azul-noche',
    desc: 'Da a conocer tu compromiso con la formación.' },
]

const plataforma = [
  { label: 'Perfil de empresa y áreas de actividad',  icon: BuildingOffice2Icon,       soft: 'bg-centros/10 text-centros' },
  { label: 'Conexión con centros educativos',         icon: AcademicCapIcon,           soft: 'bg-alumnos/10 text-alumnos' },
  { label: 'Propuesta de retos y microproyectos',     icon: LightBulbIcon,             soft: 'bg-empresas/10 text-empresas' },
  { label: 'Coordinación de estancias formativas',    icon: CalendarDaysIcon,          soft: 'bg-administraciones/10 text-administraciones' },
  { label: 'Seguimiento y comunicación con tutores',  icon: ChatBubbleLeftRightIcon,   soft: 'bg-primary-100 text-primary-700' },
  { label: 'Documentación y recursos compartidos',    icon: DocumentTextIcon,          soft: 'bg-centros/10 text-centros' },
]

const pasos = [
  { title: 'Registra tu empresa',     bg: 'bg-centros',          desc: 'Completa tu perfil y tus áreas de actividad.' },
  { title: 'Conecta con centros',     bg: 'bg-empresas',         desc: 'Encuentra formación relacionada con tu sector.' },
  { title: 'Comparte oportunidades',  bg: 'bg-alumnos',          desc: 'Propón retos y opciones de colaboración.' },
  { title: 'Acompaña al talento',     bg: 'bg-administraciones', desc: 'Coordina el aprendizaje con el equipo docente.' },
]

const colaboraciones = [
  { title: 'Acoge talento en formación', icon: UserGroupIcon, soft: 'bg-centros/10 text-centros',
    image: dua25, alt: 'Profesional de empresa conversando con alumnado de FP',
    desc: 'Participa en experiencias formativas vinculadas a tu actividad.' },
  { title: 'Propón un reto real', icon: LightBulbIcon, soft: 'bg-empresas/10 text-empresas',
    image: dua29, alt: 'Alumna presentando propuestas de mejora para un reto de empresa',
    desc: 'Comparte un desafío para trabajar con centros y alumnado.' },
  { title: 'Comparte tu experiencia', icon: PresentationChartBarIcon, soft: 'bg-alumnos/10 text-alumnos',
    image: dua6, alt: 'Profesional presentando su experiencia al alumnado',
    desc: 'Aporta conocimiento profesional y acerca tu sector al aula.' },
]

const valores = [
  { title: 'Conexión',   icon: UserGroupIcon,          text: 'text-centros',   desc: 'Relaciones con centros y talento.' },
  { title: 'Innovación', icon: LightBulbIcon,          text: 'text-empresas',  desc: 'Nuevas perspectivas para retos reales.' },
  { title: 'Compromiso', icon: GlobeEuropeAfricaIcon,  text: 'text-alumnos',   desc: 'Participación en la formación del futuro.' },
]

// PENDIENTE DE REVISAR: respuestas redactadas para la maqueta (las preguntas
// vienen del diseño DuaLab_Web_Empresas).
const faqs = [
  { q: '¿Qué necesito para registrar mi empresa?',
    a: 'Solo tienes que rellenar el formulario de contacto con los datos de tu empresa. Nuestro equipo se pondrá en contacto contigo para darla de alta y acompañarte en los primeros pasos.' },
  { q: '¿Pueden participar empresas de cualquier tamaño?',
    a: 'Sí. Desde microempresas hasta grandes compañías: cada empresa colabora en la medida que mejor encaja con su actividad y su disponibilidad.' },
  { q: '¿Cómo conecto con los centros educativos?',
    a: 'DuaLab te pone en contacto con centros que imparten formación relacionada con tu sector, y desde la plataforma coordinas con ellos los retos y las colaboraciones.' },
  { q: '¿Puedo proponer retos sin acoger alumnado?',
    a: 'Sí. Puedes plantear un reto real para que el alumnado lo trabaje desde su centro, sin necesidad de acoger estancias formativas en tu empresa.' },
  { q: '¿Cómo se coordina el seguimiento con el centro?',
    a: 'El seguimiento se hace desde la plataforma, en un espacio compartido con el equipo docente: comunicación con tutores, documentación y evolución del alumnado en un mismo lugar.' },
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
  clip-path: url(#empresas-blob);
}

/* Barrido con los colores de colectivo en la esquina del bloque oscuro */
.swoosh {
  background: conic-gradient(from 200deg, #3072AA, #19A7A8, #509928, #FF8920, #3072AA);
  opacity: 0.85;
}
</style>
