<script setup>
/**
 * Message shown when a list has no content, with an optional call to action slot.
 */
defineProps({
  icon: { type: String, default: 'bi-inbox' },
  title: { type: String, required: true },
  description: { type: String, default: '' },
})
</script>

<template>
  <div class="empty-state text-center py-5 px-3">
    <div class="empty-state-icon mx-auto mb-3">
      <i class="bi" :class="icon" aria-hidden="true"></i>
    </div>
    <h3 class="h6 mb-1">{{ title }}</h3>
    <p v-if="description" class="text-body-secondary small mb-3">{{ description }}</p>
    <slot />
  </div>
</template>
