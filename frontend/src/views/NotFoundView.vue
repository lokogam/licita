<script setup>
import EmptyState from '@/components/base/EmptyState.vue'
</script>

<template>
  <div class="card">
    <EmptyState icon="bi-signpost-split" title="Página no encontrada" description="La dirección que buscó no existe.">
      <RouterLink :to="{ name: 'tenders.index' }" class="btn btn-primary btn-sm">Ir a licitaciones</RouterLink>
    </EmptyState>
  </div>
</template>
