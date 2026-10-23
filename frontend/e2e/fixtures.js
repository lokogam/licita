import { test as base, expect } from '@playwright/test'

/**
 * @typedef {{email: string, password: string}} Credentials
 */

/** Defaults are the local demo accounts; other environments pass their own through env vars. */
export const ADMIN = Object.freeze({
  email: process.env.E2E_EMAIL ?? 'admin@licita.test',
  password: process.env.E2E_PASSWORD ?? 'Admin-Demo-2026',
})

export const VIEWER = Object.freeze({
  email: process.env.E2E_VIEWER_EMAIL ?? 'lector@licita.test',
  password: process.env.E2E_VIEWER_PASSWORD ?? 'Lector-Demo-2026',
})

/**
 * Opens a session through the API. With a page's request context the refresh cookie is
 * stored in the browser context, so the SPA restores the session when it loads.
 *
 * @param {import('@playwright/test').APIRequestContext} request
 * @param {Credentials} credentials
 * @returns {Promise<string>} access token
 */
export async function apiLogin(request, credentials) {
  const response = await request.post('/api/auth/login', { data: credentials })
  expect(response.status(), `login de ${credentials.email}`).toBe(200)

  return (await response.json()).data.access_token
}

/**
 * Every test starts signed in (as admin unless `test.use({ credentials })` says otherwise;
 * `credentials: null` starts anonymous).
 *
 * A fresh session is opened per test instead of reusing a saved storageState: refresh tokens
 * are single use (rotated on every refresh, and replaying an old one revokes the whole session),
 * so one saved cookie cannot be shared between tests.
 */
export const test = base.extend({
  /** @type {[Credentials|null, {option: true}]} */
  credentials: [ADMIN, { option: true }],

  page: async ({ page, credentials }, use) => {
    if (credentials) await apiLogin(page.request, credentials)
    await use(page)
  },
})

export { expect }
