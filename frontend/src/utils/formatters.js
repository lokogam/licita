/**
 * Display helpers (Colombian Spanish conventions).
 */

const EMPTY = '—'

/**
 * "2026-11-01" -> "01/11/2026"
 *
 * @param {string|null|undefined} isoDate
 * @returns {string}
 */
export function formatDate(isoDate) {
  if (!isoDate) return EMPTY
  const [year, month, day] = isoDate.slice(0, 10).split('-')

  return `${day}/${month}/${year}`
}

/**
 * Accepts ("2026-11-01", "08:00") or a single "2026-11-01 08:00:00" value.
 *
 * @param {string|null|undefined} date
 * @param {string} [time]
 * @returns {string}
 */
export function formatDateTime(date, time) {
  if (!date) return EMPTY
  const clock = (time ?? date.slice(11)).slice(0, 5)

  return clock ? `${formatDate(date)} ${clock}` : formatDate(date)
}

/**
 * @param {string|number} amount
 * @param {string} currency ISO 4217 code
 * @returns {string}
 */
export function formatMoney(amount, currency) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(amount))
}

/**
 * @param {number} bytes
 * @returns {string}
 */
export function formatBytes(bytes) {
  if (bytes < 1024) return `${bytes} B`
  const units = ['KB', 'MB', 'GB']
  let value = bytes / 1024
  let unit = 0
  while (value >= 1024 && unit < units.length - 1) {
    value /= 1024
    unit++
  }

  return `${Number.isInteger(value) ? value : value.toFixed(1)} ${units[unit]}`
}
