import { http } from './http'

/**
 * @typedef {import('./auth').AuthUser} User
 */

/**
 * @returns {Promise<User[]>}
 */
export async function listUsers() {
  const { data } = await http.get('/users')
  return data.data
}

/**
 * @param {{name: string, email: string, password: string, role: string}} payload
 * @returns {Promise<User>}
 */
export async function createUser(payload) {
  const { data } = await http.post('/users', payload)
  return data.data
}

/**
 * Only the fields sent are changed; the password is kept when omitted.
 *
 * @param {number} id
 * @param {{name?: string, email?: string, password?: string, role?: string}} payload
 * @returns {Promise<User>}
 */
export async function updateUser(id, payload) {
  const { data } = await http.patch(`/users/${id}`, payload)
  return data.data
}

/**
 * @param {number} id
 * @returns {Promise<void>}
 */
export async function deleteUser(id) {
  await http.delete(`/users/${id}`)
}
