<template>
  <!-- /contacto: un formulario por agente (diseños "FORMULARIO CENTROS / EMPRESAS /
       ALUMNOS / ADMINISTRACIONES"). Por defecto, centro educativo; ?tipo= lo cambia. -->
  <section ref="seccionForm" class="bg-superficie pt-8 pb-8 md:pt-12 md:pb-12 px-4 sm:px-6 scroll-mt-16">
    <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-[5fr_7fr] gap-6 md:gap-8 lg:gap-12">
      <!-- Columna izquierda: no cambia de tamaño al cambiar de agente (ver ContactoHero) -->
      <ContactoHero :agentes="AGENTES_DISPONIBLES" :activo="tipo" class="min-w-0" />

      <div class="min-w-0 bg-white rounded-2xl shadow-[0_10px_40px_rgba(23,40,62,0.08)] p-4 sm:p-5 lg:px-6 lg:py-5 form-compacto">
        <ContactoStepper :pasos="agente.pasos" :completados="completados" class="mb-4" />

        <!-- Escritorio: confirmación y formularios de todos los agentes en la misma celda, así la
             tarjeta mide siempre lo del más largo (solo el activo se ve y es interactivo).
             Móvil/tablet: solo el activo, para no dejar huecos ni alargar el scroll. -->
        <div class="grid grid-cols-1">
          <div v-if="enviado" class="[grid-area:1/1] self-center text-center py-10" role="status">
            <span class="mx-auto w-16 h-16 rounded-full flex items-center justify-center mb-5" :class="[agente.soft, agente.text]">
              <CheckCircleIcon class="w-9 h-9" />
            </span>
            <h2 class="text-2xl font-extrabold text-azul-noche">¡Solicitud enviada!</h2>
            <p class="text-gray-500 mt-2 max-w-md mx-auto">{{ MENSAJES_EXITO[accion] }}</p>
            <button type="button" class="btn-pill-outline mt-8" @click="reiniciar">Enviar otra solicitud</button>
          </div>

          <div class="[grid-area:1/1]" :class="{ 'max-lg:hidden lg:invisible': enviado }" :inert="enviado || null" :aria-hidden="enviado || undefined">
            <!-- 1. Tipo de organización -->
            <h2 class="font-heading font-bold text-sm text-azul-noche mb-2">¿Qué tipo de organización eres?</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2" role="radiogroup" aria-label="Tipo de organización">
              <button v-for="(a, key) in AGENTES" :key="key" type="button" role="radio" :aria-checked="tipo === key"
                      :disabled="a.bloqueado" :title="a.bloqueado ? 'Próximamente' : undefined"
                      class="relative flex flex-col items-center justify-center gap-1 min-h-[3.75rem] rounded-xl border-2 px-2 py-2 text-center transition-all"
                      :class="a.bloqueado ? 'border-dashed border-gray-200 opacity-50 cursor-not-allowed'
                        : tipo === key ? [a.border, a.soft] : 'border-gray-100 hover:border-gray-200'"
                      @click="cambiarTipo(key)">
                <span v-if="a.bloqueado" class="absolute top-1.5 right-1.5 text-[9px] font-black uppercase tracking-wide text-gray-500 bg-gray-100 rounded-full px-1.5 py-0.5">Próximamente</span>
                <span v-if="tipo === key" class="absolute top-2 right-2 w-5 h-5 rounded-full flex items-center justify-center text-white" :class="a.bg">
                  <svg class="w-3 h-3" viewBox="0 0 10 10" fill="none" aria-hidden="true"><path d="M1.5 5l2.5 2.5L8.5 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <component :is="a.icon" class="w-6 h-6" :class="a.text" />
                <span class="text-[11px] font-semibold leading-tight text-azul-noche">{{ a.label }}</span>
              </button>
            </div>

            <div class="grid grid-cols-1">
              <form v-for="t in TIPOS_DISPONIBLES" :key="t" novalidate class="[grid-area:1/1] flex flex-col"
                    :class="{ 'max-lg:hidden lg:invisible': t !== tipo }" :inert="t !== tipo || null" :aria-hidden="t !== tipo || undefined"
                    @submit.prevent="enviar">
                <!-- Campo trampa para bots: invisible para personas y lectores de pantalla -->
                <div class="absolute -left-[9999px] w-px h-px overflow-hidden" aria-hidden="true">
                  <label :for="`${t}-website`">No rellenes este campo</label>
                  <input :id="`${t}-website`" v-model="honeypot" type="text" name="website" tabindex="-1" autocomplete="off" />
                </div>

                <!-- ── CENTRO EDUCATIVO ── -->
                <template v-if="t === 'centro'">
                  <h2 class="form-section">Datos del centro educativo</h2>
                  <div class="grid sm:grid-cols-2 gap-x-4 gap-y-2.5">
                    <CampoForm :id="`${t}-centro_nombre`" label="Nombre del centro" required :error="err('centro_nombre')">
                      <input :id="`${t}-centro_nombre`" v-model="d.centro.centro_nombre" class="form-input" placeholder="Ej. IES Sierra Norte" required maxlength="160" />
                    </CampoForm>
                    <CampoForm :id="`${t}-direccion`" label="Dirección" required :error="err('direccion')">
                      <input :id="`${t}-direccion`" v-model="d.centro.direccion" class="form-input" placeholder="Calle, número, etc." required maxlength="200" autocomplete="street-address" />
                    </CampoForm>
                    <CampoForm :id="`${t}-provincia`" label="Provincia" required :error="err('provincia')">
                      <select :id="`${t}-provincia`" v-model="d.centro.provincia" class="form-input bg-white" required>
                        <option v-for="p in PROVINCIAS" :key="p">{{ p }}</option>
                      </select>
                    </CampoForm>
                    <CampoForm :id="`${t}-municipio`" label="Municipio" required :error="err('municipio')">
                      <select :id="`${t}-municipio`" v-model="d.centro.municipio" class="form-input bg-white" required>
                        <option value="" disabled>Selecciona un municipio</option>
                        <option v-for="m in MUNICIPIOS[d.centro.provincia]" :key="m">{{ m }}</option>
                      </select>
                    </CampoForm>
                    <CampoForm :id="`${t}-familias`" label="Familias profesionales de interés" required :hint="`Puedes seleccionar hasta ${MAX_FAMILIAS_CENTRO} familias`" :error="err('familias')" class="sm:col-span-2">
                      <FamiliasSelector :id="`${t}-familias`" v-model="d.centro.familias" :opciones="FAMILIAS_FP" :max="MAX_FAMILIAS_CENTRO" />
                    </CampoForm>
                  </div>
                </template>

                <!-- ── EMPRESA ── -->
                <template v-if="t === 'empresa'">
                  <h2 class="form-section">Datos de la empresa</h2>
                  <div class="grid sm:grid-cols-2 gap-x-4 gap-y-2.5">
                    <CampoForm :id="`${t}-empresa_nombre`" label="Nombre de la empresa" required :error="err('empresa_nombre')">
                      <input :id="`${t}-empresa_nombre`" v-model="d.empresa.empresa_nombre" class="form-input" placeholder="Ej. Tecnología Canaria S.L." required maxlength="160" autocomplete="organization" />
                    </CampoForm>
                    <CampoForm :id="`${t}-cif`" label="CIF" required :error="err('cif')">
                      <input :id="`${t}-cif`" v-model="d.empresa.cif" class="form-input uppercase" placeholder="B12345678" required maxlength="15" />
                    </CampoForm>
                    <CampoForm :id="`${t}-sector`" label="Sector de actividad" required :error="err('sector')">
                      <select :id="`${t}-sector`" v-model="d.empresa.sector" class="form-input bg-white" required>
                        <option value="" disabled>Selecciona un sector</option>
                        <option v-for="s in SECTORES" :key="s">{{ s }}</option>
                      </select>
                    </CampoForm>
                    <CampoForm :id="`${t}-provincia`" label="Provincia" required :error="err('provincia')">
                      <select :id="`${t}-provincia`" v-model="d.empresa.provincia" class="form-input bg-white" required>
                        <option v-for="p in PROVINCIAS" :key="p">{{ p }}</option>
                      </select>
                    </CampoForm>
                    <CampoForm :id="`${t}-municipio`" label="Municipio" required :error="err('municipio')">
                      <select :id="`${t}-municipio`" v-model="d.empresa.municipio" class="form-input bg-white" required>
                        <option value="" disabled>Selecciona un municipio</option>
                        <option v-for="m in MUNICIPIOS[d.empresa.provincia]" :key="m">{{ m }}</option>
                      </select>
                    </CampoForm>
                    <CampoForm :id="`${t}-direccion`" label="Dirección" required :error="err('direccion')">
                      <input :id="`${t}-direccion`" v-model="d.empresa.direccion" class="form-input" placeholder="Calle, número, etc." required maxlength="200" autocomplete="street-address" />
                    </CampoForm>
                    <CampoForm :id="`${t}-tamano`" label="Tamaño de la empresa" required :error="err('tamano')">
                      <select :id="`${t}-tamano`" v-model="d.empresa.tamano" class="form-input bg-white" required>
                        <option value="" disabled>Selecciona el tamaño</option>
                        <option v-for="t in TAMANOS" :key="t.value" :value="t.value">{{ t.label }}</option>
                      </select>
                    </CampoForm>
                    <CampoForm :id="`${t}-colabora`" label="¿Colaboras con centros educativos?" required :error="err('colabora')">
                      <select :id="`${t}-colabora`" v-model="d.empresa.colabora" class="form-input bg-white" required>
                        <option value="" disabled>Selecciona una opción</option>
                        <option v-for="c in COLABORA" :key="c.value" :value="c.value">{{ c.label }}</option>
                      </select>
                    </CampoForm>
                  </div>
                </template>

                <!-- ── PERSONA DE CONTACTO / TUS DATOS PERSONALES ── -->
                <h2 class="form-section">{{ t === 'alumno' ? 'Tus datos personales' : 'Persona de contacto' }}</h2>
                <div class="grid sm:grid-cols-2 gap-x-4 gap-y-2.5">
                  <CampoForm :id="`${t}-nombre`" label="Nombre" required :error="err('nombre')">
                    <input :id="`${t}-nombre`" v-model="persona.nombre" class="form-input" :placeholder="PLACEHOLDER_NOMBRE[t]" required maxlength="120" autocomplete="given-name" />
                  </CampoForm>
                  <CampoForm :id="`${t}-apellidos`" label="Apellidos" required :error="err('apellidos')">
                    <input :id="`${t}-apellidos`" v-model="persona.apellidos" class="form-input" placeholder="Ej. García López" required maxlength="120" autocomplete="family-name" />
                  </CampoForm>
                </div>
                <div class="grid gap-x-4 gap-y-2.5 mt-2.5" :class="t === 'alumno' ? 'sm:grid-cols-[1fr_1fr_160px]' : 'sm:grid-cols-[1.3fr_1fr_1fr]'">
                  <CampoForm v-if="t !== 'alumno'" :id="`${t}-cargo`" :label="LABEL_CARGO[t]" required :error="err('cargo')">
                    <input :id="`${t}-cargo`" v-model="persona.cargo" class="form-input" :placeholder="PLACEHOLDER_CARGO[t]" required maxlength="120" autocomplete="organization-title" />
                  </CampoForm>
                  <CampoForm :id="`${t}-email`" label="Email" required :error="err('email')">
                    <input :id="`${t}-email`" v-model="persona.email" type="email" class="form-input" :placeholder="PLACEHOLDER_EMAIL[t]" required maxlength="255" autocomplete="email" />
                  </CampoForm>
                  <CampoForm :id="`${t}-telefono`" label="Teléfono" required :error="err('telefono')">
                    <CampoTelefono :id="`${t}-telefono`" v-model="persona.telefono" />
                  </CampoForm>
                  <CampoForm v-if="t === 'alumno'" :id="`${t}-fecha_nacimiento`" label="Fecha de nacimiento" required :error="err('fecha_nacimiento')">
                    <input :id="`${t}-fecha_nacimiento`" v-model="d.alumno.fecha_nacimiento" type="date" class="form-input" :max="hoy" required autocomplete="bday" />
                  </CampoForm>
                </div>

                <!-- ── ALUMNO: información académica ── -->
                <template v-if="t === 'alumno'">
                  <h2 class="form-section">Información académica</h2>
                  <div class="grid sm:grid-cols-2 gap-x-4 gap-y-2.5">
                    <CampoForm :id="`${t}-centro_educativo`" label="Centro educativo" required :error="err('centro_educativo')">
                      <input :id="`${t}-centro_educativo`" v-model="d.alumno.centro_educativo" class="form-input" placeholder="Ej. IES Sierra Norte" required maxlength="160" />
                    </CampoForm>
                    <CampoForm :id="`${t}-ciclo`" label="Ciclo formativo" required :error="err('ciclo')">
                      <input :id="`${t}-ciclo`" v-model="d.alumno.ciclo" class="form-input" placeholder="Ej. Desarrollo de Aplicaciones Web" required maxlength="160" />
                    </CampoForm>
                    <CampoForm :id="`${t}-curso`" label="Curso actual" required :error="err('curso')">
                      <select :id="`${t}-curso`" v-model="d.alumno.curso" class="form-input bg-white" required>
                        <option value="" disabled>Selecciona un curso</option>
                        <option v-for="c in CURSOS" :key="c.value" :value="c.value">{{ c.label }}</option>
                      </select>
                    </CampoForm>
                    <CampoForm :id="`${t}-familia`" label="Familia profesional" required :error="err('familia')">
                      <select :id="`${t}-familia`" v-model="d.alumno.familia" class="form-input bg-white" required>
                        <option value="" disabled>Selecciona la familia profesional</option>
                        <option v-for="f in FAMILIAS_FP" :key="f">{{ f }}</option>
                      </select>
                    </CampoForm>
                  </div>
                </template>

                <!-- ── ADMINISTRACIÓN: datos de la entidad ── -->
                <template v-if="t === 'administracion'">
                  <h2 class="form-section">Datos de la entidad</h2>
                  <div class="grid sm:grid-cols-2 gap-x-4 gap-y-2.5">
                    <CampoForm :id="`${t}-entidad_nombre`" label="Nombre de la entidad / organismo" required :error="err('entidad_nombre')">
                      <input :id="`${t}-entidad_nombre`" v-model="d.administracion.entidad_nombre" class="form-input" placeholder="Ej. Consejería de Educación del Gobierno de Canarias" required maxlength="160" autocomplete="organization" />
                    </CampoForm>
                    <CampoForm :id="`${t}-cif`" label="CIF" required :error="err('cif')">
                      <input :id="`${t}-cif`" v-model="d.administracion.cif" class="form-input uppercase" placeholder="P3500000X" required maxlength="15" />
                    </CampoForm>
                    <CampoForm :id="`${t}-tipo_entidad`" label="Tipo de entidad" required :error="err('tipo_entidad')">
                      <select :id="`${t}-tipo_entidad`" v-model="d.administracion.tipo_entidad" class="form-input bg-white" required>
                        <option value="" disabled>Selecciona una opción</option>
                        <option v-for="t in TIPOS_ENTIDAD" :key="t.value" :value="t.value">{{ t.label }}</option>
                      </select>
                    </CampoForm>
                    <CampoForm :id="`${t}-ambito`" label="Ámbito territorial" required :error="err('ambito')">
                      <select :id="`${t}-ambito`" v-model="d.administracion.ambito" class="form-input bg-white" required>
                        <option value="" disabled>Selecciona un ámbito</option>
                        <option v-for="a in AMBITOS" :key="a.value" :value="a.value">{{ a.label }}</option>
                      </select>
                    </CampoForm>
                  </div>
                  <div class="grid sm:grid-cols-[1.3fr_1fr_1fr] gap-x-4 gap-y-2.5 mt-2.5">
                    <CampoForm :id="`${t}-direccion`" label="Dirección" required :error="err('direccion')">
                      <input :id="`${t}-direccion`" v-model="d.administracion.direccion" class="form-input" placeholder="Calle, número, etc." required maxlength="200" autocomplete="street-address" />
                    </CampoForm>
                    <CampoForm :id="`${t}-provincia`" label="Provincia" required :error="err('provincia')">
                      <select :id="`${t}-provincia`" v-model="d.administracion.provincia" class="form-input bg-white" required>
                        <option v-for="p in PROVINCIAS" :key="p">{{ p }}</option>
                      </select>
                    </CampoForm>
                    <CampoForm :id="`${t}-municipio`" label="Municipio" required :error="err('municipio')">
                      <select :id="`${t}-municipio`" v-model="d.administracion.municipio" class="form-input bg-white" required>
                        <option value="" disabled>Selecciona un municipio</option>
                        <option v-for="m in MUNICIPIOS[d.administracion.provincia]" :key="m">{{ m }}</option>
                      </select>
                    </CampoForm>
                  </div>
                </template>

                <!-- ── INTERESES (empresa, alumno, administración) ── -->
                <div v-if="t !== 'centro'" class="mt-4 rounded-xl px-4 py-3 bg-primary-50/50 border border-primary-100">
                  <h2 class="font-heading font-bold text-sm text-azul-noche">{{ TITULO_INTERESES[t] }}</h2>
                  <p class="text-[11px] text-gray-500 mb-2">Selecciona las opciones que te interesan:</p>
                  <InteresesSelector v-model="d[t].intereses" :opciones="INTERESES[t]"
                                     :variante="t === 'alumno' ? 'tarjetas' : 'check'" :color-icono="AGENTES[t].text" />
                </div>

                <!-- Único campo libre de todos los agentes. En escritorio crece hasta ocupar el sitio
                     libre: privacidad y botones quedan abajo, en el mismo sitio, sin hueco en blanco. -->
                <CampoForm :id="`${t}-comentarios`" label="Comentarios" hint="Opcional" :error="err('comentarios')" class="mt-3 flex flex-col grow">
                  <textarea :id="`${t}-comentarios`" v-model="persona.comentarios" rows="2" maxlength="2000" class="form-input resize-y grow min-h-[3.75rem]" :placeholder="PLACEHOLDER_COMENTARIOS[t]" />
                </CampoForm>

                <!-- Privacidad -->
                <div class="flex items-start gap-3 rounded-xl bg-primary-50 px-3 py-2 mt-3">
                  <LockClosedIcon class="w-4 h-4 text-primary-600 shrink-0 mt-0.5" />
                  <p class="text-[11px] text-gray-600 leading-snug">
                    Tus datos serán tratados de forma confidencial y únicamente para gestionar tu solicitud.
                    <!-- PENDIENTE: enlazar cuando exista la página de Política de privacidad -->
                    <span class="text-primary-700 font-semibold">Consulta nuestra Política de privacidad.</span>
                  </p>
                </div>

                <!-- Comunicaciones comerciales: casilla aparte, opcional y sin marcar. Enviar la solicitud no depende de ella -->
                <label :for="`${t}-marketing`" class="flex items-start gap-2.5 mt-2.5 cursor-pointer">
                  <input :id="`${t}-marketing`" v-model="persona.marketing" type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                  <span class="text-[11px] text-gray-600 leading-snug">
                    Quiero recibir novedades, contenidos y propuestas de DuaLab por correo electrónico. Puedes darte de baja cuando quieras. (Opcional)
                  </span>
                </label>

                <p v-if="errorGeneral" class="text-sm text-red-600 mt-3" role="alert">{{ errorGeneral }}</p>

                <!-- Acciones -->
                <div class="grid sm:grid-cols-3 gap-2 sm:gap-3 mt-3">
                  <button type="submit" class="btn-pill bg-alumnos hover:bg-alumnos/90 !text-xs !px-4 !py-2.5 whitespace-nowrap disabled:opacity-60" :disabled="enviando" @click="accion = 'registro'">
                    <UserPlusIcon class="w-4 h-4 shrink-0" /> Registrarme <ArrowRightIcon class="w-4 h-4 shrink-0" />
                  </button>
                  <button type="submit" class="btn-pill-outline !text-xs !px-4 !py-2.5 whitespace-nowrap disabled:opacity-60" :disabled="enviando" @click="accion = 'info'">
                    <InformationCircleIcon class="w-4 h-4 shrink-0" /> Más info
                  </button>
                  <button type="submit" class="btn-pill bg-primary-600 hover:bg-primary-700 !text-xs !px-4 !py-2.5 whitespace-nowrap disabled:opacity-60" :disabled="enviando" @click="accion = 'demo'">
                    <PlayIcon class="w-4 h-4 shrink-0" /> Solicitar demo
                  </button>
                </div>
                <p v-if="enviando" class="text-xs text-gray-400 text-center mt-3">Enviando…</p>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- "Agenda una reunión": vuelve al formulario de arriba -->
  <CtaTransforma @agendar="agendarReunion" />
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  CheckCircleIcon, LockClosedIcon, UserPlusIcon, ArrowRightIcon, InformationCircleIcon, PlayIcon,
} from '@heroicons/vue/24/outline'
import api, { ensureCsrfCookie } from '@/services/api'
import { origenActual } from '@/services/origen'
import ContactoHero from '@/components/contacto/ContactoHero.vue'
import CtaTransforma from '@/components/common/CtaTransforma.vue'
import ContactoStepper from '@/components/contacto/ContactoStepper.vue'
import CampoForm from '@/components/contacto/CampoForm.vue'
import CampoTelefono from '@/components/contacto/CampoTelefono.vue'
import FamiliasSelector from '@/components/contacto/FamiliasSelector.vue'
import InteresesSelector from '@/components/contacto/InteresesSelector.vue'
import { AGENTES, TIPOS } from '@/components/contacto/contactoConfig'
import {
  PROVINCIAS, MUNICIPIOS, FAMILIAS_FP, MAX_FAMILIAS_CENTRO, SECTORES, TAMANOS, COLABORA,
  CURSOS, TIPOS_ENTIDAD, AMBITOS, INTERESES,
} from '@/data/contacto'

