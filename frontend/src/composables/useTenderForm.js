import { computed, reactive, ref, watch } from 'vue'
import { validateTender } from '@/utils/validators'

const FIELDS = [
  'subject',
  'description',
  'currency',
  'budget',
  'activity_id',
  'start_date',
  'start_time',
  'closing_date',
  'closing_time',
]

/**
 * @typedef {Object} ActivityOption
 * @property {number} id
 * @property {number} product_code
 * @property {string} product
 */

/**
 * State, validation and payload of the tender form (basic information + schedule).
 *
 * @param {object|null} [initial] existing tender used to prefill the form
 */
export function useTenderForm(initial = null) {
  const form = reactive({
    subject: initial?.subject ?? '',
    description: initial?.description ?? '',
    currency: initial?.currency ?? '',
    budget: initial?.budget ?? '',
    /** @type {ActivityOption|null} */
    activity: initial?.activity ?? null,
    start_date: initial?.start_date ?? '',
    start_time: initial?.start_time ?? '',
    closing_date: initial?.closing_date ?? '',
    closing_time: initial?.closing_time ?? '',
  })

  const touched = reactive(Object.fromEntries(FIELDS.map((field) => [field, false])))
  const serverErrors = ref({})

  const values = computed(() => ({ ...form, activity_id: form.activity?.id ?? null }))
  const errors = computed(() => validateTender(values.value))
  const isValid = computed(() => Object.keys(errors.value).length === 0)

  // A server message is dropped as soon as the user edits that field.
  for (const field of FIELDS) {
    const key = field === 'activity_id' ? 'activity' : field
    watch(
      () => form[key],
      () => {
        if (serverErrors.value[field]) {
          const { [field]: _removed, ...rest } = serverErrors.value
          serverErrors.value = rest
        }
      },
      { flush: 'sync' },
    )
  }

  /** @param {string} field */
  function touch(field) {
    touched[field] = true
  }

  function touchAll() {
    FIELDS.forEach(touch)
  }

  /**
   * Error to display for a field: server errors always, client errors once touched.
   *
   * @param {string} field
   * @returns {string}
   */
  function visibleError(field) {
    if (serverErrors.value[field]) return serverErrors.value[field]
    return touched[field] ? (errors.value[field] ?? '') : ''
  }

  /** @param {Record<string, string>} messages */
  function setServerErrors(messages) {
    serverErrors.value = { ...messages }
  }

  /** @returns {Record<string, string|number|null>} body for the API */
  function payload() {
    return Object.fromEntries(FIELDS.map((field) => [field, values.value[field]]))
  }

  return { form, errors, isValid, touch, touchAll, visibleError, setServerErrors, payload }
}
