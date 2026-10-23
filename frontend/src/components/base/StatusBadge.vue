<script setup>
/**
 * Tender status pill (Programada / Abierta / Cerrada).
 */
import { computed } from 'vue'

const props = defineProps({
  status: { type: Object, required: true },
})

const STYLES = {
  scheduled: { className: 'status-scheduled', icon: 'bi-calendar-event' },
  open: { className: 'status-open', icon: 'bi-broadcast' },
  closed: { className: 'status-closed', icon: 'bi-lock' },
}

const style = computed(() => STYLES[props.status.value] ?? STYLES.closed)
</script>

<template>
  <span class="status-badge" :class="style.className">
    <i class="bi" :class="style.icon" aria-hidden="true"></i>
    {{ status.label }}
  </span>
</template>
