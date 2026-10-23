<script setup>
/**
 * Create tender page. The consecutive is assigned by the API.
 */
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/base/PageHeader.vue'
import TenderForm from '@/components/tenders/TenderForm.vue'
import { createTender } from '@/api/tenders'
import { useToasts } from '@/composables/useToasts'

const router = useRouter()
const { notify } = useToasts()

const submitting = ref(false)
const serverErrors = ref({})

async function save(payload) {
  submitting.value = true
  serverErrors.value = {}
  try {
    const tender = await createTender(payload)
    notify(`Licitación ${tender.consecutive} creada correctamente.`)
    router.push({ name: 'tenders.show', params: { id: tender.id } })
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
    <RouterLink :to="{ name: 'tenders.index' }" class="back-link mb-3">
      <i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al listado
    </RouterLink>
    <PageHeader title="Nueva licitación" subtitle="El consecutivo se asigna automáticamente al guardar." />
    <TenderForm
      submit-label="Crear licitación"
      :submitting="submitting"
      :server-errors="serverErrors"
      @submit="save"
      @cancel="router.push({ name: 'tenders.index' })"
    />
  </div>
</template>
