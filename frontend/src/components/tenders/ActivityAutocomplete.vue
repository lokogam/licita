<script setup>
/**
 * Searchable select for UNSPSC activities (server-side search via Axios).
 * Disabled until the initial suggestions are loaded.
 */
import { computed, onMounted, ref, watch } from 'vue'
import { searchActivities } from '@/api/activities'
import { useDebouncedFn } from '@/composables/useDebouncedFn'

const MIN_SEARCH = 2

const props = defineProps({
  modelValue: { type: Object, default: null },
  inputId: { type: String, required: true },
  invalid: { type: Boolean, default: false },
  describedBy: { type: String, default: undefined },
})

const emit = defineEmits(['update:modelValue', 'blur'])

const ready = ref(false)
const loadError = ref('')
const searching = ref(false)
const open = ref(false)
const query = ref('')
const options = ref([])
const initialOptions = ref([])
const highlighted = ref(-1)
let requestId = 0

const listboxId = computed(() => `${props.inputId}-listbox`)
const placeholder = computed(() => {
  if (loadError.value) return 'No fue posible cargar las actividades'
  return ready.value ? 'Busque por nombre o código del producto' : 'Cargando actividades…'
})

/** @param {{product_code: number, product: string}|null} activity */
const label = (activity) => (activity ? `${activity.product_code} · ${activity.product}` : '')

watch(
  () => props.modelValue,
  (activity) => {
    if (activity) query.value = label(activity)
  },
  { immediate: true },
)

async function loadInitial() {
  loadError.value = ''
  try {
    initialOptions.value = await searchActivities('')
    options.value = initialOptions.value
    ready.value = true
  } catch {
    loadError.value = 'No fue posible cargar las actividades.'
  }
}

onMounted(loadInitial)

const runSearch = useDebouncedFn(async (term) => {
  if (term.length < MIN_SEARCH) {
    options.value = initialOptions.value
    return
  }

  const current = ++requestId
  searching.value = true
  try {
    const results = await searchActivities(term)
    if (current === requestId) options.value = results
  } catch {
    if (current === requestId) options.value = []
  } finally {
    if (current === requestId) searching.value = false
  }
}, 300)

function onInput(event) {
  query.value = event.target.value
  if (props.modelValue) emit('update:modelValue', null)
  open.value = true
  highlighted.value = -1
  runSearch(query.value.trim())
}

function select(activity) {
  emit('update:modelValue', activity)
  query.value = label(activity)
  open.value = false
  highlighted.value = -1
}

function onKeydown(event) {
  if (!open.value && ['ArrowDown', 'ArrowUp'].includes(event.key)) open.value = true

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      highlighted.value = Math.min(highlighted.value + 1, options.value.length - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      highlighted.value = Math.max(highlighted.value - 1, 0)
      break
    case 'Enter':
      if (open.value && options.value[highlighted.value]) {
        event.preventDefault()
        select(options.value[highlighted.value])
      }
      break
    case 'Escape':
      open.value = false
      break
  }
}

function onBlur() {
  open.value = false
  emit('blur')
}
</script>

<template>
  <div class="autocomplete position-relative">
    <div class="input-group has-validation">
      <span class="input-group-text">
        <span v-if="!ready && !loadError" class="spinner-border spinner-border-sm" aria-hidden="true"></span>
        <i v-else class="bi bi-search" aria-hidden="true"></i>
      </span>
      <input
        :id="inputId"
        type="text"
        class="form-control"
        :class="{ 'is-invalid': invalid }"
        role="combobox"
        autocomplete="off"
        aria-autocomplete="list"
        :aria-expanded="open"
        :aria-controls="listboxId"
        :aria-activedescendant="highlighted >= 0 ? `${listboxId}-${highlighted}` : undefined"
        :aria-invalid="invalid"
        :aria-describedby="describedBy"
        :disabled="!ready"
        :placeholder="placeholder"
        :value="query"
        @input="onInput"
        @focus="open = true"
        @blur="onBlur"
        @keydown="onKeydown"
      />
      <button v-if="loadError" type="button" class="btn btn-outline-secondary" @click="loadInitial">Reintentar</button>
    </div>

    <ul v-show="open && ready" :id="listboxId" class="autocomplete-menu list-unstyled mb-0" role="listbox">
      <li v-if="searching" class="autocomplete-status">Buscando…</li>
      <li v-else-if="options.length === 0" class="autocomplete-status">Sin coincidencias. Pruebe con otro término.</li>
      <li
        v-for="(activity, index) in options"
        v-else
        :id="`${listboxId}-${index}`"
        :key="activity.id"
        role="option"
        class="autocomplete-option"
        :class="{ active: index === highlighted }"
        :aria-selected="modelValue?.id === activity.id"
        @mousedown.prevent="select(activity)"
        @mouseenter="highlighted = index"
      >
        <span class="autocomplete-code">{{ activity.product_code }}</span>
        <span class="autocomplete-text">
          <span class="d-block text-truncate">{{ activity.product }}</span>
          <small v-if="activity.class_name" class="d-block text-body-secondary text-truncate">{{
            activity.class_name
          }}</small>
        </span>
      </li>
    </ul>
  </div>
</template>