const route = useRoute()
const router = useRouter()

// ── Textos que cambian por agente ─────────────────────────────────────────────
const LABEL_CARGO = { centro: 'Cargo en el centro', empresa: 'Cargo en la empresa', administracion: 'Cargo' }
const PLACEHOLDER_CARGO = {
  centro: 'Ej. Dirección, Jefatura de Estudios, Coordinación de FP…',
  empresa: 'Ej. Responsable de RR. HH., Dirección…',
  administracion: 'Ej. Técnico/a de Formación, Responsable de Programa…',
}
const PLACEHOLDER_NOMBRE = { centro: 'Ej. María', empresa: 'Ej. Laura', alumno: 'Ej. María', administracion: 'Ej. Ana' }
const PLACEHOLDER_EMAIL = {
  centro: 'nombre@centroeducativo.es', empresa: 'nombre@empresa.es', alumno: 'tuemail@ejemplo.com', administracion: 'nombre@entidad.es',
}
const TITULO_INTERESES = { empresa: 'Tu interés en DuaLab', alumno: '¿Qué te interesa en DuaLab?', administracion: '¿En qué podemos ayudarte?' }
const PLACEHOLDER_COMENTARIOS = {
  centro: 'Déjanos cualquier comentario o petición…',
  empresa: 'Cuéntanos tus objetivos, cómo te gustaría colaborar o cualquier petición. Ej. Nos gustaría plantear un reto sobre…',
  alumno: 'Cuéntanos tus objetivos o cualquier petición. Ej. Quiero realizar prácticas en el sector tecnológico…',
  administracion: 'Cuéntanos tus objetivos, necesidades o cualquier petición. Ej. Queremos conocer cómo DuaLab puede apoyar nuestro programa de FP Dual…',
}
const MENSAJES_EXITO = {
  registro: 'Gracias por registrarte. Revisaremos tus datos y te escribiremos para completar el alta.',
  info: 'Hemos recibido tu solicitud. Te enviaremos más información muy pronto.',
  demo: 'Hemos recibido tu solicitud. Nos pondremos en contacto contigo para agendar la demo.',
}

