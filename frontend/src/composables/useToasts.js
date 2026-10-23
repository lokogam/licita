import { reactive } from 'vue'

/**
 * @typedef {Object} Toast
 * @property {number} id
 * @property {'success'|'danger'|'info'} variant
 * @property {string} message
 */

/** @type {Toast[]} */
const toasts = reactive([])
let nextId = 1

/**
 * Global toast notifications.
 */
export function useToasts() {
  /**
   * @param {string} message
   * @param {Toast['variant']} [variant]
   * @param {number} [timeout] ms before it disappears
   */
  function notify(message, variant = 'success', timeout = 5000) {
    const id = nextId++
    toasts.push({ id, variant, message })
    setTimeout(() => dismiss(id), timeout)
  }

  /** @param {number} id */
  function dismiss(id) {
    const index = toasts.findIndex((toast) => toast.id === id)
    if (index !== -1) toasts.splice(index, 1)
  }

  return { toasts, notify, dismiss }
}
