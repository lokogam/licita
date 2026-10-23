<script setup>
/**
 * Navbar account menu: current user, role and "Cerrar sesión".
 */
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import RoleBadge from '@/components/users/RoleBadge.vue'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { user, logout } = useAuth()

const open = ref(false)
const leaving = ref(false)
const root = ref(null)

const initials = computed(() =>
  (user.value?.name ?? '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word[0].toUpperCase())
    .join(''),
)

function onDocumentClick(event) {
  if (root.value && !root.value.contains(event.target)) open.value = false
}

function onKeydown(event) {
  if (event.key === 'Escape') open.value = false
}

watch(open, (visible) => {
  if (visible) {
    document.addEventListener('click', onDocumentClick)
    document.addEventListener('keydown', onKeydown)
  } else {
    document.removeEventListener('click', onDocumentClick)
    document.removeEventListener('keydown', onKeydown)
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onKeydown)
})

async function signOut() {
  leaving.value = true
  await logout()
  open.value = false
  leaving.value = false
  router.push({ name: 'login' })
}
</script>

<template>
  <div v-if="user" ref="root" class="user-menu">
    <button
      type="button"
      class="user-menu-toggle"
      aria-haspopup="menu"
      :aria-expanded="open"
      aria-controls="user-menu-panel"
      :aria-label="`Cuenta de ${user.name}`"
      @click="open = !open"
    >
      <span class="user-avatar" aria-hidden="true">{{ initials }}</span>
      <span class="user-menu-text">
        <span class="user-menu-name">{{ user.name }}</span>
        <span class="user-menu-role">{{ user.role.label }}</span>
      </span>
      <i class="bi bi-chevron-down small" aria-hidden="true"></i>
    </button>

    <div v-if="open" id="user-menu-panel" class="user-menu-panel" role="menu">
      <div class="px-3 pt-3 pb-2">
        <p class="fw-semibold mb-0 text-truncate">{{ user.name }}</p>
        <p class="small text-body-secondary mb-2 text-truncate">{{ user.email }}</p>
        <RoleBadge :role="user.role" />
      </div>
      <hr class="my-1" />
      <button type="button" class="user-menu-item" role="menuitem" :disabled="leaving" @click="signOut">
        <span v-if="leaving" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
        <i v-else class="bi bi-box-arrow-right" aria-hidden="true"></i>
        Cerrar sesión
      </button>
    </div>
  </div>
</template>
