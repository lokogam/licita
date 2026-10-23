<script setup>
/**
 * Tenders list with filters (synced to the URL), pagination and Excel export.
 */
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import EmptyState from '@/components/base/EmptyState.vue'
import PageHeader from '@/components/base/PageHeader.vue'
import PaginationBar from '@/components/base/PaginationBar.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { exportTenders, listTenders } from '@/api/tenders'
import { useAuth } from '@/composables/useAuth'
import { useDebouncedFn } from '@/composables/useDebouncedFn'
import { useToasts } from '@/composables/useToasts'
import { saveBlob } from '@/utils/download'
import { formatDate } from '@/utils/formatters'

const PER_PAGE = 10
const FILTER_KEYS = ['consecutive', 'subject', 'description', 'status']
const STATUS_OPTIONS = [
  { value: '', label: 'Todos' },
  { value: 'scheduled', label: 'Programada' },
  { value: 'open', label: 'Abierta' },
  { value: 'closed', label: 'Cerrada' },
]

const route = useRoute()
const router = useRouter()
const { notify } = useToasts()
const { can } = useAuth()

const filters = reactive(Object.fromEntries(FILTER_KEYS.map((key) => [key, String(route.query[key] ?? '')])))
const tenders = ref([])
const meta = ref(null)
const loading = ref(true)
const exporting = ref(false)
const error = ref('')

/** Non-empty filters only. */
const activeFilters = computed(() =>
  Object.fromEntries(
    FILTER_KEYS.map((key) => [key, String(route.query[key] ?? '').trim()]).filter(([, value]) => value !== ''),
  ),
)
const hasFilters = computed(() => Object.keys(activeFilters.value).length > 0)
const currentPage = computed(() => Math.max(1, Number(route.query.page) || 1))

