<script setup>
/**
 * Page title row with optional breadcrumb text and actions slot.
 */
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
})
</script>

<template>
  <header class="page-header d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
    <div class="min-w-0">
      <slot name="eyebrow" />
      <h1 class="page-title mb-1">{{ title }}</h1>
      <p v-if="subtitle" class="text-body-secondary mb-0">{{ subtitle }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <slot name="actions" />
    </div>
  </header>
</template>
