<template>
  <!-- Página para instituciones y administraciones (diseño DuaLab_Web_Administraciones).
       Misma estructura que /centros, /empresas-asociadas y /para-alumnado. -->
  <svg width="0" height="0" class="absolute" aria-hidden="true">
    <defs>
      <!-- Misma forma orgánica que la imagen de "Retos académicos" de la home -->
      <clipPath id="entidades-blob" clipPathUnits="objectBoundingBox">
        <path d="M0.06,0.42 C0.12,0.2 0.3,0.26 0.46,0.14 C0.62,0.02 0.8,-0.02 0.93,0.07 C1,0.12 1,0.28 1,0.4 L1,0.86 C1,0.95 0.96,1 0.88,1 L0.18,1 C0.07,1 0.01,0.9 0,0.76 C-0.01,0.62 0.02,0.52 0.06,0.42 Z" />
      </clipPath>
    </defs>
  </svg>

  <!-- HERO: mismo lenguaje que el de la home (azul de marca, foto de fondo
       tenue, rótulo en píldora y botones píldora blancos) -->
  <section class="relative overflow-hidden bg-primary-700 py-16 md:py-24 px-6">
    <div class="absolute inset-0 bg-cover bg-center opacity-20" :style="{ backgroundImage: `url('${p.imagen('hero.imagen_fondo', dua4)}')` }" />

    <div class="relative max-w-6xl mx-auto grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-12 items-center">
      <div>
        <span class="hero-badge inline-block bg-white/15 text-white px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest mb-6 border border-white/30">
          {{ p.t('hero.antetitulo', 'Instituciones y administraciones') }}
        </span>
        <!-- "Administraciones" es una palabra muy larga: el tamaño baja en lg (columna estrecha) y en móvil -->
        <h1 class="hero-title text-[28px] sm:text-5xl md:text-6xl lg:text-5xl font-black tracking-tighter text-white leading-[1.1] mb-5">
          {{ p.t('hero.titulo', 'Impulsamos la Formación Dual desde') }}
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#5fd0d0] to-[#8fc46a]">{{ p.t('hero.destacado', 'las Administraciones') }}</span>
        </h1>
        <p v-if="p.tiene('hero.descripcion')" class="hero-desc text-white/75 text-base leading-relaxed mb-8 max-w-lg">{{ p.t('hero.descripcion') }}</p>
        <!-- Sin editar en el panel: el original con sus resaltados -->
        <p v-else class="hero-desc text-white/75 text-base leading-relaxed mb-8 max-w-lg">
          DuaLab es la plataforma que conecta <strong class="text-white font-bold">centros</strong>,
          <strong class="text-[#8fc46a] font-bold">empresas</strong> y <strong class="text-[#ffb066] font-bold">alumnado</strong>,
          facilitando la planificación, gestión, seguimiento y evaluación de los programas de Formación Profesional Dual
          de forma eficiente, transparente y con <strong class="text-[#5fd0d0] font-bold">impacto real en el territorio</strong>.
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
          Formación Dual<br>para territorios<br><span class="text-[#5fd0d0]">más competitivos</span>
        </p>
        <svg class="hidden lg:block absolute left-16 top-28 w-20 h-16 z-10" viewBox="0 0 100 80" fill="none" aria-hidden="true">
          <path d="M8 6 C 6 40, 40 64, 86 58" stroke="#FF8920" stroke-width="3" stroke-linecap="round" />
          <path d="M76 50 L88 58 L76 66" stroke="#FF8920" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <img :src="p.imagen('hero.imagen', consejeriaEducacion)" alt="Sede de una consejería de educación"
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
    <div class="max-w-6xl mx-auto grid lg:grid-cols-[5fr_7fr] gap-12 items-center">
      <div v-reveal class="relative">
        <img :src="dua26" alt="Alumnado trabajando con portátiles en una sesión de DuaLab"
             class="w-full h-[300px] sm:h-[420px] lg:h-[520px] object-cover rounded-2xl" loading="lazy" />
        <div class="absolute left-4 right-4 sm:right-auto bottom-4 sm:left-6 sm:bottom-6 bg-white rounded-2xl p-4 flex items-center gap-4 shadow-[0_10px_30px_rgba(23,40,62,0.15)]">
          <span class="w-12 h-12 rounded-full bg-centros text-white flex items-center justify-center shrink-0">
            <ChartBarIcon class="w-6 h-6" />
          </span>
          <div class="min-w-0">
            <p class="font-heading font-extrabold text-2xl text-azul-noche leading-none">{{ p.t('plataforma.cifra_valor', destacado.valor) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ p.t('plataforma.cifra_texto', destacado.texto) }}</p>
          </div>
        </div>
      </div>

      <div v-reveal="150">
        <p class="section-eyebrow">{{ p.t('plataforma.antetitulo', 'Una plataforma al servicio del territorio') }}</p>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">
          {{ p.t('plataforma.titulo', 'Todo lo que necesitas para gestionar la') }} <span class="text-primary-500">{{ p.t('plataforma.destacado', 'Formación Dual') }}</span>
        </h2>
        <p class="text-gray-500 leading-relaxed mt-5">
          {{ p.t('plataforma.descripcion', 'DuaLab facilita a las administraciones la planificación, coordinación y evaluación de los programas de Formación Dual, con información actualizada, trazabilidad y métricas de impacto.') }}
        </p>
        <ul class="mt-6 grid sm:grid-cols-2 gap-3">
          <li v-for="item in plataformaTextos" :key="item.title" class="bg-white rounded-2xl p-4 flex items-start gap-3 shadow-[0_2px_12px_rgba(23,40,62,0.05)]">
            <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" :class="item.soft">
              <component :is="item.icon" class="w-5 h-5" />
            </span>
            <div class="min-w-0">
              <p class="font-heading font-bold text-sm text-azul-noche leading-snug">{{ item.title }}</p>
              <p class="text-xs text-gray-500 leading-snug mt-0.5">{{ item.desc }}</p>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <!-- BENEFICIOS -->
  <section class="bg-white py-20 px-6">
    <div class="max-w-6xl mx-auto">
      <div v-reveal class="mb-10">
        <p class="section-eyebrow">{{ p.t('beneficios.antetitulo', 'Beneficios para las administraciones') }}</p>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">
          {{ p.t('beneficios.titulo', 'Más FP Dual, más talento,') }} <span class="text-primary-500">{{ p.t('beneficios.destacado', 'más territorio') }}</span>
        </h2>
      </div>
      <div v-reveal="100" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="b in beneficiosTextos" :key="b.title" class="bg-superficie rounded-2xl p-5">
          <span class="w-11 h-11 rounded-full flex items-center justify-center text-white shrink-0" :class="b.bg">
            <component :is="b.icon" class="w-5 h-5" />
          </span>
          <h3 class="font-heading font-bold text-azul-noche mt-4">{{ b.title }}</h3>
          <p class="text-sm text-gray-500 leading-snug mt-1">{{ b.desc }}</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CÓMO FUNCIONA -->
  <section class="bg-superficie py-20 px-6">
    <div class="max-w-6xl mx-auto">
      <div v-reveal class="mb-10">
        <p class="section-eyebrow">{{ p.t('como_funciona.antetitulo', 'Cómo funciona') }}</p>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">
          {{ p.t('como_funciona.titulo', 'Implanta y gestiona tus programas en') }} <span class="text-primary-500">{{ p.t('como_funciona.destacado', '4 pasos') }}</span>
        </h2>
      </div>
      <ol v-reveal="100" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <li v-for="(item, i) in pasosTextos" :key="item.title"
            class="bg-white rounded-2xl p-5 shadow-[0_2px_12px_rgba(23,40,62,0.05)]">
          <div class="flex items-center gap-3">
            <span class="w-11 h-11 rounded-full flex items-center justify-center text-white font-heading font-black text-lg shadow-md shrink-0" :class="item.bg">
              {{ i + 1 }}
            </span>
            <!-- Conector hacia el siguiente paso (solo en fila completa) -->
            <span v-if="i < pasos.length - 1" class="hidden lg:flex flex-1 items-center" aria-hidden="true">
              <span class="flex-1 h-px bg-primary-200" />
              <ArrowRightIcon class="w-4 h-4 text-primary-400 -ml-1" />
            </span>
          </div>
          <h3 class="font-heading font-bold text-azul-noche mt-4">{{ item.title }}</h3>
          <p class="text-sm text-gray-500 leading-snug mt-1">{{ item.desc }}</p>
        </li>
      </ol>
      <div v-reveal="150" class="flex flex-wrap gap-3 mt-10">
        <EnlaceCms :to="pasosB1.to" class="btn-pill bg-administraciones hover:bg-administraciones/90 px-8">
          {{ pasosB1.texto }}
        </EnlaceCms>
        <EnlaceCms :to="pasosB2.to" class="btn-pill-outline">
          {{ pasosB2.texto }}
          <ArrowRightIcon class="w-4 h-4" />
        </EnlaceCms>
      </div>
    </div>
  </section>

  <!-- CTA FINAL: mismo banner que la home -->
  <CtaTransforma @agendar="router.push(contactoEntidad)" />
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import dua4 from '@/assets/4_dua.jpeg'
import dua26 from '@/assets/dua_26.jpg'
import consejeriaEducacion from '@/assets/consejeria_educacion.jpg'
import CtaTransforma from '@/components/common/CtaTransforma.vue'
import {
  ArrowRightIcon,
  ArrowsRightLeftIcon,
  BuildingOffice2Icon,
  ChartBarIcon,
  Cog6ToothIcon,
  DocumentCheckIcon,
  DocumentTextIcon,
  GlobeEuropeAfricaIcon,
  LightBulbIcon,
  PresentationChartLineIcon,
  ServerStackIcon,
  SparklesIcon,
  UserGroupIcon,
  ViewfinderCircleIcon,
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
// Todas las acciones de la página llevan al formulario con "Entidad / Administración" marcado
const contactoEntidad = { name: 'contacto', query: { tipo: 'administracion' } }

// Textos editables desde el panel de gestión web (config/cms_paginas.php → entidades)
const p = usePagina('entidades', { titulo: 'Instituciones y administraciones' })
const heroB1 = computed(() => p.boton('hero.boton_principal', { texto: 'Contáctanos', to: contactoEntidad }))
const heroB2 = computed(() => p.boton('hero.boton_secundario', { texto: 'Solicitar demo', to: contactoEntidad }))
const pasosB1 = computed(() => p.boton('como_funciona.boton_principal', { texto: 'Agenda una reunión', to: contactoEntidad }))
const pasosB2 = computed(() => p.boton('como_funciona.boton_secundario', { texto: 'Solicitar demo', to: contactoEntidad }))

// ── Data ───────────────────────────────────────────────────────────────────────
const ventajas = [
  { title: 'Planificación y control', icon: ChartBarIcon, bg: 'bg-centros',
    desc: 'Datos e indicadores para la toma de decisiones basadas en evidencia.' },
  { title: 'Coordinación territorial', icon: UserGroupIcon, bg: 'bg-empresas',
    desc: 'Conecta centros, empresas y agentes del ecosistema en un único entorno.' },
  { title: 'Transparencia y trazabilidad', icon: DocumentTextIcon, bg: 'bg-alumnos',
    desc: 'Seguimiento de prácticas, proyectos y resultados en tiempo real.' },
  { title: 'Optimización de recursos', icon: Cog6ToothIcon, bg: 'bg-administraciones',
    desc: 'Una gestión más eficiente de programas y convenios.' },
  { title: 'Innovación en la FP', icon: LightBulbIcon, bg: 'bg-azul-noche',
    desc: 'Impulsa nuevos modelos adaptados a las necesidades de cada territorio.' },
]

const plataforma = [
  { title: 'Planificación estratégica',     icon: ViewfinderCircleIcon,      soft: 'bg-alumnos/10 text-alumnos',
    desc: 'Define objetivos, programas y convocatorias.' },
  { title: 'Seguimiento y evaluación',      icon: PresentationChartLineIcon, soft: 'bg-administraciones/10 text-administraciones',
    desc: 'Indicadores de impacto y cuadros de mando.' },
  { title: 'Gestión de centros y empresas', icon: BuildingOffice2Icon,       soft: 'bg-empresas/10 text-empresas',
    desc: 'Red de centros, empresas y proyectos en tiempo real.' },
  { title: 'Cumplimiento normativo',        icon: DocumentCheckIcon,         soft: 'bg-primary-100 text-primary-700',
    desc: 'Documentación, convenios y trazabilidad.' },
  { title: 'Informes y datos abiertos',     icon: ServerStackIcon,           soft: 'bg-centros/10 text-centros',
    desc: 'Exportación de datos y evidencias para auditorías.' },
  { title: 'Colaboración interdepartamental', icon: ArrowsRightLeftIcon,     soft: 'bg-alumnos/10 text-alumnos',
    desc: 'Coordina educación, empleo y desarrollo económico.' },
]

const beneficios = [
  { title: 'Desarrollo económico',   icon: ChartBarIcon,          bg: 'bg-centros',
    desc: 'Responde a las necesidades del tejido productivo local.' },
  { title: 'Mayor empleabilidad',    icon: UserGroupIcon,         bg: 'bg-empresas',
    desc: 'Mejora la inserción laboral del alumnado.' },
  { title: 'Eficiencia en la gestión', icon: SparklesIcon,        bg: 'bg-alumnos',
    desc: 'Centraliza la información y reduce la carga administrativa.' },
  { title: 'Impacto social',         icon: GlobeEuropeAfricaIcon, bg: 'bg-administraciones',
    desc: 'Contribuye a un territorio más innovador, inclusivo y sostenible.' },
]

const pasos = [
  { title: 'Configura el programa',        bg: 'bg-centros',          desc: 'Define objetivos, convocatorias y criterios de participación.' },
  { title: 'Conecta agentes',              bg: 'bg-empresas',         desc: 'Invita a centros, empresas y tutores.' },
  { title: 'Gestiona y haz seguimiento',   bg: 'bg-alumnos',          desc: 'Controla prácticas, proyectos y evaluación de resultados.' },
  { title: 'Analiza el impacto',           bg: 'bg-administraciones', desc: 'Accede a informes y métricas para la toma de decisiones.' },
]

// Las listas, con sus textos del panel (iconos y colores se quedan aquí;
// item_N_titulo / item_N_texto siguen el orden de cada lista)
const conTextos = (lista, seccion) => computed(() => lista.map((item, i) => ({
  ...item,
  title: p.t(`${seccion}.item_${i + 1}_titulo`, item.title),
  desc: p.t(`${seccion}.item_${i + 1}_texto`, item.desc),
})))
const ventajasTextos   = conTextos(ventajas, 'ventajas')
const plataformaTextos = conTextos(plataforma, 'plataforma')
const beneficiosTextos = conTextos(beneficios, 'beneficios')
const pasosTextos      = conTextos(pasos, 'como_funciona')

// PENDIENTE DE CONFIRMAR: cifra tomada tal cual de la maqueta
// DuaLab_Web_Administraciones. Sustituir por un dato real con fuente antes
// de publicar en producción (o quitar la tarjeta). Editable en el panel:
// plataforma.cifra_valor / plataforma.cifra_texto.
const destacado = { valor: '+40%', texto: 'de empleabilidad en territorios con FP Dual' }
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
  clip-path: url(#entidades-blob);
}
</style>
