import { onBeforeUnmount } from 'vue'

/**
 * Returns a debounced version of fn that is cancelled when the component unmounts.
 *
 * @template {(...args: any[]) => void} T
 * @param {T} fn
 * @param {number} wait ms
 * @returns {T & {cancel: () => void}}
 */
export function useDebouncedFn(fn, wait) {
  let timer = null

  const debounced = (...args) => {
    clearTimeout(timer)
    timer = setTimeout(() => fn(...args), wait)
  }
  debounced.cancel = () => clearTimeout(timer)

  onBeforeUnmount(debounced.cancel)

  return debounced
}
