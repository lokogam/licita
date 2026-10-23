import axios from 'axios'

/**
 * @typedef {Object} ApiError
 * @property {number} status HTTP status (0 when the server could not be reached)
 * @property {string} message Human readable message (Spanish)
 * @property {Record<string, string>} errors First validation message per field
 */

/** Axios instance for the tenders API. */
export const http = axios.create({
  baseURL: '/api',
  headers: { Accept: 'application/json' },
  timeout: 60000,
})

/**
 * Normalizes any Axios error into an ApiError.
 *
 * @param {any} error
 * @returns {ApiError}
 */
export function toApiError(error) {
  if (error?.response) {
    const data = error.response.data ?? {}
    const errors = Object.fromEntries(
      Object.entries(data.errors ?? {}).map(([field, messages]) => [field, [].concat(messages)[0]]),
    )

    return {
      status: error.response.status,
      message: data.message ?? 'Ocurrió un error inesperado. Intente nuevamente.',
      errors,
    }
  }

  if (error?.request) {
    return { status: 0, message: 'No fue posible conectar con el servidor. Verifique su conexión.', errors: {} }
  }

  return { status: 0, message: error?.message ?? 'Ocurrió un error inesperado.', errors: {} }
}

http.interceptors.response.use(
  (response) => response,
  (error) => Promise.reject(toApiError(error)),
)