async function load() {
  loading.value = true
  error.value = ''
  try {
    const result = await listTenders({ page: currentPage.value, per_page: PER_PAGE, ...activeFilters.value })
    tenders.value = result.data
    meta.value = result.meta
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

watch(() => route.query, load, { immediate: true })

function applyFiltersNow() {
  const query = Object.fromEntries(
    Object.entries(filters)
      .map(([key, value]) => [key, value.trim()])
      .filter(([, value]) => value !== ''),
  )
  router.replace({ query })
}

const applyFilters = useDebouncedFn(applyFiltersNow, 300)

function clearFilters() {
  FILTER_KEYS.forEach((key) => (filters[key] = ''))
  applyFilters.cancel()
  router.replace({ query: {} })
}

function goToPage(page) {
  router.push({ query: { ...route.query, page } })
}

async function exportExcel() {
  exporting.value = true
  try {
    const { blob, filename } = await exportTenders(activeFilters.value)
    saveBlob(blob, filename)
    notify('El reporte en Excel se generó correctamente.')
  } catch (e) {
    notify(e.message, 'danger')
  } finally {
    exporting.value = false
  }
}
</script>

<template>
  <div>
    <PageHeader title="Licitaciones" subtitle="Consulte, filtre y exporte las licitaciones registradas.">
      <template #actions>
        <button type="button" class="btn btn-outline-primary" :disabled="exporting" @click="exportExcel">
          <span v-if="exporting" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
          <i v-else class="bi bi-file-earmark-excel me-2" aria-hidden="true"></i>Exportar Excel
        </button>
        <RouterLink v-if="can('tenders.create')" :to="{ name: 'tenders.create' }" class="btn btn-primary">
          <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Nueva licitación
        </RouterLink>
      </template>
    </PageHeader>

    <section class="card filters-card mb-3" aria-label="Filtros">
      <div class="card-body">
        <div class="row g-2 align-items-end">
          <div class="col-md-2">
            <label for="filter-consecutive" class="form-label small">Consecutivo</label>
            <div class="input-icon">
              <i class="bi bi-hash" aria-hidden="true"></i>
              <input
                id="filter-consecutive"
                v-model="filters.consecutive"
                type="search"
                class="form-control"
                placeholder="O-0001-26"
                @input="applyFilters"
              />
            </div>
          </div>
          <div class="col-md-3">
            <label for="filter-subject" class="form-label small">Objeto</label>
            <div class="input-icon">
              <i class="bi bi-search" aria-hidden="true"></i>
              <input
                id="filter-subject"
                v-model="filters.subject"
                type="search"
                class="form-control"
                placeholder="Buscar por objeto"
                @input="applyFilters"
              />
            </div>
          </div>
          <div class="col-md-3">
            <label for="filter-description" class="form-label small">Descripción</label>
            <div class="input-icon">
              <i class="bi bi-text-left" aria-hidden="true"></i>
              <input
                id="filter-description"
                v-model="filters.description"
                type="search"
                class="form-control"
                placeholder="Buscar en la descripción"
                @input="applyFilters"
              />
            </div>
          </div>
          <div class="col-md-3">
            <label for="filter-status" class="form-label small">Estado</label>
            <select id="filter-status" v-model="filters.status" class="form-select" @change="applyFiltersNow">
              <option v-for="option in STATUS_OPTIONS" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </div>
          <div class="col-md-1 text-md-end">
            <button v-if="hasFilters" type="button" class="btn btn-link btn-sm px-0" @click="clearFilters">
              Limpiar
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="card data-card">
      <div v-if="error" class="alert alert-danger m-3" role="alert">
        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>{{ error }}
        <button type="button" class="btn btn-sm btn-outline-danger ms-2" @click="load">Reintentar</button>
      </div>

      <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0 data-table">
          <thead>
            <tr>
              <th scope="col">Consecutivo</th>
              <th scope="col">Objeto</th>
              <th scope="col">Descripción</th>
              <th scope="col">Fecha de inicio</th>
              <th scope="col">Fecha de cierre</th>
              <th scope="col">Estado</th>
              <th scope="col" class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody v-if="loading">
            <tr v-for="n in 5" :key="n" class="placeholder-glow" aria-hidden="true">
              <td v-for="c in 7" :key="c"><span class="placeholder col-8 rounded"></span></td>
            </tr>
          </tbody>
          <tbody v-else-if="tenders.length">
            <tr v-for="tender in tenders" :key="tender.id">
              <td class="consecutive">{{ tender.consecutive }}</td>
              <td class="cell-subject">
                <span class="text-truncate d-block" :title="tender.subject">{{ tender.subject }}</span>
              </td>
              <td class="cell-description text-body-secondary">
                <span class="text-truncate d-block" :title="tender.description">{{ tender.description }}</span>
              </td>
              <td class="text-nowrap">{{ formatDate(tender.start_date) }}</td>
              <td class="text-nowrap">{{ formatDate(tender.closing_date) }}</td>
              <td><StatusBadge :status="tender.status" /></td>
              <td class="text-end">
                <RouterLink
                  :to="{ name: 'tenders.show', params: { id: tender.id } }"
                  class="btn btn-sm btn-light"
                  :aria-label="`Ver detalle de ${tender.consecutive}`"
                >
                  <i class="bi bi-eye me-1" aria-hidden="true"></i>Ver detalle
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>

        <EmptyState
          v-if="!loading && tenders.length === 0"
          :icon="hasFilters ? 'bi-funnel' : 'bi-clipboard2-plus'"
          :title="hasFilters ? 'No encontramos licitaciones con esos filtros' : 'Aún no hay licitaciones'"
          :description="
            hasFilters
              ? 'Ajuste o limpie los filtros para ver más resultados.'
              : can('tenders.create')
                ? 'Cree la primera licitación para empezar.'
                : 'Cuando se registren licitaciones aparecerán aquí.'
          "
        >
          <button v-if="hasFilters" type="button" class="btn btn-outline-secondary btn-sm" @click="clearFilters">
            Limpiar filtros
          </button>
          <RouterLink v-else-if="can('tenders.create')" :to="{ name: 'tenders.create' }" class="btn btn-primary btn-sm"
            >Nueva licitación</RouterLink
          >
        </EmptyState>
      </div>

      <div v-if="meta && !error" class="card-footer bg-transparent">
        <PaginationBar :meta="meta" @change="goToPage" />
      </div>
    </section>
  </div>
</template>
