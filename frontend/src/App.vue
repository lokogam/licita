<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import ToastStack from '@/components/base/ToastStack.vue'
import UserMenu from '@/components/layout/UserMenu.vue'
import { useAuth } from '@/composables/useAuth'

const route = useRoute()
const { isAuthenticated, can } = useAuth()

/** The login page (and the blank state before the first navigation) has no app shell. */
const showShell = computed(() => route.matched.length > 0 && route.meta.layout !== 'blank')
</script>

<template>
  <div class="app-shell">
    <template v-if="showShell">
      <nav class="navbar app-navbar" aria-label="Principal">
        <div class="container-xxl gap-3">
          <RouterLink :to="{ name: 'tenders.index' }" class="navbar-brand d-flex align-items-center gap-2 me-0">
            <span class="brand-mark" aria-hidden="true"></span>
            <span class="brand-name">licita</span>
            <span class="brand-divider" aria-hidden="true"></span>
            <span class="brand-module">Licitaciones</span>
          </RouterLink>

          <div class="d-flex align-items-center gap-1 gap-sm-2 ms-auto">
            <template v-if="isAuthenticated">
              <RouterLink :to="{ name: 'tenders.index' }" class="app-nav-link" active-class="active">
                <i class="bi bi-clipboard2-data" aria-hidden="true"></i>
                <span class="app-nav-label">Licitaciones</span>
              </RouterLink>
              <RouterLink
                v-if="can('users.manage')"
                :to="{ name: 'users.index' }"
                class="app-nav-link"
                active-class="active"
              >
                <i class="bi bi-people" aria-hidden="true"></i>
                <span class="app-nav-label">Usuarios</span>
              </RouterLink>
            </template>
            <a href="/api/docs" class="app-nav-link" target="_blank" rel="noopener" title="Documentación de la API">
              <i class="bi bi-braces" aria-hidden="true"></i>
              <span class="app-nav-label">API</span>
            </a>
            <UserMenu v-if="isAuthenticated" class="ms-1 ms-sm-2" />
          </div>
        </div>
      </nav>

      <main class="container-xxl py-4 py-lg-5">
        <RouterView />
      </main>
    </template>

    <RouterView v-else />

    <ToastStack />
  </div>
</template>