// ── Tipo de agente (por defecto, centro educativo) ──────────────────────────────
// Solo agentes disponibles (no bloqueados); cualquier otro valor → centro.
// Agentes que se pueden elegir (los bloqueados no tienen formulario)
const TIPOS_DISPONIBLES = TIPOS.filter((t) => !AGENTES[t].bloqueado)
const AGENTES_DISPONIBLES = Object.fromEntries(TIPOS_DISPONIBLES.map((t) => [t, AGENTES[t]]))
const tipoValido = (t) => (AGENTES[t] && !AGENTES[t].bloqueado ? t : null)
const tipo = ref(tipoValido(route.query.tipo) ?? 'centro')
const agente = computed(() => AGENTES[tipo.value])

function cambiarTipo(t) {
  if (!tipoValido(t)) return
  tipo.value = t
  errores.value = {}
  errorGeneral.value = ''
  router.replace({ query: { ...route.query, tipo: t } })
}
watch(() => route.query.tipo, (t) => { tipo.value = tipoValido(t) ?? 'centro' })

// ── Estado del formulario: persona común + datos propios por agente ───────────────
// Separados por agente para no perder lo escrito al cambiar de pestaña.
function estadoInicial() {
  return {
    persona: { nombre: '', apellidos: '', cargo: '', email: '', telefono: '', comentarios: '', marketing: false },
    d: {
      centro: { centro_nombre: '', direccion: '', municipio: '', provincia: 'Santa Cruz de Tenerife', familias: [] },
      empresa: { empresa_nombre: '', cif: '', sector: '', provincia: 'Santa Cruz de Tenerife', municipio: '', direccion: '', tamano: '', colabora: '', intereses: [] },
      alumno: { fecha_nacimiento: '', centro_educativo: '', ciclo: '', curso: '', familia: '', intereses: [] },
      administracion: { entidad_nombre: '', cif: '', tipo_entidad: '', ambito: '', direccion: '', municipio: '', provincia: 'Las Palmas', intereses: [] },
    },
  }
}
const inicial = estadoInicial()
const persona = reactive(inicial.persona)
const d = reactive(inicial.d)

