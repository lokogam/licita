<script setup>
/**
 * Tender detail organized in tabs, with access to the edit form.
 */
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import EmptyState from '@/components/base/EmptyState.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { getTender } from '@/api/tenders'
import { useAuth } from '@/composables/useAuth'
import { useDocumentDownload } from '@/composables/useDocumentDownload'
import { formatBytes, formatDate, formatDateTime, formatMoney } from '@/utils/formatters'

const route = useRoute()
const { can } = useAuth()
const { download, downloadingId } = useDocumentDownload()

const tender = ref(null)
const loading = ref(true)
const error = ref('')
const activeTab = ref('basic')

const TABS = [
  { key: 'basic', label: 'Información básica', icon: 'bi-info-circle' },
  { key: 'schedule', label: 'Cronograma', icon: 'bi-calendar3' },
  { key: 'documents', label: 'Documentos', icon: 'bi-folder2' },
]

onMounted(async () => {
  try {
    tender.value = await getTender(route.params.id)
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
})

const fileIcon = (name) => (name.toLowerCase().endsWith('.zip') ? 'bi-file-earmark-zip' : 'bi-file-earmark-pdf')
</script>

<template>
  <div>
    <RouterLink :to="{ name: 'tenders.index' }" class="back-link mb-3">
      <i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al listado
    </RouterLink>

    <div v-if="loading" class="card placeholder-glow p-4" aria-busy="true">
      <span class="placeholder col-3 mb-3"></span>
      <span class="placeholder col-7 mb-2"></span>
      <span class="placeholder col-5"></span>
    </div>

    <div v-else-if="error" class="card">
      <EmptyState icon="bi-search" :title="error" description="Verifique el enlace o regrese al listado.">
        <RouterLink :to="{ name: 'tenders.index' }" class="btn btn-primary btn-sm">Ir al listado</RouterLink>
      </EmptyState>
    </div>

    <template v-else>
      <header class="detail-header card mb-4">
        <div class="card-body d-flex flex-column flex-lg-row gap-3 justify-content-between">
          <div class="min-w-0">
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="consecutive-chip">{{ tender.consecutive }}</span>
              <StatusBadge :status="tender.status" />
            </div>
            <h1 class="page-title mb-1">{{ tender.subject }}</h1>
            <p class="text-body-secondary mb-0">
              {{ formatMoney(tender.budget, tender.currency) }} · cierra el
              {{ formatDateTime(tender.closing_date, tender.closing_time) }}
            </p>
          </div>
          <div v-if="can('tenders.update')" class="flex-shrink-0">
            <RouterLink :to="{ name: 'tenders.edit', params: { id: tender.id } }" class="btn btn-primary">
              <i class="bi bi-pencil-square me-2" aria-hidden="true"></i>Editar
            </RouterLink>
          </div>
        </div>
      </header>

      <div class="card">
        <div class="card-header bg-transparent border-bottom-0 pb-0">
          <ul class="nav nav-underline" role="tablist">
            <li v-for="tab in TABS" :key="tab.key" class="nav-item" role="presentation">
              <button
                :id="`tab-${tab.key}`"
                type="button"
                class="nav-link"
                :class="{ active: activeTab === tab.key }"
                role="tab"
                :aria-selected="activeTab === tab.key"
                :aria-controls="`panel-${tab.key}`"
                @click="activeTab = tab.key"
              >
                <i class="bi me-1" :class="tab.icon" aria-hidden="true"></i>{{ tab.label }}
                <span v-if="tab.key === 'documents'" class="badge rounded-pill text-bg-light border">{{
                  tender.documents.length
                }}</span>
              </button>
            </li>
          </ul>
        </div>

        <div class="card-body">
          <div v-show="activeTab === 'basic'" id="panel-basic" role="tabpanel" aria-labelledby="tab-basic">
            <dl class="detail-grid">
              <div class="detail-item detail-item-wide">
                <dt>Objeto</dt>
                <dd>{{ tender.subject }}</dd>
              </div>
              <div class="detail-item detail-item-wide">
                <dt>Descripción / Alcance</dt>
                <dd class="text-pre-line">{{ tender.description }}</dd>
              </div>
              <div class="detail-item">
                <dt>Moneda</dt>
                <dd>{{ tender.currency }}</dd>
              </div>
              <div class="detail-item">
                <dt>Presupuesto</dt>
                <dd class="font-tabular">{{ formatMoney(tender.budget, tender.currency) }}</dd>
              </div>
              <div class="detail-item detail-item-wide">
                <dt>Actividad</dt>
                <dd>
                  <span class="activity-code">{{ tender.activity.product_code }}</span>
                  {{ tender.activity.product }}
                  <small class="d-block text-body-secondary">
                    {{ tender.activity.segment }} › {{ tender.activity.family }} › {{ tender.activity.class_name }}
                  </small>
                </dd>
              </div>
            </dl>
          </div>

          <div v-show="activeTab === 'schedule'" id="panel-schedule" role="tabpanel" aria-labelledby="tab-schedule">
            <ol class="timeline list-unstyled mb-0">
              <li class="timeline-item">
                <span class="timeline-dot" aria-hidden="true"></span>
                <div>
                  <p class="small text-body-secondary mb-0">Inicio</p>
                  <p class="fw-semibold mb-0">{{ formatDateTime(tender.start_date, tender.start_time) }}</p>
                </div>
              </li>
              <li class="timeline-item">
                <span class="timeline-dot timeline-dot-end" aria-hidden="true"></span>
                <div>
                  <p class="small text-body-secondary mb-0">Cierre</p>
                  <p class="fw-semibold mb-0">{{ formatDateTime(tender.closing_date, tender.closing_time) }}</p>
                </div>
              </li>
            </ol>
            <p class="small text-body-secondary mt-3 mb-0">
              Creada el {{ formatDateTime(tender.created_at) }} · última actualización
              {{ formatDateTime(tender.updated_at) }}
            </p>
          </div>

          <div v-show="activeTab === 'documents'" id="panel-documents" role="tabpanel" aria-labelledby="tab-documents">
            <ul v-if="tender.documents.length" class="document-list list-unstyled mb-0">
              <li v-for="document in tender.documents" :key="document.id" class="document-item">
                <i class="bi document-icon" :class="fileIcon(document.original_name)" aria-hidden="true"></i>
                <div class="min-w-0 flex-grow-1">
                  <p class="fw-semibold mb-0">{{ document.title }}</p>
                  <p class="small text-body-secondary mb-0">{{ document.description }}</p>
                </div>
                <div class="text-end small">
                  <a
                    :href="document.download_url"
                    class="file-link"
                    :aria-busy="downloadingId === document.id"
                    @click.prevent="download(tender.id, document)"
                  >
                    <span
                      v-if="downloadingId === document.id"
                      class="spinner-border spinner-border-sm me-1"
                      aria-hidden="true"
                    ></span>
                    <i v-else class="bi bi-download me-1" aria-hidden="true"></i>{{ document.original_name }}
                  </a>
                  <span class="d-block text-body-secondary">
                    {{ formatBytes(document.size) }} · {{ formatDate(document.created_at) }}
                  </span>
                </div>
              </li>
            </ul>
            <EmptyState
              v-else
              icon="bi-folder2-open"
              title="Sin documentos"
              :description="
                can('tenders.update')
                  ? 'Agregue documentos desde el formulario de edición.'
                  : 'Esta licitación aún no tiene documentos cargados.'
              "
            />
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
