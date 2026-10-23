<script setup>
/**
 * Edit tender page: same sections as creation plus documents, all sent in one multipart request.
 */
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import EmptyState from '@/components/base/EmptyState.vue'
import PageHeader from '@/components/base/PageHeader.vue'
import DocumentsSection from '@/components/tenders/DocumentsSection.vue'
import TenderForm from '@/components/tenders/TenderForm.vue'
import { getTender, updateTender } from '@/api/tenders'
import { useToasts } from '@/composables/useToasts'

const route = useRoute()
const router = useRouter()
const { notify } = useToasts()

const tender = ref(null)
const loading = ref(true)
const loadError = ref('')
const pending = ref([])
const submitting = ref(false)
const serverErrors = ref({})

const documentsValid = computed(() => (tender.value?.documents.length ?? 0) + pending.value.length > 0)

/** Document errors come as documents / documents.N.field; they are shown in the documents section. */
const documentsError = computed(() => {
  const entry = Object.entries(serverErrors.value).find(([field]) => field.startsWith('documents'))
  return entry ? entry[1] : ''
})

onMounted(async () => {
  try {
    tender.value = await getTender(route.params.id)
  } catch (e) {
    loadError.value = e.message
  } finally {
    loading.value = false
  }
})

async function save(payload) {
  submitting.value = true
  serverErrors.value = {}
  try {
    const updated = await updateTender(tender.value.id, payload, pending.value)
    notify(`Licitación ${updated.consecutive} actualizada correctamente.`)
    router.push({ name: 'tenders.show', params: { id: updated.id } })
  } catch (e) {
    serverErrors.value = e.errors ?? {}
    notify(e.message, 'danger')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div>
    <RouterLink :to="{ name: 'tenders.show', params: { id: route.params.id } }" class="back-link mb-3">
      <i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al detalle
    </RouterLink>

    <div v-if="loading" class="card placeholder-glow p-4" aria-busy="true">
      <span class="placeholder col-4 mb-3"></span>
      <span class="placeholder col-8"></span>
    </div>

    <div v-else-if="loadError" class="card">
      <EmptyState icon="bi-search" :title="loadError">
        <RouterLink :to="{ name: 'tenders.index' }" class="btn btn-primary btn-sm">Ir al listado</RouterLink>
      </EmptyState>
    </div>

    <template v-else>
      <PageHeader
        :title="`Editar ${tender.consecutive}`"
        subtitle="Actualice la información y adjunte los documentos."
      />
      <TenderForm
        :initial="tender"
        submit-label="Guardar cambios"
        :submitting="submitting"
        :server-errors="serverErrors"
        :extra-valid="documentsValid"
        @submit="save"
        @cancel="router.push({ name: 'tenders.show', params: { id: tender.id } })"
      >
        <template #extra>
          <DocumentsSection v-model="pending" :existing="tender.documents" :error="documentsError" />
        </template>
      </TenderForm>
    </template>
  </div>
</template>