// Al cambiar de provincia, el municipio elegido deja de ser válido
for (const t of ['centro', 'empresa', 'administracion']) {
  watch(() => d[t].provincia, (p) => {
    if (!MUNICIPIOS[p]?.includes(d[t].municipio)) d[t].municipio = ''
  })
}

const hoy = new Date().toISOString().slice(0, 10)

// ── Pasos completados (solo indicativo; la validación real es del backend) ─────────
const lleno = (...vals) => vals.every((v) => (Array.isArray(v) ? v.length > 0 : String(v ?? '').trim() !== ''))

const completados = computed(() => {
  const t = tipo.value
  const p = persona
  const datosOk = t === 'alumno'
    ? lleno(p.nombre, p.apellidos, p.email, p.telefono, d.alumno.fecha_nacimiento)
    : lleno(p.nombre, p.apellidos, p.cargo, p.email, p.telefono)
  const x = d[t]
  const seccionOk = {
    centro: () => lleno(x.centro_nombre, x.direccion, x.municipio, x.provincia, x.familias),
    empresa: () => lleno(x.empresa_nombre, x.cif, x.sector, x.provincia, x.municipio, x.direccion, x.tamano, x.colabora),
    alumno: () => lleno(x.centro_educativo, x.ciclo, x.curso, x.familia),
    administracion: () => lleno(x.entidad_nombre, x.cif, x.tipo_entidad, x.ambito, x.direccion, x.municipio, x.provincia),
  }[t]()
  const pasos = [true, datosOk, seccionOk]
  if (t === 'alumno' || t === 'administracion') pasos.push(x.intereses.length > 0 || lleno(persona.comentarios))
  pasos.push(enviado.value)
  return pasos
})

