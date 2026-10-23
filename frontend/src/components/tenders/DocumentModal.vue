<script setup>
/**
 * Modal to describe a document (title, description, PDF/ZIP file) before adding it to the list.
 */
import { computed, reactive, ref, watch } from 'vue'
import AppModal from '@/components/base/AppModal.vue'
import FormField from '@/components/base/FormField.vue'
import { LIMITS, validateDocument } from '@/utils/validators'
import { formatBytes } from '@/utils/formatters'

const props = defineProps({
  show: { type: Boolean, default: false },
})

const emit = defineEmits(['add', 'close'])

const draft = reactive({ title: '', description: '', file: null })
const touched = reactive({ title: false, description: false, file: false })
const fileInput = ref(null)

const errors = computed(() => validateDocument(draft))
const isValid = computed(() => Object.keys(errors.value).length === 0)
const visibleError = (field) => (touched[field] ? (errors.value[field] ?? '') : '')

watch(
  () => props.show,
  (visible) => {
    if (!visible) return
    Object.assign(draft, { title: '', description: '', file: null })
    Object.assign(touched, { title: false, description: false, file: false })
    if (fileInput.value) fileInput.value.value = ''
  },
)

function onFileChange(event) {
  draft.file = event.target.files?.[0] ?? null
  touched.file = true
}

function add() {
  Object.assign(touched, { title: true, description: true, file: true })
  if (!isValid.value) return
  emit('add', { title: draft.title.trim(), description: draft.description.trim(), file: draft.file })
}
</script>

<template>
  <AppModal :show="show" title="Agregar documento" icon="bi-file-earmark-arrow-up" title-id="document-modal-title" @close="emit('close')">
    <div class="d-grid gap-3">
      <FormField
        label="Título"
        input-id="document-title"
        required
        :error="visibleError('title')"
        :count="draft.title.length"
        :max="LIMITS.documentTitle"
      >
        <input
          id="document-title"
          v-model="draft.title"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': visibleError('title') }"
          :maxlength="LIMITS.documentTitle"
          placeholder="Ej. Pliego de condiciones"
          @blur="touched.title = true"
        />
      </FormField>

      <FormField
        label="Descripción"
        input-id="document-description"
        required
        :error="visibleError('description')"
        :count="draft.description.length"
        :max="LIMITS.documentDescription"
      >
        <textarea
          id="document-description"
          v-model="draft.description"
          rows="3"
          class="form-control"
          :class="{ 'is-invalid': visibleError('description') }"
          :maxlength="LIMITS.documentDescription"
          placeholder="Breve descripción del contenido"
          @blur="touched.description = true"
        ></textarea>
      </FormField>

      <FormField
        label="Archivo"
        input-id="document-file"
        required
        :hint="`PDF o ZIP, máximo ${LIMITS.documentMegabytes} MB.`"
        :error="visibleError('file')"
      >
        <input
          id="document-file"
          ref="fileInput"
          type="file"
          class="form-control"
          :class="{ 'is-invalid': visibleError('file') }"
          accept=".pdf,.zip,application/pdf,application/zip"
          @change="onFileChange"
        />
        <p v-if="draft.file && !errors.file" class="small text-body-secondary mt-2 mb-0">
          <i class="bi bi-paperclip" aria-hidden="true"></i> {{ draft.file.name }} · {{ formatBytes(draft.file.size) }}
        </p>
      </FormField>
    </div>

    <template #footer>
      <button type="button" class="btn btn-outline-secondary" @click="emit('close')">Cancelar</button>
      <button type="button" class="btn btn-primary" :disabled="!isValid" @click="add">Agregar</button>
    </template>
  </AppModal>
</template>
