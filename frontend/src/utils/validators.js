/**
 * Client-side validation rules. They mirror the API rules and messages so the
 * user sees the same feedback before and after submitting.
 */

export const LIMITS = Object.freeze({
  subject: 150,
  description: 400,
  documentTitle: 100,
  documentDescription: 200,
  documentMegabytes: 10,
})

export const CURRENCIES = Object.freeze(['COP', 'USD', 'EUR'])

export const MAX_BUDGET = 9999999999999.99

const DOCUMENT_EXTENSIONS = ['pdf', 'zip']
const DOCUMENT_MIME_TYPES = [
  'application/pdf',
  'application/zip',
  'application/x-zip-compressed',
  'application/x-zip',
  '', // some browsers do not report a type for .zip files
]

const LABELS = {
  subject: 'objeto',
  description: 'descripción',
  currency: 'moneda',
  budget: 'presupuesto',
  activity_id: 'actividad',
  start_date: 'fecha de inicio',
  start_time: 'hora de inicio',
  closing_date: 'fecha de cierre',
  closing_time: 'hora de cierre',
}

/**
 * @param {string} value
 * @returns {boolean} true for a real calendar date in YYYY-MM-DD format
 */
export function isValidDate(value) {
  if (typeof value !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return false
  const [year, month, day] = value.split('-').map(Number)
  const date = new Date(Date.UTC(year, month - 1, day))

  return date.getUTCFullYear() === year && date.getUTCMonth() === month - 1 && date.getUTCDate() === day
}

/**
 * @param {string} value
 * @returns {boolean} true for a 24h time in HH:mm format
 */
export function isValidTime(value) {
  return typeof value === 'string' && /^([01]\d|2[0-3]):[0-5]\d$/.test(value)
}

/**
 * Keeps only digits and one decimal point, with at most two decimals.
 *
 * @param {string} raw
 * @returns {string}
 */
export function sanitizeBudget(raw) {
  const cleaned = String(raw ?? '').replace(/[^\d.]/g, '')
  const [integer, ...rest] = cleaned.split('.')
  if (rest.length === 0) return integer

  const decimals = rest.join('').slice(0, 2)

  return `${integer === '' ? '0' : integer}.${decimals}`
}

/**
 * Returns the first error message for a required text field, or null.
 *
 * @param {unknown} value
 * @param {string} label
 * @param {number} [max]
 * @returns {string|null}
 */
function textError(value, label, max) {
  if (value === null || value === undefined || value === '') return `El campo ${label} es obligatorio.`
  if (typeof value !== 'string') return `El campo ${label} debe ser texto.`
  if (value.trim() === '') return `El campo ${label} no puede contener solo espacios.`
  if (max && value.length > max) return `El campo ${label} no debe superar ${max} caracteres.`

  return null
}

function isEmpty(value) {
  return value === null || value === undefined || String(value).trim() === ''
}

function budgetError(value) {
  if (isEmpty(value)) return 'El campo presupuesto es obligatorio.'
  const text = String(value)
  if (!/^\d+(\.\d{1,2})?$/.test(text)) {
    return 'El campo presupuesto debe ser un número con máximo 2 decimales (solo números y punto).'
  }
  if (Number(text) <= 0) return 'El campo presupuesto debe ser mayor a 0.'
  if (Number(text) > MAX_BUDGET) return `El campo presupuesto no debe ser mayor a ${MAX_BUDGET}.`

  return null
}

/**
 * @typedef {Object} TenderForm
 * @property {string} subject
 * @property {string} description
 * @property {string} currency
 * @property {string} budget
 * @property {number|string|null} activity_id
 * @property {string} start_date
 * @property {string} start_time
 * @property {string} closing_date
 * @property {string} closing_time
 */

/**
 * Validates the basic information and schedule sections.
 *
 * @param {Partial<TenderForm>} form
 * @returns {Record<string, string>} first error message per field
 */
export function validateTender(form) {
  const errors = {}
  const set = (field, message) => {
    if (message) errors[field] = message
  }

  set('subject', textError(form.subject, LABELS.subject, LIMITS.subject))
  set('description', textError(form.description, LABELS.description, LIMITS.description))

  if (isEmpty(form.currency)) set('currency', 'El campo moneda es obligatorio.')
  else if (!CURRENCIES.includes(form.currency))
    set('currency', `El campo moneda debe ser uno de: ${CURRENCIES.join(', ')}.`)

  set('budget', budgetError(form.budget))

  if (isEmpty(form.activity_id)) set('activity_id', 'El campo actividad es obligatorio.')

  for (const field of ['start_date', 'closing_date']) {
    if (isEmpty(form[field])) set(field, `El campo ${LABELS[field]} es obligatorio.`)
    else if (!isValidDate(form[field])) {
      set(field, `El campo ${LABELS[field]} debe ser una fecha válida con formato AAAA-MM-DD.`)
    }
  }

  for (const field of ['start_time', 'closing_time']) {
    if (isEmpty(form[field])) set(field, `El campo ${LABELS[field]} es obligatorio.`)
    else if (!isValidTime(form[field])) {
      set(field, `El campo ${LABELS[field]} debe ser una hora válida con formato HH:mm (24 horas).`)
    }
  }

  const scheduleIsComplete = ['start_date', 'start_time', 'closing_date', 'closing_time'].every((f) => !errors[f])
  if (scheduleIsComplete) {
    if (form.closing_date < form.start_date) {
      set('closing_date', 'La fecha de cierre no puede ser menor a la fecha de inicio.')
    } else if (form.closing_date === form.start_date && form.closing_time <= form.start_time) {
      set('closing_time', 'La hora de cierre debe ser mayor a la hora de inicio cuando ambas fechas son iguales.')
    }
  }

  return errors
}

/**
 * @typedef {Object} DocumentDraft
 * @property {string} title
 * @property {string} description
 * @property {File|{name: string, type: string, size: number}|null} file
 */

/**
 * Validates a document before it is added to the pending list or saved.
 *
 * @param {DocumentDraft} document
 * @param {{requireFile?: boolean}} [options] the file is optional when editing a saved document
 * @returns {Record<string, string>}
 */
export function validateDocument(document, { requireFile = true } = {}) {
  const errors = {}

  const title = textError(document.title, 'título', LIMITS.documentTitle)
  if (title) errors.title = title

  const description = textError(document.description, 'descripción', LIMITS.documentDescription)
  if (description) errors.description = description

  const file = document.file
  if (!file) {
    if (requireFile) errors.file = 'Debe seleccionar un archivo.'
  } else {
    const extension = file.name.split('.').pop()?.toLowerCase() ?? ''
    if (!DOCUMENT_EXTENSIONS.includes(extension) || !DOCUMENT_MIME_TYPES.includes(file.type)) {
      errors.file = 'Solo se permiten archivos PDF o ZIP.'
    } else if (file.size > LIMITS.documentMegabytes * 1024 * 1024) {
      errors.file = `El archivo no debe superar ${LIMITS.documentMegabytes} MB.`
    }
  }

  return errors
}
