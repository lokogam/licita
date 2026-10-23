<script setup>
/**
 * Accessible Bootstrap-styled modal controlled by Vue state.
 */
import { onBeforeUnmount, watch } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, required: true },
  icon: { type: String, default: '' },
  titleId: { type: String, default: 'app-modal-title' },
})

const emit = defineEmits(['close'])

function onKeydown(event) {
  if (event.key === 'Escape') emit('close')
}

watch(
  () => props.show,
  (visible) => {
    if (typeof document === 'undefined') return
    document.body.classList.toggle('modal-open', visible)
    if (visible) document.addEventListener('keydown', onKeydown)
    else document.removeEventListener('keydown', onKeydown)
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  document.body.classList.remove('modal-open')
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <template v-if="show">
    <div class="modal d-block" tabindex="-1" role="dialog" aria-modal="true" :aria-labelledby="titleId">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h2 :id="titleId" class="modal-title fs-5">
              <i v-if="icon" class="bi me-2 text-primary" :class="icon" aria-hidden="true"></i>{{ title }}
            </h2>
            <button type="button" class="btn-close" aria-label="Cerrar" @click="emit('close')"></button>
          </div>
          <div class="modal-body">
            <slot />
          </div>
          <div class="modal-footer">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop show"></div>
  </template>
</template>
