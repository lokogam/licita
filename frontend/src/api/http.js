import axios from 'axios'

/**
 * @typedef {Object} ApiError
 * @property {number} status HTTP status (0 when the server could not be reached)
 * @property {string} message Human readable message (Spanish)
 * @property {Record<string, string>} errors First validation message per field
 */

/**
 * @typedef {Object} AuthHandlers
 * @property {() => string|null} getAccessToken current in-memory access token
 * @property {() => Promise<unknown>} refresh renews the access token with the refresh cookie
 * @property {() => void} onSessionExpired called when the session cannot be renewed
 */

/** Axios instance for the tenders API. The refresh cookie travels with `withCredentials`. */
export const http = axios.create({
  baseURL: '/api',
  headers: { Accept: 'application/json' },
  timeout: 60000,
  withCredentials: true,
})

/** Session endpoints never trigger a refresh-and-retry (a 401 there is a real answer). */
const SESSION_ENDPOINTS = ['/auth/login', '/auth/refresh', '/auth/logout']

/** @type {AuthHandlers} */
let authHandlers = {
  getAccessToken: () => null,
  refresh: () => Promise.reject(new Error('No session')),
  onSessionExpired: () => {},
}

/**
 * Connects the HTTP client with the session (kept in useAuth to avoid a circular import).
 *
 * @param {Partial<AuthHandlers>} handlers
 */
export function setAuthHandlers(handlers) {
  authHandlers = { ...authHandlers, ...handlers }
}

/**
 * @param {string|undefined} url
 * @returns {boolean}
 */
export function isSessionEndpoint(url) {
  const path = (url ?? '').split('?')[0].replace(/^\/api/, '')
  return SESSION_ENDPOINTS.includes(path)
}

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

http.interceptors.request.use((config) => {
  const token = authHandlers.getAccessToken()
  if (token) config.headers.Authorization = `Bearer ${token}`

  return config
})

/**
 * On a 401 from a protected endpoint the access token is renewed once and the request repeated once.
 * If the session cannot be renewed the user is sent back to the login screen.
 *
 * @param {any} error
 * @returns {Promise<any>}
 */
export async function handleResponseError(error) {
  const config = error?.config
  const unauthorized = error?.response?.status === 401

  if (unauthorized && config && !config._retried && !isSessionEndpoint(config.url)) {
    config._retried = true
    try {
      await authHandlers.refresh()
    } catch (refreshError) {
      if (refreshError?.status === 401 || refreshError?.response?.status === 401) authHandlers.onSessionExpired()
      throw toApiError(error)
    }

    return http.request(config)
  }

  throw toApiError(error)
}

http.interceptors.response.use((response) => response, handleResponseError)
