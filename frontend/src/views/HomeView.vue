<template>
  <!-- Formas de recorte reutilizables (coordenadas relativas 0–1 a la imagen) -->
  <svg width="0" height="0" class="absolute" aria-hidden="true">
    <defs>
      <clipPath id="blob-shape" clipPathUnits="objectBoundingBox">
        <path d="M0.06,0.42 C0.12,0.2 0.3,0.26 0.46,0.14 C0.62,0.02 0.8,-0.02 0.93,0.07 C1,0.12 1,0.28 1,0.4 L1,0.86 C1,0.95 0.96,1 0.88,1 L0.18,1 C0.07,1 0.01,0.9 0,0.76 C-0.01,0.62 0.02,0.52 0.06,0.42 Z" />
      </clipPath>
    </defs>
  </svg>

  <!-- HERO -->
  <section class="relative overflow-hidden bg-primary-700 py-24 px-6">
    <!-- Carousel backgrounds -->
    <div
      v-for="(slide, i) in heroSlides"
      :key="i"
      class="absolute inset-0 bg-cover bg-center transition-opacity duration-1000"
      :class="heroIndex === i ? 'opacity-20' : 'opacity-0'"
      :style="{ backgroundImage: `url('${slide.image}')` }"
    />

    <!-- Content: cada elemento entra con delay escalonado (orchestrated entrance) -->
    <div class="relative max-w-5xl mx-auto">
    <div class="max-w-2xl">
      <span class="hero-badge inline-block bg-white/15 text-white px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest mb-6 border border-white/30">
        {{ p.t('hero.antetitulo', 'Innovación Educativa B2B') }}
      </span>
      <h1 class="hero-title text-5xl md:text-6xl font-black tracking-tighter text-white leading-[1.1] mb-5">
        <template v-if="p.tiene('hero.titulo')">{{ p.t('hero.titulo') }}</template><template v-else>Conecta talento<br>con</template>
        <span class="text-transparent bg-clip-text bg-gradient-to-r from-alumnos to-administraciones">{{ p.t('hero.destacado', 'retos reales') }}</span>
      </h1>
      <!-- Editada en el panel → texto plano; sin editar → el original con sus resaltados -->
      <p v-if="p.tiene('hero.descripcion')" class="hero-desc text-white/70 text-base leading-relaxed mb-8 max-w-lg">{{ p.t('hero.descripcion') }}</p>
      <p v-else class="hero-desc text-white/70 text-base leading-relaxed mb-8 max-w-lg">
        DuaLab es la <strong class="text-white font-bold">solución definitiva</strong> para conectar
        <strong class="text-[#8fc46a] font-bold">empresas</strong> con el
        <strong class="text-[#ffb066] font-bold">alumnado en prácticas</strong>.
        Transformamos necesidades empresariales en
        <strong class="text-white font-black">retos académicos</strong>
        para impulsar el <strong class="text-white font-bold">aprendizaje práctico</strong>
        y descubrir <strong class="text-[#5fd0d0] font-black">talento emergente</strong>.
      </p>
      <div class="hero-btns flex flex-wrap md:flex-nowrap gap-3">
        <button @click="comoFuncionaOpen = true"
                class="cta-attention group inline-flex items-center gap-2 px-6 py-3 bg-white text-primary-700 rounded-full font-black text-sm uppercase tracking-widest shadow-[0_8px_25px_rgba(0,0,0,0.2)] hover:shadow-[0_14px_35px_rgba(0,0,0,0.3)] hover:[animation-play-state:paused] transition-all duration-300 hover:-translate-y-0.5 active:scale-95 whitespace-nowrap">
          <svg class="w-4 h-4 transition-transform group-hover:rotate-90 duration-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          {{ p.t('hero.boton_como_funciona', 'Cómo funciona') }}
        </button>

        <EnlaceCms :to="heroDemo.to"
                class="inline-flex items-center gap-2 px-6 py-3 bg-white/10 text-white border-2 border-white/40 rounded-full font-black text-sm uppercase tracking-widest hover:bg-white/20 hover:border-white/70 transition-all duration-300 hover:-translate-y-0.5 active:scale-95 backdrop-blur-sm whitespace-nowrap">
          {{ heroDemo.texto }}
        </EnlaceCms>
      </div>

    </div>
    </div>

    <!-- Carousel dots -->
    <div class="absolute bottom-6 left-0 right-0 flex justify-center gap-2 z-10">
      <button
        v-for="(_, i) in heroSlides"
        :key="i"
        @click="goToSlide(i)"
        :class="[
          'rounded-full transition-all duration-300',
          heroIndex === i ? 'w-6 h-2.5 bg-white' : 'w-2.5 h-2.5 bg-white/40 hover:bg-white/70'
        ]"
        :aria-label="`Diapositiva ${i + 1}`"
      />
    </div>
  </section>

  <!-- STATS -->
  <section class="bg-white pt-8 px-6">
    <div v-reveal class="max-w-3xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 text-center pb-8">
      <StatItem :number="p.t('estadisticas.cifra_1', '50+')"  :label="p.t('estadisticas.etiqueta_1', 'Retos Activos')" />
      <StatItem :number="p.t('estadisticas.cifra_2', '3')"    :label="p.t('estadisticas.etiqueta_2', 'Niveles de Dificultad')" />
      <StatItem :number="p.t('estadisticas.cifra_3', '20+')"  :label="p.t('estadisticas.etiqueta_3', 'Empresas')" />
      <StatItem :number="p.t('estadisticas.cifra_4', '100%')" :label="p.t('estadisticas.etiqueta_4', 'Aplicable a la vida laboral')" />
    </div>
  </section>
  <!-- Franja con los cuatro colores de colectivo -->
  <div class="grid grid-cols-4 h-2" aria-hidden="true">
    <span class="bg-centros" /><span class="bg-empresas" /><span class="bg-alumnos" /><span class="bg-administraciones" />
  </div>

  <!-- ECOSISTEMA -->
  <section id="ecosistema" class="bg-white py-20 px-6">
    <div class="max-w-6xl mx-auto grid xl:grid-cols-[4fr_8fr] gap-14 items-center">
      <div v-reveal>
        <p class="section-eyebrow">{{ p.t('ecosistema.antetitulo', 'Un ecosistema colaborativo') }}</p>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">
          {{ p.t('ecosistema.titulo', 'Todo el ecosistema de la Formación Dual,') }} <span class="text-primary-600">{{ p.t('ecosistema.destacado', 'conectado') }}</span>
        </h2>
        <p v-if="p.tiene('ecosistema.descripcion')" class="text-gray-500 leading-relaxed mt-5 max-w-md">{{ p.t('ecosistema.descripcion') }}</p>
        <p v-else class="text-gray-500 leading-relaxed mt-5 max-w-md">
          DuaLab pone en relación a todos los <strong class="text-centros font-semibold">agentes</strong>
          de la FP Dual en un entorno único, facilitando la <strong class="text-alumnos font-semibold">gestión</strong>,
          la colaboración y la generación de
          <strong class="text-administraciones font-semibold">oportunidades de aprendizaje</strong>
          y <strong class="text-empresas font-semibold">empleo</strong>.
        </p>
        <button type="button" class="btn-pill-outline mt-8" @click="comoFuncionaOpen = true">
          {{ p.t('ecosistema.boton', 'Descubre en qué consiste') }}
        </button>
      </div>
      <div v-reveal="150">
        <EcosystemDiagram :textos="ecosistemaAgentes" />
      </div>
    </div>
  </section>

  <!-- SOLUCIÓN INTEGRAL (funcionalidades clave + servicios actuales) -->
  <section id="servicios" class="bg-superficie py-20 px-6">
    <div v-reveal class="max-w-4xl mx-auto text-center mb-12">
      <p class="section-eyebrow">{{ p.t('funcionalidades.antetitulo', 'Funcionalidades clave') }}</p>
      <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">
        {{ p.t('funcionalidades.titulo', 'Una solución integral para una Formación Dual') }}<br class="hidden md:block">
        <span class="text-primary-600"> {{ p.t('funcionalidades.destacado', 'más eficiente') }}</span>
      </h2>
    </div>
    <div v-reveal="100" class="max-w-6xl mx-auto grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <FeatureCard v-for="f in funcionalidades" :key="f.title"
                   :icon="f.icon" :icon-class="f.iconClass" :title="f.title" :desc="f.desc"
                   @click="activeService = f.modal" />
    </div>

    <!-- Otros servicios (consultoría/formación): ocultos hasta pulsar "Otros servicios" -->
    <div class="max-w-6xl mx-auto text-center mt-10">
      <button
        type="button"
        class="btn-pill-outline"
        :aria-expanded="moreServicesOpen"
        aria-controls="otros-servicios"
        @click="moreServicesOpen = !moreServicesOpen"
      >
        {{ p.t('otros_servicios.boton', 'Otros servicios') }}
        <svg class="w-4 h-4 transition-transform duration-300" :class="moreServicesOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
      </button>
    </div>
    <Transition name="expand">
      <div v-show="moreServicesOpen" id="otros-servicios" class="max-w-6xl mx-auto mt-8 bg-white rounded-2xl p-8 shadow-[0_2px_12px_rgba(23,40,62,0.05)]">
        <p class="text-sm text-gray-500 text-center mb-8">{{ p.t('otros_servicios.descripcion', 'Además de la plataforma, te acompañamos con servicios de consultoría y formación') }}</p>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-8">
          <ServiceItem v-for="s in serviciosItems" :key="s.title"
                       :icon="s.icon" :title="s.title" :desc="s.desc"
                       @click="activeService = s" />
        </div>
      </div>
    </Transition>
  </section>

  <ServiceModal :service="activeService" @close="activeService = null" @contact="onModalContact" />

  <!-- RETOS ACADÉMICOS -->
  <section class="bg-white py-20 px-6 overflow-hidden">
    <div class="max-w-6xl mx-auto grid lg:grid-cols-2 gap-10 items-center">
      <div v-reveal class="relative z-10">
        <p class="section-eyebrow">{{ p.t('retos.antetitulo', 'Aprendizaje real') }}</p>
        <h2 v-if="p.tiene('retos.titulo')" class="text-3xl md:text-[42px] font-extrabold tracking-tight text-azul-noche leading-[1.1]">{{ p.t('retos.titulo') }}</h2>
        <h2 v-else class="text-3xl md:text-[42px] font-extrabold tracking-tight text-azul-noche leading-[1.1]">
          Retos académicos que generan <span class="text-primary-500">talento</span>
          y <span class="text-primary-500">soluciones</span> reales.
        </h2>
        <p v-if="p.tiene('retos.descripcion')" class="text-gray-500 leading-relaxed mt-5 max-w-md">{{ p.t('retos.descripcion') }}</p>
        <p v-else class="text-gray-500 leading-relaxed mt-5 max-w-md">
          Los retos de DuaLab acercan al alumnado a proyectos planteados por empresas,
          fomentando la <strong class="text-primary-600 font-semibold">innovación</strong>, el trabajo en equipo
          y el desarrollo de <strong class="text-primary-600 font-semibold">competencias clave</strong>.
        </p>
        <div class="flex flex-col gap-3 mt-10 max-w-xs">
          <EnlaceCms :to="retosCentro.to" class="btn-pill bg-centros hover:bg-centros/90">{{ retosCentro.texto }}</EnlaceCms>
          <EnlaceCms :to="retosEmpresa.to" class="btn-pill bg-empresas hover:bg-empresas/90">{{ retosEmpresa.texto }}</EnlaceCms>
          <!-- Formulario de alumnado bloqueado de momento (ver contactoConfig.js) -->
          <span class="btn-pill bg-alumnos/50 shadow-none cursor-not-allowed hover:translate-y-0" aria-disabled="true" title="Próximamente">
            {{ p.t('retos.boton_alumnado', 'Encuentra tus prácticas') }}
            <span class="text-[10px] font-black bg-white/25 rounded-full px-2 py-0.5 normal-case tracking-normal">Próximamente</span>
          </span>
          <EnlaceCms :to="retosExplorar.to" class="btn-pill bg-administraciones hover:bg-administraciones/90">{{ retosExplorar.texto }}</EnlaceCms>
        </div>
      </div>

      <div v-reveal="150" class="relative">
        <!-- Rotulación manuscrita con flechas hacia la imagen -->
        <div class="absolute left-[18%] -top-2 md:-top-6 z-10 font-hand text-primary-600 text-2xl md:text-3xl leading-7 -rotate-6" aria-hidden="true">
          Ideas de hoy<br>para el mundo<br>de mañana
        </div>
        <svg class="absolute left-[12%] top-14 md:top-9 w-16 h-24 z-10" viewBox="0 0 100 120" fill="none" aria-hidden="true">
          <path d="M10 8 C 0 50, 20 90, 70 108" stroke="#FF8920" stroke-width="2.5" stroke-linecap="round" />
          <path d="M60 100 L72 109 L58 114" stroke="#FF8920" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <svg class="absolute left-[40%] top-24 md:top-20 w-24 h-16 z-10" viewBox="0 0 100 60" fill="none" aria-hidden="true">
          <path d="M8 6 C 10 36, 40 50, 88 44" stroke="#19A7A8" stroke-width="2.5" stroke-linecap="round" />
          <path d="M78 36 L90 44 L78 52" stroke="#19A7A8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <img :src="p.imagen('retos.imagen', dua26)" alt="Alumnado trabajando en un reto en el aula"
             class="blob-image w-full h-[340px] md:h-[440px] object-cover mt-20 md:mt-16" loading="lazy" />
      </div>
    </div>
  </section>

  <!-- UNA SOLUCIÓN PARA CADA AGENTE (antes: Para Empresas / Centros / Alumnos) -->
  <section class="bg-superficie py-20 px-6">
    <div v-reveal class="max-w-4xl mx-auto text-center mb-10">
      <p class="section-eyebrow">{{ p.t('agentes.antetitulo', 'Cuatro agentes, un mismo objetivo') }}</p>
      <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche">{{ p.t('agentes.titulo', 'Una solución para cada agente') }}</h2>
    </div>
    <div class="max-w-6xl mx-auto grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <div v-for="(a, i) in agentes" :id="a.id" :key="a.title" v-reveal="i * 100" class="scroll-mt-24">
        <AgentCard :title="a.title" :desc="a.desc" :image="a.image" :bullets="a.bullets" :color="a.color"
                   :texto-boton="p.t('agentes.boton', 'Más información')"
                   @more="activeService = a.modal" />
      </div>
    </div>
  </section>

  <!-- PLATAFORMA: toda la gestión en un mismo lugar -->
  <section id="plataforma" class="bg-white py-20 px-6 scroll-mt-16">
    <div class="max-w-6xl mx-auto grid lg:grid-cols-[7fr_5fr] gap-12 items-center">
      <div v-reveal class="order-2 lg:order-1">
        <PlatformMockup />
      </div>
      <div v-reveal="150" class="order-1 lg:order-2">
        <p class="section-eyebrow">{{ p.t('plataforma.antetitulo', 'Plataforma DuaLab') }}</p>
        <h2 v-if="p.tiene('plataforma.titulo')" class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">{{ p.t('plataforma.titulo') }}</h2>
        <h2 v-else class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche leading-tight">
          Toda la <span class="text-primary-500">gestión</span><br>en un <span class="text-primary-500">mismo lugar</span>
        </h2>
        <p class="text-gray-500 leading-relaxed mt-5">
          {{ p.t('plataforma.descripcion', 'Una plataforma intuitiva y completa para gestionar prácticas, tutores, documentación, comunicaciones y mucho más, con información siempre actualizada y accesible para todos los agentes.') }}
        </p>
        <ul class="space-y-2 mt-6">
          <li v-for="item in plataformaFeatures" :key="item" class="flex items-center gap-2.5 font-medium text-azul-noche">
            <span class="w-5 h-5 rounded-full bg-administraciones flex items-center justify-center shrink-0">
              <svg width="10" height="10" viewBox="0 0 10 10" fill="none" aria-hidden="true"><path d="M1.5 5l2.5 2.5L8.5 2" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            {{ item }}
          </li>
        </ul>
        <div class="flex flex-wrap gap-3 mt-8">
          <EnlaceCms :to="plataformaDemo.to" class="btn-pill bg-primary-600 hover:bg-primary-700 px-8">{{ plataformaDemo.texto }}</EnlaceCms>
          <!-- Enlace externo: la herramienta es otra SPA (mismo botón que en el navbar) -->
          <a :href="`${toolUrl}/login`" class="btn-pill-outline">{{ p.t('plataforma.boton_herramienta', 'Usar DuaLab') }}</a>
        </div>
      </div>
    </div>
  </section>

  <!-- GALERÍA -->
  <section id="experiencia" class="bg-superficie py-20 px-6 scroll-mt-16">
    <div v-reveal class="max-w-5xl mx-auto text-center mb-10">
      <div class="inline-block bg-primary-100 text-primary-700 px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest mb-4 border border-primary-200">{{ p.t('galeria.etiqueta', 'Nuestra galería') }}</div>
      <h2 class="text-3xl font-black tracking-tighter text-azul-noche">{{ p.t('galeria.titulo', 'Hablamos con experiencia') }}</h2>
      <p class="text-gray-500 mt-2 font-medium">{{ p.t('galeria.subtitulo', 'Momentos reales de formación, retos y trabajo en equipo') }}</p>
    </div>
    <div v-reveal="100" class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-5">
      <button v-for="img in galeriaImages" :key="img" type="button"
              class="block rounded-2xl overflow-hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-2"
              aria-label="Ampliar foto"
              @click="openGallery(img)">
        <img :src="img" alt="Sesión de trabajo en DuaLab"
             class="w-full h-64 object-cover rounded-2xl hover:scale-[1.02] transition-transform duration-300 cursor-zoom-in"
             loading="lazy" />
      </button>
    </div>
    <div v-reveal="150" class="text-center mt-10">
      <button
        type="button"
        class="inline-flex items-center gap-2 px-7 py-3.5 bg-white text-[#1F2937] border-2 border-gray-200 rounded-full font-black text-sm uppercase tracking-widest hover:border-primary-600 hover:text-primary-600 transition-all duration-300 hover:-translate-y-0.5 active:scale-95"
        @click="openGallery()"
      >
        {{ p.t('galeria.boton_ver_mas', 'Ver más') }}
      </button>
    </div>
  </section>

  <GalleryModal :open="galleryOpen" :images="allGalleryImages" :start-index="galleryStart" @close="galleryOpen = false" />

  <!-- CÓMO FUNCIONA: modal que abren "Cómo funciona" (hero) y "Descubre en qué consiste" -->
  <ComoFuncionaModal :open="comoFuncionaOpen" :textos="comoFunciona" @close="comoFuncionaOpen = false" @demo="onComoFuncionaDemo" />

  <!-- FAMILIAS PROFESIONALES (solo el rótulo y "Profesionales" en tono Alumnado) -->
  <section class="bg-white py-20 px-6">
    <div v-reveal class="max-w-4xl mx-auto text-center mb-8">
      <p class="section-eyebrow !text-alumnos">{{ p.t('familias.antetitulo', 'Formación') }}</p>
      <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-azul-noche">{{ p.t('familias.titulo', 'Familias') }} <span class="text-alumnos">{{ p.t('familias.destacado', 'Profesionales') }}</span></h2>
      <p class="text-gray-500 mt-2 font-medium">{{ p.t('familias.subtitulo', 'Retos organizados en áreas clave del mercado laboral') }}</p>
    </div>
    <div v-reveal="100" class="text-center">
      <EnlaceCms :to="familiasBoton.to" class="btn-pill bg-primary-600 hover:bg-primary-700 px-8">{{ familiasBoton.texto }}</EnlaceCms>
    </div>
  </section>


  <!-- CTA FINAL: transforma la Formación Dual -->
  <CtaTransforma @agendar="goToContact()" />
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import dua1 from '@/assets/1_dua.jpeg'
import dua2 from '@/assets/2_dua.jpg'
import dua3 from '@/assets/3_dua.jpg'
import dua4 from '@/assets/4_dua.jpeg'
import dua5 from '@/assets/5_dua.jpeg'
import dua6 from '@/assets/6_dua.jpg'
import dua7 from '@/assets/7_dua.jpg'
import dua8 from '@/assets/8_dua.jpg'
import dua9  from '@/assets/dua_9.jpg'
import dua10 from '@/assets/dua_10.jpeg'
import dua11 from '@/assets/dua_11.jpeg'
import dua12 from '@/assets/dua_12.jpeg'
import dua13 from '@/assets/dua_13.jpg'
import dua14 from '@/assets/dua_14.jpeg'
import dua16 from '@/assets/dua_16.jpg'
import dua17 from '@/assets/dua_17.jpg'
import dua18 from '@/assets/dua_18.jpg'
import dua19 from '@/assets/dua_19.jpg'
import dua20 from '@/assets/dua_20.jpg'
import dua21 from '@/assets/dua_21.jpg'
import dua22 from '@/assets/dua_22.jpg'
import dua23 from '@/assets/dua_23.jpg'
import dua24 from '@/assets/dua_24.jpg'
import dua25 from '@/assets/dua_25.jpg'
import dua26 from '@/assets/dua_26.jpg'
import dua27 from '@/assets/dua_27.jpg'
import dua28 from '@/assets/dua_28.jpg'
import dua29 from '@/assets/dua_29.jpg'
import dua30 from '@/assets/dua_30.jpg'
import dua31 from '@/assets/dua_31.jpg'
import dua32 from '@/assets/dua_32.jpg'
import dua33 from '@/assets/dua_33.jpg'
import centroEducativo from '@/assets/centro_educativo.jpg'
import consejeriaEducacion from '@/assets/consejeria_educacion.jpg'
import StatItem        from '@/components/home/StatItem.vue'
import EcosystemDiagram from '@/components/home/EcosystemDiagram.vue'
import FeatureCard     from '@/components/home/FeatureCard.vue'
import AgentCard       from '@/components/home/AgentCard.vue'
import PlatformMockup  from '@/components/home/PlatformMockup.vue'
import ComoFuncionaModal from '@/components/home/ComoFuncionaModal.vue'
import CtaTransforma from '@/components/common/CtaTransforma.vue'
import ServiceItem     from '@/components/home/ServiceItem.vue'
import ServiceModal    from '@/components/home/ServiceModal.vue'
import GalleryModal    from '@/components/home/GalleryModal.vue'
import EnlaceCms       from '@/components/common/EnlaceCms.vue'
import { usePagina }   from '@/composables/usePagina'
import {
  BriefcaseIcon,
  PresentationChartBarIcon,
  ClipboardDocumentListIcon,
  ArrowPathIcon,
  BookOpenIcon,
  MapIcon,
  MagnifyingGlassIcon,
  CheckBadgeIcon,
  NewspaperIcon,
  UserGroupIcon,
  ArrowTrendingUpIcon,
  CalendarDaysIcon,
  LightBulbIcon,
  ChartBarIcon,
  ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline'

// ── Directiva local: scroll-reveal (Intersection Observer) ────────────────────
// Uso: v-reveal (sin delay) o v-reveal="200" (con 200ms de delay)
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

// ── Textos editables desde el panel de gestión web (config/cms_paginas.php → inicio) ──
const p = usePagina('inicio', { titulo: 'Conecta talento con retos reales' })
const heroDemo       = computed(() => p.boton('hero.boton_demo', { texto: 'Solicitar demo', to: { name: 'contacto' } }))
const retosCentro    = computed(() => p.boton('retos.boton_centro', { texto: 'Inscribe tu centro', to: { name: 'contacto', query: { tipo: 'centro' } } }))
const retosEmpresa   = computed(() => p.boton('retos.boton_empresa', { texto: 'Participa como empresa', to: { name: 'contacto', query: { tipo: 'empresa' } } }))
const retosExplorar  = computed(() => p.boton('retos.boton_explorar', { texto: 'Explora retos', to: { name: 'familias' } }))
const plataformaDemo = computed(() => p.boton('plataforma.boton_demo', { texto: 'Solicitar demo', to: { name: 'contacto' } }))
const familiasBoton  = computed(() => p.boton('familias.boton', { texto: 'Ver familias', to: { name: 'familias' } }))

// ── Carousel ──────────────────────────────────────────────────────────────────
const slidesOriginales = [
  { image: dua1 },
  { image: dua2 },
  { image: dua3 },
  { image: dua4 },
  { image: dua5 },
  { image: dua6 },
  { image: dua7 },
  { image: dua8 },
]
// Fotos del carrusel y de la galería: las del panel (Páginas → Inicio) o las originales
const fotosPanel = campo => (p.valores.value[campo]?.length ? p.valores.value[campo] : null)
const heroSlides = computed(() => fotosPanel('hero.fotos_carrusel')?.map(image => ({ image })) ?? slidesOriginales)

const activeService = ref(null)
const moreServicesOpen = ref(false)

// La herramienta DuaLab (otra SPA): mismo destino que "Usar DuaLab" del navbar.
const toolUrl = import.meta.env.VITE_TOOL_URL ?? (import.meta.env.DEV ? 'http://localhost:5173' : 'https://dualab.es')

const galeriaImages = computed(() => fotosPanel('galeria.fotos')?.slice(0, 3) ?? [dua11, dua14, dua21])
const galleryOpen = ref(false)
const galleryStart = ref(0)
// Abre la galería en la foto pulsada (o en la primera desde "Ver más").
function openGallery(img) {
  galleryStart.value = img ? Math.max(allGalleryImages.value.indexOf(img), 0) : 0
  galleryOpen.value = true
}
const galeriaOriginal = [
  dua1, dua2, dua3, dua4, dua5, dua6, dua7, dua8,
  dua9, dua10, dua11, dua12, dua13, dua14,
  dua16, dua17, dua18, dua19, dua20, dua21,
  dua22, dua23, dua24, dua25, dua26,
  dua27, dua28, dua29, dua30, dua32,
]
const allGalleryImages = computed(() => fotosPanel('galeria.fotos') ?? galeriaOriginal)

const heroIndex = ref(0)
let heroTimer = null

function goToSlide(i) {
  heroIndex.value = i
  clearInterval(heroTimer)
  heroTimer = setInterval(nextSlide, 5000)
}

function nextSlide() {
  heroIndex.value = (heroIndex.value + 1) % heroSlides.value.length
}

onMounted(() => { heroTimer = setInterval(nextSlide, 5000) })
onUnmounted(() => { clearInterval(heroTimer) })

// ── Navigation ─────────────────────────────────────────────────────────────────
const router = useRouter()
const comoFuncionaOpen = ref(false)
// "Solicitar demo" del modal: misma acción que el del hero (página de contacto).
function onComoFuncionaDemo() {
  comoFuncionaOpen.value = false
  goToContact()
}

// Todos los "Solicitar demo", "Contactar"… llevan a /contacto; con `tipo`
// ('centro', 'empresa', 'administracion') se abre con ese agente marcado.
function goToContact(tipo = '') {
  router.push({ name: 'contacto', query: tipo ? { tipo } : {} })
}
function onModalContact(tipo) {
  activeService.value = null
  goToContact(tipo)
}

// ── Data ───────────────────────────────────────────────────────────────────────
const empresasFeatures = [
  { title: 'Reclutamiento eficiente', desc: 'Evalúa candidatos por sus resultados en retos prácticos' },
  { title: 'Formación a medida',      desc: 'Propón retos alineados con tus necesidades específicas' },
  { title: 'Visibilidad de marca',    desc: 'Posiciona tu empresa como referente en formación dual' },
]
const centrosFeatures = [
  { title: 'Contenido actualizado', desc: 'Retos alineados con las demandas del mercado laboral' },
  { title: 'Red de empresas',       desc: 'Facilita prácticas y empleo para tus alumnos' },
  { title: 'Seguimiento real',      desc: 'Mide el progreso de tus estudiantes en tiempo real' },
]
const alumnosFeatures = [
  { title: 'Experiencia práctica',   desc: 'Aplica tus conocimientos en situaciones reales' },
  { title: 'Portfolio profesional',  desc: 'Construye un portfolio de proyectos reales' },
  { title: 'Oportunidades laborales',desc: 'Conecta con empresas que buscan tu perfil' },
]

// Funcionalidades clave: cada tarjeta abre el mismo ServiceModal que "Otros
// servicios", con contenido tomado de los servicios relacionados.
const DEMO = { label: 'Solicitar demo', tipo: '' }
const funcionalidadesOriginales = [
  {
    title: 'Gestión de prácticas', icon: CalendarDaysIcon, iconClass: 'text-centros',
    desc: 'Publicación de ofertas, asignación y seguimiento en un único entorno.',
    modal: {
      badge: 'Funcionalidad clave', icon: CalendarDaysIcon, image: dua24,
      title: 'Gestión de prácticas',
      description: 'Centraliza la **gestión de las prácticas de FP Dual**: publicación de ofertas, asignación del alumnado y seguimiento en un único entorno.',
      highlights: [
        { title: 'Procedimientos eficientes', desc: 'Diseñamos los flujos de trabajo necesarios para implantar la dual.' },
        { title: 'Planificación estratégica', desc: 'Diseño y organización de cada etapa del proyecto.' },
        { title: 'Evaluación continua', desc: 'Sistemas de seguimiento que garantizan una implementación sostenible.' },
      ],
      contact: DEMO,
    },
  },
  {
    title: 'Seguimiento del alumnado', icon: UserGroupIcon, iconClass: 'text-alumnos',
    desc: 'Evolución, tutorías y progreso en tiempo real.',
    modal: {
      badge: 'Funcionalidad clave', icon: UserGroupIcon, image: dua19,
      title: 'Seguimiento del alumnado',
      description: 'Sigue la **evolución de cada estudiante**: tutorías, progreso y resultados en tiempo real, con orientación hacia sus oportunidades formativas y laborales.',
      highlights: [
        { title: 'Seguimiento real', desc: 'Mide el progreso de tus estudiantes en tiempo real.' },
        { title: 'Orientación profesional', desc: 'Guiamos al alumnado hacia oportunidades alineadas con su perfil.' },
        { title: 'Portfolio profesional', desc: 'Cada reto resuelto suma a un portfolio de proyectos reales.' },
      ],
      contact: DEMO,
    },
  },
  {
    title: 'Conexión con empresas', icon: BriefcaseIcon, iconClass: 'text-empresas',
    desc: 'Captación de empresas, gestión de convenios y colaboración continua.',
    modal: {
      badge: 'Funcionalidad clave', icon: BriefcaseIcon, image: dua20,
      title: 'Conexión con empresas',
      description: 'Captamos y acompañamos a las **empresas colaboradoras**: prospección, convenios y formación de sus tutores para una colaboración continua.',
      highlights: [
        { title: 'Prospección efectiva', desc: 'Prospección de empresas para proyectos educativos en varias comunidades autónomas.' },
        { title: 'Formación de tutores de empresa', desc: 'Tutores formados en proyectos de FP Dual en todo el territorio español.' },
        { title: 'Red de empresas', desc: 'Facilita prácticas y empleo para tus alumnos.' },
      ],
      contact: DEMO,
      link: { to: { name: 'empresas' }, label: 'Empresas' },
    },
  },
  {
    title: 'Retos y proyectos', icon: LightBulbIcon, iconClass: 'text-administraciones',
    desc: 'Proyectos reales que conectan talento y necesidades del entorno productivo.',
    modal: {
      badge: 'Funcionalidad clave', icon: LightBulbIcon, image: dua18,
      title: 'Retos y proyectos',
      description: 'Transformamos **necesidades reales de las empresas** en retos académicos alineados al currículo oficial, gestionados de principio a fin.',
      highlights: [
        { title: 'La empresa propone', desc: 'Identifica una fricción o cuello de botella real en su día a día.' },
        { title: 'La IA lo transforma', desc: 'DuaLab genera un reto académico alineado al currículo oficial.' },
        { title: 'El alumnado resuelve', desc: 'Gana experiencia práctica mientras aporta valor real a la empresa.' },
      ],
      contact: DEMO,
      link: { to: { name: 'familias' }, label: 'Explorar retos' },
    },
  },
  {
    title: 'Datos e indicadores', icon: ChartBarIcon, iconClass: 'text-empresas',
    desc: 'Información clave para la toma de decisiones.',
    modal: {
      badge: 'Funcionalidad clave', icon: ChartBarIcon, image: dua33,
      title: 'Datos e indicadores',
      description: 'Indicadores y reportes para **analizar, gestionar y alcanzar tus objetivos formativos**, con el respaldo de nuestra consultoría especializada.',
      highlights: [
        { title: 'Indicadores y reportes', desc: 'Información siempre actualizada para la toma de decisiones.' },
        { title: 'Estrategias a medida', desc: 'Planes de transformación adaptados a cada organización.' },
        { title: 'Acompañamiento estratégico', desc: 'Te ayudamos a analizar, gestionar y alcanzar tus objetivos formativos.' },
      ],
      contact: DEMO,
    },
  },
  {
    title: 'Comunicación y coordinación', icon: ChatBubbleLeftRightIcon, iconClass: 'text-centros',
    desc: 'Un espacio común para todos los agentes, con comunicaciones ágiles y trazables.',
    modal: {
      badge: 'Funcionalidad clave', icon: ChatBubbleLeftRightIcon, image: dua14,
      title: 'Comunicación y coordinación',
      description: 'Un **espacio común** para centros educativos, empresas, alumnado y administraciones, con comunicaciones ágiles y trazables.',
      highlights: [
        { title: 'Todos los agentes conectados', desc: 'Centros, empresas, alumnado y administraciones en un mismo entorno.' },
        { title: 'Adaptación al entorno', desc: 'Trabajamos con centros, empresas y administraciones según sus necesidades.' },
        { title: 'Recursos prácticos', desc: 'Guías y materiales aplicables directamente en el día a día formativo.' },
      ],
      contact: DEMO,
      link: { to: { name: 'noticias' }, label: 'Ver publicaciones y noticias' },
    },
  },
]

const plataformaFeaturesOriginales = [
  'Seguimiento en tiempo real',
  'Gestión documental',
  'Tutorías y evaluaciones',
  'Indicadores y reportes',
  'Comunicación integrada',
]

// "Una solución para cada agente": la tarjeta muestra lo esencial y "Más
// información" abre el modal con el contenido de las antiguas secciones
// Para Empresas / Centros / Alumnos (mismo ServiceModal que los servicios).
const agentesOriginales = [
  {
    title: 'Centros Educativos', color: 'centros', image: centroEducativo,
    desc: 'Mejora la empleabilidad de tus estudiantes.',
    bullets: ['Gestión integral de la FP Dual', 'Coordinación con empresas', 'Seguimiento del alumnado', 'Acceso a indicadores'],
    modal: {
      badge: 'Para Centros Educativos', image: centroEducativo,
      title: 'Mejora la empleabilidad de tus estudiantes',
      description: 'Accede a retos actualizados y conecta con empresas que buscan talento. Ofrece formación práctica y relevante para el mercado laboral.',
      highlights: centrosFeatures,
      contact: { label: 'Solicitar demo', tipo: 'centro' },
      link: { to: { name: 'centros' }, label: 'Centros' },
    },
  },
  {
    title: 'Empresas', color: 'empresas', image: dua32,
    desc: 'Identifica y atrae talento cualificado.',
    bullets: ['Acceso a talento cualificado', 'Plantea retos y proyectos', 'Participación en la formación', 'Refuerza la marca empleadora'],
    modal: {
      badge: 'Para Empresas', image: dua32,
      title: 'Identifica y atrae talento cualificado',
      description: 'Propón retos reales y encuentra candidatos con habilidades demostradas. Conecta con centros educativos para formar a los profesionales que necesitas.',
      highlights: empresasFeatures,
      contact: { label: 'Contactar ahora', tipo: 'empresa' },
      link: { to: { name: 'empresas' }, label: 'Empresas' },
    },
  },
  {
    // id: destino del enlace "Alumnos" del navbar (#para-alumnos)
    id: 'para-alumnos',
    title: 'Alumnado', color: 'alumnos', image: dua4,
    desc: 'Desarrolla habilidades que importan.',
    bullets: ['Encuentra prácticas', 'Participa en retos', 'Desarrolla tus competencias', 'Conecta con empresas'],
    modal: {
      badge: 'Para Alumnos', image: dua4,
      title: 'Desarrolla habilidades que importan',
      description: 'Resuelve retos reales de empresas, construye tu portfolio y destaca en tu carrera. Aprende haciendo y demuestra tu valía.',
      highlights: alumnosFeatures,
      link: { to: { name: 'alumnos' }, label: 'Alumnado' },
    },
  },
  {
    title: 'Administraciones', color: 'administraciones', image: consejeriaEducacion,
    desc: 'Impulsa y haz crecer la Formación Dual.',
    bullets: ['Impulso de la Formación Dual', 'Gestión de programas', 'Monitorización del impacto', 'Facilita la colaboración'],
    modal: {
      badge: 'Para Administraciones', image: consejeriaEducacion,
      title: 'Impulsa la Formación Dual en tu territorio',
      description: 'Coordina **programas de FP Dual**, mide su impacto y facilita la colaboración entre centros educativos y empresas desde un mismo entorno.',
      highlights: [
        { title: 'Gestión de programas',      desc: 'Visión conjunta de centros, empresas y alumnado participantes.' },
        { title: 'Monitorización del impacto', desc: 'Indicadores para evaluar y mejorar la Formación Dual.' },
        { title: 'Colaboración entre agentes', desc: 'Un espacio común para coordinar a todo el ecosistema.' },
      ],
      link: { to: { name: 'entidades' }, label: 'Administraciones' },
    },
  },
]

const serviciosOriginales = [
  {
    title: 'Consultoría y asesoría',
    icon: PresentationChartBarIcon,
    desc: 'Asesoría y estrategias educativas y empresariales.',
    image: dua33,
    description: 'Prestamos servicios de **asesoría y consultoría especializada en formación**, desarrollando estrategias personalizadas para centros educativos, empresas y administraciones públicas.',
    highlights: [
      { title: 'Más de 12 años de experiencia', desc: 'Profundo conocimiento de la normativa y los procedimientos del sector formativo.' },
      { title: 'Estrategias a medida', desc: 'Planes de transformación adaptados a cada organización.' },
      { title: 'Acompañamiento estratégico', desc: 'Te ayudamos a analizar, gestionar y alcanzar tus objetivos formativos.' },
    ],
  },
  {
    title: 'Gestión de Proyectos',
    icon: ClipboardDocumentListIcon,
    desc: 'Gestión integral de proyectos formativos.',
    image: dua18,
    description: 'En DuaLab gestionamos **proyectos formativos de manera integral**, desde el diseño inicial hasta la evaluación final.',
    highlights: [
      { title: 'Planificación estratégica', desc: 'Diseño y organización de cada etapa del proyecto.' },
      { title: 'Ejecución eficiente', desc: 'Control de costes y cumplimiento de los objetivos establecidos.' },
      { title: 'Adaptación al entorno', desc: 'Trabajamos con centros, empresas y administraciones según sus necesidades.' },
    ],
  },
  {
    title: 'Ruta a la Dual',
    icon: ArrowPathIcon,
    desc: 'Acompañamiento en la transición hacia la dual.',
    image: dua24,
    description: 'Ofrecemos un **servicio integral de acompañamiento** a los agentes del ecosistema educativo para facilitar su transición hacia la metodología dual.',
    highlights: [
      { title: 'Formaciones específicas', desc: 'Adaptadas a las necesidades de cada centro o territorio.' },
      { title: 'Procedimientos eficientes', desc: 'Diseñamos los flujos de trabajo necesarios para implantar la dual.' },
      { title: 'Evaluación continua', desc: 'Sistemas de seguimiento que garantizan una implementación sostenible.' },
    ],
  },
  {
    title: 'Formación de tutores de empresa',
    icon: BookOpenIcon,
    desc: 'Formación flexible para tutores de FP.',
    image: dua31,
    description: 'Hemos formado a **tutores de empresa** en proyectos de FP Dual en todo el territorio español.',
    highlights: [
      { title: 'Contenidos propios y actualizados', desc: 'Alineados con la normativa vigente en cada momento.' },
      { title: 'Plataforma interactiva', desc: 'Gestionada por profesionales con amplia experiencia en el sector.' },
      { title: 'Formación a medida', desc: 'Adaptada a sectores productivos, normativa autonómica y disponibilidad horaria.' },
    ],
  },
  {
    title: 'Orientación Profesional',
    icon: MapIcon,
    desc: 'Fortalecemos la orientación profesional educativa.',
    image: dua19,
    description: 'Guiamos a **jóvenes, desempleados y trabajadores** hacia oportunidades formativas y laborales alineadas con su perfil.',
    highlights: [
      { title: 'Cobertura nacional', desc: 'Hemos formado a orientadores de todas las comunidades autónomas.' },
      { title: 'Herramientas actualizadas', desc: 'Conocimientos prácticos para potenciar el éxito educativo y profesional.' },
      { title: 'Talleres especializados', desc: 'Dirigidos a orientadores educativos y del ámbito del empleo.' },
    ],
  },
  {
    title: 'Prospección Efectiva',
    icon: MagnifyingGlassIcon,
    desc: 'Formación práctica para prospectores educativos.',
    image: dua20,
    description: 'Capacitamos a **profesionales de la prospección** en todos los sectores y niveles educativos.',
    highlights: [
      { title: 'Amplia experiencia', desc: 'Prospección de empresas para proyectos educativos en varias comunidades autónomas.' },
      { title: 'Contenidos adaptados', desc: 'A la normativa autonómica y a la realidad socioeducativa de cada entorno.' },
      { title: 'Formación práctica', desc: 'Alineada con las necesidades reales del mercado laboral y educativo.' },
    ],
  },
  {
    title: 'Acreditación de competencias',
    icon: CheckBadgeIcon,
    desc: 'Acreditación de competencias profesionales.',
    image: dua9,
    description: 'Gestionamos proyectos de **acreditación de competencias profesionales** para diversos colectivos en distintas comunidades autónomas.',
    highlights: [
      { title: 'Itinerarios personalizados', desc: 'Alineados con las necesidades del mercado laboral.' },
      { title: 'Soporte especializado', desc: 'Actuamos en representación o apoyo de los profesionales implicados.' },
      { title: 'Conocimiento territorial', desc: 'Dominio de los procedimientos específicos de cada comunidad autónoma.' },
    ],
  },
  {
    title: 'Publicaciones',
    icon: NewspaperIcon,
    desc: 'Publicaciones sobre formación dual.',
    image: dua16,
    description: 'Desarrollamos **guías, manuales y estudios** que abordan los aspectos clave de la formación dual.',
    highlights: [
      { title: 'Recursos prácticos', desc: 'Materiales aplicables directamente en el día a día formativo.' },
      { title: 'Enfoque estratégico', desc: 'Contenidos pensados para facilitar la implementación de la dual.' },
      { title: 'Compromiso con la excelencia', desc: 'Reflejo de nuestra experiencia en el ámbito educativo y empresarial.' },
    ],
    link: { to: { name: 'noticias' }, label: 'Ver publicaciones y noticias' },
  },
  {
    title: 'HR Itinerarios Formativos',
    icon: UserGroupIcon,
    desc: 'Creación de perfiles profesionales adaptados.',
    image: dua12,
    description: 'Ayudamos a las empresas a **desarrollar perfiles profesionales** adaptados a las competencias que necesitan sus posiciones clave.',
    highlights: [
      { title: 'Perfiles híbridos', desc: 'Creación de roles adaptados a los nuevos retos del mercado laboral.' },
      { title: 'Actualización de competencias', desc: 'Fortalecemos el talento de los equipos ya existentes.' },
      { title: 'Soluciones a medida', desc: 'Diseñadas para impulsar tanto el talento individual como el crecimiento organizacional.' },
    ],
  },
  {
    title: 'Expansión educativa',
    icon: ArrowTrendingUpIcon,
    desc: 'Desarrollo estratégico para instituciones educativas.',
    image: dua10,
    description: 'Colaboramos con **instituciones educativas** para impulsar el desarrollo y crecimiento de sus modelos de negocio.',
    highlights: [
      { title: 'Oportunidades estratégicas', desc: 'Identificación y optimización de la oferta educativa.' },
      { title: 'Apertura de centros', desc: 'Liderazgo en la acreditación y apertura de nuevos centros educativos.' },
      { title: 'Cumplimiento normativo', desc: 'Procesos ágiles y exitosos, garantizando el marco legal en todo momento.' },
    ],
  },
]

// ── Textos de las tarjetas editables desde el panel (config/cms_paginas.php → inicio) ──
// Iconos, colores, imágenes y modales se quedan en los arrays de arriba; aquí solo se
// sustituyen los textos que se ven en la página (campos item_N_* de cada sección).

// Funcionalidades clave: el título del modal es el mismo que el de la tarjeta
const funcionalidades = computed(() => funcionalidadesOriginales.map((f, i) => {
  const title = p.t(`funcionalidades.item_${i + 1}_titulo`, f.title)
  return { ...f, title, desc: p.t(`funcionalidades.item_${i + 1}_texto`, f.desc), modal: { ...f.modal, title } }
}))

// Otros servicios: el modal (activeService = s) muestra el mismo título
const serviciosItems = computed(() => serviciosOriginales.map((s, i) => ({
  ...s,
  title: p.t(`otros_servicios.item_${i + 1}_titulo`, s.title),
  desc: p.t(`otros_servicios.item_${i + 1}_texto`, s.desc),
})))

// Una solución para cada agente: título, frase y los cuatro puntos de cada tarjeta
const agentes = computed(() => agentesOriginales.map((a, i) => ({
  ...a,
  title: p.t(`agentes.item_${i + 1}_titulo`, a.title),
  desc: p.t(`agentes.item_${i + 1}_texto`, a.desc),
  bullets: a.bullets.map((b, j) => p.t(`agentes.item_${i + 1}_punto_${j + 1}`, b)),
})))

const plataformaFeatures = computed(() => plataformaFeaturesOriginales.map((f, i) => p.t(`plataforma.item_${i + 1}_texto`, f)))

// Diagrama del ecosistema (EcosystemDiagram): nombre y frase de cada agente, en su orden
const ecosistemaAgentes = computed(() => [
  { titulo: p.t('ecosistema.item_1_titulo', 'Centros Educativos'), texto: p.t('ecosistema.item_1_texto', 'Gestionan, coordinan y potencian la FP Dual') },
  { titulo: p.t('ecosistema.item_2_titulo', 'Empresas'), texto: p.t('ecosistema.item_2_texto', 'Apoyan el talento y colaboran en proyectos reales') },
  { titulo: p.t('ecosistema.item_3_titulo', 'Alumnado'), texto: p.t('ecosistema.item_3_texto', 'Desarrolla su talento en entornos profesionales') },
  { titulo: p.t('ecosistema.item_4_titulo', 'Administraciones'), texto: p.t('ecosistema.item_4_texto', 'Impulsan y hacen crecer la Formación Dual') },
])

// Modal «¿Cómo funciona DuaLab?» (ComoFuncionaModal)
const comoFunciona = computed(() => ({
  antetitulo: p.t('como_funciona.antetitulo', 'Flujo de trabajo'),
  titulo: p.t('como_funciona.titulo', '¿Cómo funciona DuaLab?'),
  descripcion: p.t('como_funciona.descripcion', 'Tres pasos para transformar necesidades reales en aprendizaje práctico'),
  pasos: [
    { titulo: p.t('como_funciona.item_1_titulo', 'La Empresa propone'), texto: p.t('como_funciona.item_1_texto', 'Identifican una fricción o cuello de botella real en su día a día.') },
    { titulo: p.t('como_funciona.item_2_titulo', 'La IA lo transforma'), texto: p.t('como_funciona.item_2_texto', 'DuaLab genera un reto académico alineado al currículo oficial.') },
    { titulo: p.t('como_funciona.item_3_titulo', 'El Alumnado resuelve'), texto: p.t('como_funciona.item_3_texto', 'Ganan experiencia práctica mientras aportan valor real a la empresa.') },
  ],
  boton: p.t('como_funciona.boton', 'Solicitar demo'),
}))

</script>

<style scoped>
/* ── Orchestrated entrance: animaciones de entrada del hero ───────────────────
   Cada elemento aparece secuencialmente con delay escalonado.
   Técnica: @keyframes + animation-delay + animation-fill-mode: both          */
@keyframes heroFadeUp {
  from {
    opacity: 0;
    transform: translateY(40px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.hero-badge {
  animation: heroFadeUp 1.1s cubic-bezier(0.16, 1, 0.3, 1) both;
  animation-delay: 0.2s;
}
.hero-title {
  animation: heroFadeUp 1.3s cubic-bezier(0.16, 1, 0.3, 1) both;
  animation-delay: 0.55s;
}
.hero-desc {
  animation: heroFadeUp 1.2s cubic-bezier(0.16, 1, 0.3, 1) both;
  animation-delay: 1.0s;
}
.hero-btns {
  animation: heroFadeUp 1.1s cubic-bezier(0.16, 1, 0.3, 1) both;
  animation-delay: 1.45s;
}

/* ── Formas del diseño ───────────────────────────────────────────────────── */
.blob-image {
  clip-path: url(#blob-shape);
}

/* ── "Más servicios": despliegue suave ──────────────────────────────────── */
.expand-enter-active,
.expand-leave-active {
  transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.expand-enter-from,
.expand-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* ── Botón CTA: salto periódico para llamar la atención ─────────────────── */
@keyframes ctaAttention {
  0%, 60%, 100% { transform: translateY(0); }
  72%           { transform: translateY(-10px); }
  84%           { transform: translateY(-4px); }
  92%           { transform: translateY(-7px); }
}
.cta-attention {
  animation: ctaAttention 3s ease-in-out infinite;
}

</style>
