<!-- Editor de las entradas: CKEditor 5 (licencia GPL) con la barra justa para lo que el
     backend permite guardar (config/purifier.php → perfil `cms`). Las imágenes se
     insertan desde la biblioteca de medios. -->
<template>
  <div class="editor-rico">
    <Ckeditor v-model="html" :editor="ClassicEditor" :config="config" :disabled="disabled" @ready="alListo" />
    <div class="mt-2 flex flex-wrap items-center gap-2">
      <button v-if="!disabled" type="button" class="gw-accion-secundaria !h-9" @click="selector = true">
        <PhotoIcon class="h-4 w-4" aria-hidden="true" /> Insertar imagen de la biblioteca
      </button>
      <span class="text-xs text-texto-secundario">{{ palabras }} palabras</span>
    </div>
    <SelectorMedio :abierto="selector" tipo="imagen" titulo="Insertar imagen" @cerrar="selector = false" @elegir="insertarImagen" />
  </div>
</template>

<script setup>
import { computed, ref, shallowRef } from 'vue'
import { Ckeditor } from '@ckeditor/ckeditor5-vue'
import {
  ClassicEditor, Essentials, Paragraph, Heading, Bold, Italic, Underline, Strikethrough,
  Link, List, BlockQuote, Table, TableToolbar, Image, ImageToolbar, ImageCaption,
  ImageTextAlternative, HorizontalLine, Autoformat, PasteFromOffice, RemoveFormat,
} from 'ckeditor5'
import traduccionesEs from 'ckeditor5/translations/es.js'
import 'ckeditor5/ckeditor5.css'
import { PhotoIcon } from '@heroicons/vue/24/outline'
import SelectorMedio from './SelectorMedio.vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

const html = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v),
})

const config = {
  licenseKey: 'GPL',
  language: 'es',
  translations: [traduccionesEs],
  plugins: [
    Essentials, Paragraph, Heading, Bold, Italic, Underline, Strikethrough, Link, List, BlockQuote,
    Table, TableToolbar, Image, ImageToolbar, ImageCaption, ImageTextAlternative, HorizontalLine,
    Autoformat, PasteFromOffice, RemoveFormat,
  ],
  toolbar: {
    items: ['heading', '|', 'bold', 'italic', 'underline', 'strikethrough', '|', 'link', 'bulletedList', 'numberedList',
      'blockQuote', 'insertTable', 'horizontalLine', '|', 'removeFormat', '|', 'undo', 'redo'],
    shouldNotGroupWhenFull: false,
  },
  // h1 es el título de la entrada: el texto empieza en h2
  heading: {
    options: [
      { model: 'paragraph', title: 'Párrafo', class: 'ck-heading_paragraph' },
      { model: 'heading2', view: 'h2', title: 'Título 2', class: 'ck-heading_heading2' },
      { model: 'heading3', view: 'h3', title: 'Título 3', class: 'ck-heading_heading3' },
      { model: 'heading4', view: 'h4', title: 'Título 4', class: 'ck-heading_heading4' },
    ],
  },
  link: {
    defaultProtocol: 'https://',
    decorators: { externos: { mode: 'automatic', callback: url => /^https?:\/\//.test(url ?? ''), attributes: { target: '_blank', rel: 'noopener noreferrer' } } },
  },
  image: { toolbar: ['imageTextAlternative', 'toggleImageCaption'] },
  table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'] },
}

const editor = shallowRef(null)
const selector = ref(false)
const alListo = e => { editor.value = e }

function insertarImagen(medio) {
  editor.value?.execute('insertImage', { source: [{ src: medio.url, alt: medio.alt || '' }] })
  editor.value?.editing.view.focus()
}

const palabras = computed(() => (props.modelValue || '').replace(/<[^>]+>/g, ' ').split(/\s+/).filter(Boolean).length)
</script>

<style scoped>
.editor-rico :deep(.ck-editor__editable) { min-height: 22rem; max-height: 70vh; }
.editor-rico :deep(.ck.ck-editor__main > .ck-editor__editable) { border-bottom-left-radius: 0.75rem; border-bottom-right-radius: 0.75rem; }
.editor-rico :deep(.ck.ck-editor__top .ck-sticky-panel .ck-toolbar) { border-top-left-radius: 0.75rem; border-top-right-radius: 0.75rem; }
.editor-rico :deep(.ck-content h2) { font-size: 1.5rem; font-weight: 700; color: #17283E; }
.editor-rico :deep(.ck-content h3) { font-size: 1.25rem; font-weight: 700; color: #17283E; }
.editor-rico :deep(.ck-content h4) { font-weight: 700; color: #17283E; }
</style>
