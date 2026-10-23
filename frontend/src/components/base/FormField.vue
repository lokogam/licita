<script setup>
/**
 * Label + control + feedback wrapper with an optional character counter.
 */
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  inputId: { type: String, required: true },
  required: { type: Boolean, default: false },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  count: { type: Number, default: null },
  max: { type: Number, default: null },
})

const showCounter = computed(() => props.count !== null && props.max !== null)
const counterClass = computed(() => {
  if (!showCounter.value) return ''
  if (props.count >= props.max) return 'text-danger'
  return props.count >= props.max * 0.9 ? 'text-warning-emphasis' : 'text-body-secondary'
})
</script>

<template>
  <div class="form-field">
    <label :for="inputId" class="form-label">
      {{ label }}<span v-if="required" class="text-danger ms-1" aria-hidden="true">*</span>
    </label>
    <slot />
    <div class="d-flex justify-content-between gap-3 mt-1 small">
      <div>
        <div v-if="error" :id="`${inputId}-error`" class="invalid-feedback d-block mt-0">{{ error }}</div>
        <div v-else-if="hint" class="form-text mt-0">{{ hint }}</div>
      </div>
      <span v-if="showCounter" class="form-counter" :class="counterClass" aria-live="polite">{{ count }}/{{ max }}</span>
    </div>
  </div>
</template>
