<script setup>
/**
 * "Mostrando X–Y de Z" + page buttons with ellipsis.
 */
import { computed } from 'vue'

const props = defineProps({
  meta: { type: Object, required: true },
})

const emit = defineEmits(['change'])

const pages = computed(() => {
  const { current_page: current, last_page: last } = props.meta
  const wanted = new Set([1, last, current - 1, current, current + 1].filter((p) => p >= 1 && p <= last))
  const sorted = [...wanted].sort((a, b) => a - b)

  const items = []
  sorted.forEach((page, index) => {
    if (index > 0 && page - sorted[index - 1] > 1) items.push({ type: 'gap', key: `gap-${page}` })
    items.push({ type: 'page', key: page, page })
  })

  return items
})

function go(page) {
  if (page >= 1 && page <= props.meta.last_page && page !== props.meta.current_page) emit('change', page)
}
</script>

<template>
  <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
    <p class="small text-body-secondary mb-0">
      <template v-if="meta.total > 0">Mostrando {{ meta.from }}–{{ meta.to }} de {{ meta.total }}</template>
      <template v-else>Sin resultados</template>
    </p>
    <nav v-if="meta.last_page > 1" aria-label="Paginación">
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
          <button type="button" class="page-link" aria-label="Página anterior" @click="go(meta.current_page - 1)">
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
          </button>
        </li>
        <li
          v-for="item in pages"
          :key="item.key"
          class="page-item"
          :class="{ active: item.page === meta.current_page, disabled: item.type === 'gap' }"
        >
          <span v-if="item.type === 'gap'" class="page-link">…</span>
          <button
            v-else
            type="button"
            class="page-link"
            :aria-label="`Página ${item.page}`"
            :aria-current="item.page === meta.current_page ? 'page' : undefined"
            @click="go(item.page)"
          >
            {{ item.page }}
          </button>
        </li>
        <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
          <button type="button" class="page-link" aria-label="Página siguiente" @click="go(meta.current_page + 1)">
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
          </button>
        </li>
      </ul>
    </nav>
  </div>
</template>
