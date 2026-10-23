<script setup>
/**
 * "Documentos" section of the edit form: saved documents + pending ones (sent on save).
 */
import { computed, ref } from 'vue'
import DocumentModal from '@/components/tenders/DocumentModal.vue'
import { formatBytes } from '@/utils/formatters'

const props = defineProps({
  existing: { type: Array, default: () => [] },
  modelValue: { type: Array, default: () => [] },
  error: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const showModal = ref(false)
const total = computed(() => props.existing.length + props.modelValue.length)
const message = computed(() => props.error || (total.value === 0 ? 'Debe registrar al menos 1 documento.' : ''))

function add(document) {
  emit('update:modelValue', [...props.modelValue, document])
  showModal.value = false
}

function remove(index) {
  emit(
    'update:modelValue',
    props.modelValue.filter((_, i) => i !== index),
  )
}

const fileIcon = (name) => (name.toLowerCase().endsWith('.zip') ? 'bi-file-earmark-zip' : 'bi-file-earmark-pdf')
</script>

<template>
  <section class="card form-section mb-4" aria-labelledby="section-documents">
    <div class="card-body">
      <div class="form-section-header">
        <span class="form-section-step" aria-hidden="true">3</span>
        <div class="flex-grow-1">
          <h2 id="section-documents" class="h5 mb-0">Documentos</h2>
          <p class="text-body-secondary small mb-0">Pliegos, anexos y soportes de la licitación (PDF o ZIP).</p>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" @click="showModal = true">
          <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Agregar documento
        </button>
      </div>

      <div v-if="total > 0" class="table-responsive">
        <table class="table align-middle mb-0 documents-table">
          <thead>
            <tr>
              <th scope="col">Título</th>
              <th scope="col">Descripción</th>
              <th scope="col">Archivo</th>
              <th scope="col" class="text-end">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="document in existing" :key="`saved-${document.id}`">
              <td class="fw-semibold">{{ document.title }}</td>
              <td class="text-body-secondary">{{ document.description }}</td>
              <td>
                <a :href="document.download_url" class="file-link">
                  <i class="bi" :class="fileIcon(document.original_name)" aria-hidden="true"></i>
                  {{ document.original_name }}
                </a>
                <small class="text-body-secondary ms-1">{{ formatBytes(document.size) }}</small>
              </td>
              <td class="text-end"><span class="badge text-bg-light border">Guardado</span></td>
            </tr>
            <tr v-for="(document, index) in modelValue" :key="`pending-${index}`" class="table-row-pending">
              <td class="fw-semibold">{{ document.title }}</td>
              <td class="text-body-secondary">{{ document.description }}</td>
              <td>
                <i class="bi" :class="fileIcon(document.file.name)" aria-hidden="true"></i>
                {{ document.file.name }}
                <small class="text-body-secondary ms-1">{{ formatBytes(document.file.size) }}</small>
              </td>
              <td class="text-end text-nowrap">
                <span class="badge badge-pending me-2">Pendiente</span>
                <button
                  type="button"
                  class="btn btn-sm btn-link text-danger p-0"
                  :aria-label="`Quitar ${document.file.name}`"
                  @click="remove(index)"
                >
                  <i class="bi bi-trash3" aria-hidden="true"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="documents-empty">
        <i class="bi bi-folder2-open" aria-hidden="true"></i>
        <span>Aún no hay documentos. Agregue al menos uno para poder guardar.</span>
      </div>

      <p v-if="message" class="invalid-feedback d-block mb-0 mt-2" role="alert">{{ message }}</p>
      <p v-else-if="modelValue.length" class="form-text mb-0 mt-2">
        Los documentos pendientes se cargarán al guardar los cambios.
      </p>
    </div>

    <DocumentModal :show="showModal" @add="add" @close="showModal = false" />
  </section>
</template>
