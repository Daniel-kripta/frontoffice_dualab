<!-- Formularios y Correo ya no están en Gestión web: se atienden en DuaLab Central («Primer contacto»).
     Esta pantalla recibe los enlaces antiguos (/gestion-web/formularios, /gestion-web/correo). -->
<template>
  <CabeceraPanel antetitulo="Formularios" :titulo="titulo" subtitulo="Ahora se atiende en DuaLab Central, en el espacio «Primer contacto»." />

  <section class="gw-tarjeta mx-auto max-w-2xl p-6 text-center sm:p-8">
    <EnvelopeIcon class="mx-auto h-10 w-10 text-administraciones-dark" aria-hidden="true" />
    <p class="mt-4 text-sm text-azul-noche sm:text-base">
      Los formularios de la web y el correo de info@ se leen, se responden y se archivan desde DuaLab Central.
      Gestión web se queda con el contenido, el diseño, el SEO y la analítica de la web.
    </p>
    <p class="mt-2 text-sm text-texto-secundario">Entra con tu cuenta de atención al cliente o de superadmin de DuaLab.</p>
    <a :href="destino" class="gw-accion-principal mt-6 inline-flex">
      Abrir DuaLab Central
      <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" />
    </a>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowTopRightOnSquareIcon, EnvelopeIcon } from '@heroicons/vue/24/outline'
import CabeceraPanel from '../componentes/CabeceraPanel.vue'
import { URL_CORREO_CENTRAL, URL_PRIMER_CONTACTO } from '../central'

const route = useRoute()
const esCorreo = computed(() => route.name === 'panel-correo')
const titulo = computed(() => (esCorreo.value ? 'Correo' : 'Formularios'))
const destino = computed(() => (esCorreo.value ? URL_CORREO_CENTRAL : URL_PRIMER_CONTACTO))
</script>
