import { http } from './http'

/**
 * @typedef {Object} TenderFilters
 * @property {string} [consecutive]
 * @property {string} [subject]
 * @property {string} [description]
 */

/**
 * @param {TenderFilters & {page?: number, per_page?: number}} params
 * @returns {Promise<{data: object[], meta: object}>}
 */
export async function listTenders(params) {
  const { data } = await http.get('/tenders', { params })
  return data
}

/**
 * @param {number|string} id
 * @returns {Promise<object>} tender detail
 */
export async function getTender(id) {
  const { data } = await http.get(`/tenders/${id}`)
  return data.data
}

/**
 * @param {object} payload validated form payload
 * @returns {Promise<object>} created tender
 */
export async function createTender(payload) {
  const { data } = await http.post('/tenders', payload)
  return data.data
}

/**
 * Sends the edited fields and the new documents in a single multipart request.
 *
 * @param {number|string} id
 * @param {object} payload
 * @param {Array<{title: string, description: string, file: File}>} documents
 * @returns {Promise<object>} updated tender
 */
export async function updateTender(id, payload, documents) {
  const { data } = await http.post(`/tenders/${id}`, buildUpdateFormData(payload, documents))
  return data.data
}

/**
 * Downloads the Excel report for the current filters (no pagination).
 *
 * @param {TenderFilters} filters
 * @returns {Promise<{blob: Blob, filename: string}>}
 */
export async function exportTenders(filters) {
  const response = await http.get('/tenders/export', { params: filters, responseType: 'blob' })

  return {
    blob: response.data,
    filename: filenameFromDisposition(response.headers['content-disposition'], 'licitaciones.xlsx'),
  }
}

/**
 * @param {object} payload
 * @param {Array<{title: string, description: string, file: File}>} documents
 * @returns {FormData}
 */
export function buildUpdateFormData(payload, documents) {
  const form = new FormData()
  form.append('_method', 'PUT')

  for (const [key, value] of Object.entries(payload)) {
    form.append(key, value === null || value === undefined ? '' : String(value))
  }

  documents.forEach((document, index) => {
    form.append(`documents[${index}][title]`, document.title)
    form.append(`documents[${index}][description]`, document.description)
    form.append(`documents[${index}][file]`, document.file, document.file.name)
  })

  return form
}

/**
 * Extracts the file name from a Content-Disposition header (UTF-8 name preferred).
 *
 * @param {string|undefined} header
 * @param {string} [fallback]
 * @returns {string}
 */
export function filenameFromDisposition(header, fallback = 'archivo') {
  if (!header) return fallback

  const utf8 = /filename\*=UTF-8''([^;]+)/i.exec(header)
  if (utf8) return decodeURIComponent(utf8[1])

  const ascii = /filename="([^"]+)"/i.exec(header)

  return ascii ? ascii[1] : fallback
}
