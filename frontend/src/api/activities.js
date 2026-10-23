import { http } from './http'

/**
 * Autocomplete search. An empty term returns the first activities alphabetically.
 *
 * @param {string} term product name or code prefix
 * @param {number} [limit]
 * @returns {Promise<Array<{id: number, product_code: number, product: string, class_name: string}>>}
 */
export async function searchActivities(term, limit = 20) {
  const params = term ? { search: term, limit } : { limit }
  const { data } = await http.get('/activities', { params })
  return data.data
}
