<script setup>
import ToastStack from '@/components/base/ToastStack.vue'
</script>

<template>
  <div class="app-shell">
    <nav class="navbar app-navbar" aria-label="Principal">
      <div class="container-xxl">
        <RouterLink :to="{ name: 'tenders.index' }" class="navbar-brand d-flex align-items-center gap-2">
          <span class="brand-mark" aria-hidden="true"></span>
          <span class="brand-name">suplos</span>
          <span class="brand-divider" aria-hidden="true"></span>
          <span class="brand-module">Licitaciones</span>
        </RouterLink>
        <a href="/api/docs" class="nav-link-docs" target="_blank" rel="noopener">
          <i class="bi bi-braces me-1" aria-hidden="true"></i>API
        </a>
      </div>
    </nav>

    <main class="container-xxl py-4 py-lg-5">
      <RouterView />
    </main>

    <ToastStack />
  </div>
</template>
