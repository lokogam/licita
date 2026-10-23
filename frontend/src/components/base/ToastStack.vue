<script setup>
/**
 * Renders the global toast notifications.
 */
import { useToasts } from '@/composables/useToasts'

const { toasts, dismiss } = useToasts()

const ICONS = { success: 'bi-check-circle-fill', danger: 'bi-exclamation-octagon-fill', info: 'bi-info-circle-fill' }
</script>

<template>
  <div class="toast-container position-fixed top-0 end-0 p-3" aria-live="polite" aria-atomic="true">
    <TransitionGroup name="toast">
      <div v-for="toast in toasts" :key="toast.id" class="toast show app-toast" :class="`app-toast-${toast.variant}`" role="status">
        <div class="d-flex align-items-start gap-2 p-3">
          <i class="bi fs-5" :class="ICONS[toast.variant]" aria-hidden="true"></i>
          <div class="flex-grow-1 small">{{ toast.message }}</div>
          <button type="button" class="btn-close btn-close-sm" aria-label="Cerrar" @click="dismiss(toast.id)"></button>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>
