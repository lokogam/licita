/**
 * Access rules for every navigation, kept free of router/Vue dependencies so they can be unit tested.
 *
 * Routes require a session unless `meta.public`; `meta.guestOnly` routes (login) are only for
 * anonymous users; `meta.ability` is the permission the route needs.
 */

/**
 * @typedef {Object} GuardRoute
 * @property {string} fullPath
 * @property {{public?: boolean, guestOnly?: boolean, ability?: string}} meta
 */

/**
 * @typedef {Object} GuardAuth
 * @property {boolean} isAuthenticated
 * @property {(ability: string) => boolean} can
 */

/**
 * @typedef {{allow: true} | {allow: false, reason: 'unauthenticated'|'forbidden'|'guest', redirect: object}} GuardResult
 */

/** Where users land after login and when a section is not allowed. */
export const HOME_ROUTE = Object.freeze({ name: 'tenders.index' })

/**
 * @param {GuardRoute} to
 * @param {GuardAuth} auth
 * @returns {GuardResult}
 */
export function checkAccess(to, auth) {
  const meta = to.meta ?? {}

  if (meta.public) {
    if (meta.guestOnly && auth.isAuthenticated) return { allow: false, reason: 'guest', redirect: { ...HOME_ROUTE } }
    return { allow: true }
  }

  if (!auth.isAuthenticated) {
    return { allow: false, reason: 'unauthenticated', redirect: { name: 'login', query: { redirect: to.fullPath } } }
  }

  if (meta.ability && !auth.can(meta.ability)) {
    return { allow: false, reason: 'forbidden', redirect: { ...HOME_ROUTE } }
  }

  return { allow: true }
}

/**
 * Only internal paths are accepted as the post-login destination (prevents open redirects).
 *
 * @param {unknown} redirect value of the ?redirect query param
 * @returns {string|null}
 */
export function safeRedirect(redirect) {
  if (typeof redirect !== 'string') return null
  if (!redirect.startsWith('/') || redirect.startsWith('//') || redirect.startsWith('/\\')) return null
  if (redirect === '/login' || redirect.startsWith('/login?')) return null

  return redirect
}
