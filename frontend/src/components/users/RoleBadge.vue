<script setup>
/**
 * User role pill (Administrador / Editor / Lector).
 */
import { computed } from 'vue'

const props = defineProps({
  role: { type: Object, required: true },
})

const ICONS = { admin: 'bi-shield-lock', editor: 'bi-pencil', viewer: 'bi-eye' }

const icon = computed(() => ICONS[props.role.value] ?? 'bi-person')
</script>

<template>
  <span class="role-badge" :class="`role-${role.value}`">
    <i class="bi" :class="icon" aria-hidden="true"></i>
    {{ role.label }}
  </span>
</template>
