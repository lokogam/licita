import { http } from './http'

/**
 * @typedef {Object} AuthUser
 * @property {number} id
 * @property {string} name
 * @property {string} email
 * @property {{value: 'admin'|'editor'|'viewer', label: string}} role
 * @property {string[]} abilities
 * @property {string} created_at
 */

/**
 * @typedef {Object} Session
 * @property {string} access_token
 * @property {string} token_type
 * @property {number} expires_in seconds
 * @property {AuthUser} user
 */

/**
 * Opens a session. The API also sets the HttpOnly refresh cookie.
 *
 * @param {string} email
 * @param {string} password
 * @returns {Promise<Session>}
 */
export async function login(email, password) {
  const { data } = await http.post('/auth/login', { email, password })
  return data.data
}

/**
 * Exchanges the refresh cookie for a new access token (the cookie is rotated).
 *
 * @returns {Promise<Session>}
 */
export async function refreshSession() {
  const { data } = await http.post('/auth/refresh')
  return data.data
}

/**
 * Revokes the refresh token and clears the cookie.
 *
 * @returns {Promise<void>}
 */
export async function logout() {
  await http.post('/auth/logout')
}

/**
 * @returns {Promise<AuthUser>}
 */
export async function me() {
  const { data } = await http.get('/auth/me')
  return data.data
}