// ── Envío ──────────────────────────────────────────────────────────────────────
const accion = ref('info')
const honeypot = ref('')
const enviando = ref(false)
const enviado = ref(false)
const errores = ref({})
const errorGeneral = ref('')

const err = (campo) => errores.value[campo]

async function enviar(e) {
  errores.value = {}
  errorGeneral.value = ''

  // Validación nativa del navegador (required, type=email, pattern…)
  if (!e.target.reportValidity()) return
  if (tipo.value === 'centro' && !d.centro.familias.length) {
    errores.value = { familias: 'Selecciona al menos una familia profesional.' }
    return
  }

  const payload = {
    tipo: tipo.value,
    accion: accion.value,
    nombre: persona.nombre,
    apellidos: persona.apellidos,
    email: persona.email,
    telefono: `+34 ${persona.telefono.trim()}`,
    ...(tipo.value !== 'alumno' && { cargo: persona.cargo }),
    ...d[tipo.value],
    comentarios: persona.comentarios,
    consentimiento_marketing: persona.marketing,
    origen: origenActual(),
    website: honeypot.value,
  }

  enviando.value = true
  try {
    await ensureCsrfCookie()
    await api.post('/contacto', payload)
    enviado.value = true
  } catch (error) {
    if (error.response?.status === 422) {
      const errs = error.response.data?.errors ?? {}
      // "familias.0" → "familias", "intereses.2" → "intereses"
      errores.value = Object.fromEntries(Object.entries(errs).map(([k, v]) => [k.split('.')[0], v[0]]))
      errorGeneral.value = 'Revisa los campos marcados.'
    } else if (error.response?.status === 429) {
      // 429 viene del throttle por IP (1 min) o del límite por email (3/hora): no prometer un minuto
      errorGeneral.value = 'Has enviado varias solicitudes en poco tiempo. Inténtalo de nuevo dentro de una hora.'
    } else {
      errorGeneral.value = 'No hemos podido enviar tu solicitud. Inténtalo de nuevo más tarde.'
    }
  } finally {
    enviando.value = false
  }
}

const seccionForm = ref(null)
function agendarReunion() {
  seccionForm.value?.scrollIntoView({ behavior: 'smooth' })
}

function reiniciar() {
  const nuevo = estadoInicial()
  Object.assign(persona, nuevo.persona)
  for (const t of Object.keys(d)) Object.assign(d[t], nuevo.d[t])
  enviado.value = false
  accion.value = 'info'
}
</script>

<style scoped>
.form-section {
  @apply font-heading font-bold text-sm text-azul-noche mt-4 mb-2;
}
/* Campos más bajos que en el resto de la web: el formulario entero cabe junto a la foto */
.form-compacto :deep(.form-input) {
  @apply py-1.5 text-xs;
}
</style>
