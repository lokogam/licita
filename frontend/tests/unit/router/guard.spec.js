import { describe, expect, it } from 'vitest'
import { checkAccess, safeRedirect } from '@/router/guard'

const route = (fullPath, meta = {}) => ({ fullPath, meta })
const anonymous = { isAuthenticated: false, can: () => false }
const viewer = { isAuthenticated: true, can: (ability) => ability === 'tenders.view' }
const admin = { isAuthenticated: true, can: () => true }

describe('checkAccess', () => {
  it('sends anonymous users to the login with the requested page', () => {
    expect(checkAccess(route('/licitaciones?page=2'), anonymous)).toEqual({
      allow: false,
      reason: 'unauthenticated',
      redirect: { name: 'login', query: { redirect: '/licitaciones?page=2' } },
    })
  })

  it('lets anyone open public pages', () => {
    expect(checkAccess(route('/login', { public: true, guestOnly: true }), anonymous)).toEqual({ allow: true })
    expect(checkAccess(route('/no-existe', { public: true }), anonymous)).toEqual({ allow: true })
    expect(checkAccess(route('/no-existe', { public: true }), viewer)).toEqual({ allow: true })
  })

  it('sends logged-in users away from the login', () => {
    expect(checkAccess(route('/login', { public: true, guestOnly: true }), viewer)).toMatchObject({
      allow: false,
      reason: 'guest',
      redirect: { name: 'tenders.index' },
    })
  })

  it('checks the ability required by the route', () => {
    const create = route('/licitaciones/nueva', { ability: 'tenders.create' })

    expect(checkAccess(create, viewer)).toMatchObject({
      allow: false,
      reason: 'forbidden',
      redirect: { name: 'tenders.index' },
    })
    expect(checkAccess(create, admin)).toEqual({ allow: true })
    expect(checkAccess(route('/licitaciones'), viewer)).toEqual({ allow: true })
  })
})

describe('safeRedirect', () => {
  it('accepts internal paths only', () => {
    expect(safeRedirect('/licitaciones/5')).toBe('/licitaciones/5')
    expect(safeRedirect('https://evil.test')).toBeNull()
    expect(safeRedirect('//evil.test')).toBeNull()
    expect(safeRedirect('/\\evil.test')).toBeNull()
    expect(safeRedirect('/login?redirect=/x')).toBeNull()
    expect(safeRedirect(undefined)).toBeNull()
    expect(safeRedirect(['/a'])).toBeNull()
  })
})
