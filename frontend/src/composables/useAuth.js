import { computed, reactive, readonly } from 'vue'
import * as authApi from '@/api/auth'
import { setAuthHandlers } from '@/api/http'
import { useToasts } from '@/composables/useToasts'

/**
 * @typedef {import('@/api/auth').AuthUser} AuthUser
 * @typedef {import('@/api/auth').Session} Session
 */

/**
 * Session shared by the whole app. The access token lives only in memory; after a reload
 * the session is restored with the HttpOnly refresh cookie (restoreSession).
 */
const state = reactive({
  /** @type {AuthUser|null} */
  user: null,
  /** @type {string|null} */
  accessToken: null,
})

/** @type {Promise<Session>|null} */
let refreshInFlight = null
/** @type {Promise<boolean>|null} */
let restoreInFlight = null
/** @type {Array<() => void>} */
const expiredListeners = []

const user = computed(() => state.user)
const isAuthenticated = computed(() => state.user !== null && state.accessToken !== null)

/** @param {Session} session */
function setSession(session) {
  state.user = session.user
  state.accessToken = session.access_token
}

function clearSession() {
  state.user = null
  state.accessToken = null
}

/**
 * @param {string} email
 * @param {string} password
 * @returns {Promise<AuthUser>}
 */
async function login(email, password) {
  setSession(await authApi.login(email.trim(), password))
  restoreInFlight = Promise.resolve(true)

  return state.user
}

/**
 * Renews the access token. Concurrent callers share the same request, so the
 * refresh cookie (single use) is only presented once.
 *
 * @returns {Promise<Session>}
 */
function refresh() {
  if (!refreshInFlight) {
    refreshInFlight = authApi
      .refreshSession()
      .then((session) => {
        setSession(session)
        return session
      })
      .finally(() => {
        refreshInFlight = null
      })
  }

  return refreshInFlight
}

/**
 * Closes the session in the API (revokes the refresh cookie) and in memory.
 * The local session is cleared even if the API call fails.
 *
 * @returns {Promise<void>}
 */
async function logout() {
  try {
    await authApi.logout()
  } catch {
    // The user is leaving anyway; an expired cookie is not an error here.
  } finally {
    clearSession()
  }
}

/**
 * Restores the session from the refresh cookie. Runs once (before the first navigation);
 * later calls reuse the same result. Without a valid cookie the user simply stays anonymous.
 *
 * @returns {Promise<boolean>} whether there is an active session
 */
function restoreSession() {
  if (!restoreInFlight) {
    restoreInFlight = refresh()
      .then(() => true)
      .catch(() => false)
  }

  return restoreInFlight
}

/**
 * @param {string} ability e.g. "tenders.create"
 * @returns {boolean}
 */
function can(ability) {
  return state.user?.abilities?.includes(ability) ?? false
}

/**
 * Registers a callback for when the session expires and cannot be renewed (used by the router).
 *
 * @param {() => void} listener
 */
export function onSessionExpired(listener) {
  expiredListeners.push(listener)
}

/** Called by the HTTP client when a protected request fails and the refresh is rejected. */
function expireSession() {
  if (state.user === null) return
  clearSession()
  useToasts().notify('Su sesión expiró. Inicie sesión nuevamente.', 'danger')
  expiredListeners.forEach((listener) => listener())
}

setAuthHandlers({
  getAccessToken: () => state.accessToken,
  refresh,
  onSessionExpired: expireSession,
})

/**
 * Test helper: forgets the session and any cached restore.
 */
export function resetAuthState() {
  clearSession()
  refreshInFlight = null
  restoreInFlight = null
}

/**
 * Authentication state and actions.
 */
export function useAuth() {
  return {
    state: readonly(state),
    user,
    isAuthenticated,
    login,
    logout,
    refresh,
    restoreSession,
    can,
  }
}
