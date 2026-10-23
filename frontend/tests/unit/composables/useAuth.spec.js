import { beforeEach, describe, expect, it, vi } from 'vitest'
import { AxiosError } from 'axios'
import * as authApi from '@/api/auth'
import { http } from '@/api/http'
import { onSessionExpired, resetAuthState, useAuth } from '@/composables/useAuth'
import { useToasts } from '@/composables/useToasts'

vi.mock('@/api/auth', () => ({ login: vi.fn(), refreshSession: vi.fn(), logout: vi.fn() }))

const editor = {
  id: 2,
  name: 'Editor Demo',
  email: 'editor@suplos.test',
  role: { value: 'editor', label: 'Editor' },
  abilities: ['tenders.view', 'tenders.create', 'tenders.update', 'documents.manage'],
  created_at: '2026-10-20 08:00:00',
}

const session = (overrides = {}) => ({
  access_token: 'token-1',
  token_type: 'Bearer',
  expires_in: 900,
  user: editor,
  ...overrides,
})

describe('useAuth', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    resetAuthState()
  })

  it('logs in and keeps the user and token in memory only', async () => {
    authApi.login.mockResolvedValue(session())
    const auth = useAuth()

    const user = await auth.login(' editor@suplos.test ', 'Editor-Demo-2026')

    expect(authApi.login).toHaveBeenCalledWith('editor@suplos.test', 'Editor-Demo-2026')
    expect(user.name).toBe('Editor Demo')
    expect(auth.isAuthenticated.value).toBe(true)
    expect(auth.state.accessToken).toBe('token-1')
    expect(localStorage.length).toBe(0)
    expect(sessionStorage.length).toBe(0)
  })

  it('stays anonymous when the login fails', async () => {
    authApi.login.mockRejectedValue({ status: 401, message: 'Correo o contraseña incorrectos.', errors: {} })
    const auth = useAuth()

    await expect(auth.login('a@b.co', 'bad')).rejects.toMatchObject({ status: 401 })
    expect(auth.isAuthenticated.value).toBe(false)
  })

  it('checks the abilities of the current role', async () => {
    const auth = useAuth()
    expect(auth.can('tenders.view')).toBe(false)

    authApi.login.mockResolvedValue(session())
    await auth.login('editor@suplos.test', 'x')

    expect(auth.can('tenders.create')).toBe(true)
    expect(auth.can('documents.manage')).toBe(true)
    expect(auth.can('users.manage')).toBe(false)
  })

  it('shares a single refresh request between concurrent callers', async () => {
    let resolve
    authApi.refreshSession.mockReturnValue(new Promise((r) => (resolve = r)))
    const auth = useAuth()

    const calls = [auth.refresh(), auth.refresh(), auth.refresh()]
    resolve(session({ access_token: 'token-2' }))
    await Promise.all(calls)

    expect(authApi.refreshSession).toHaveBeenCalledTimes(1)
    expect(auth.state.accessToken).toBe('token-2')

    // Once finished, a later refresh makes a new request.
    authApi.refreshSession.mockResolvedValue(session({ access_token: 'token-3' }))
    await auth.refresh()
    expect(authApi.refreshSession).toHaveBeenCalledTimes(2)
    expect(auth.state.accessToken).toBe('token-3')
  })

  it('restores the session from the refresh cookie only once', async () => {
    authApi.refreshSession.mockResolvedValue(session())
    const auth = useAuth()

    expect(await auth.restoreSession()).toBe(true)
    expect(await auth.restoreSession()).toBe(true)

    expect(authApi.refreshSession).toHaveBeenCalledTimes(1)
    expect(auth.user.value.email).toBe('editor@suplos.test')
  })

  it('ignores a 401 while restoring (no cookie means no session)', async () => {
    authApi.refreshSession.mockRejectedValue({ status: 401, message: 'x', errors: {} })
    const auth = useAuth()

    expect(await auth.restoreSession()).toBe(false)
    expect(auth.isAuthenticated.value).toBe(false)
  })

  it('logs out locally even if the API call fails', async () => {
    authApi.login.mockResolvedValue(session())
    authApi.logout.mockRejectedValue({ status: 0, message: 'offline', errors: {} })
    const auth = useAuth()
    await auth.login('editor@suplos.test', 'x')

    await auth.logout()

    expect(authApi.logout).toHaveBeenCalledTimes(1)
    expect(auth.isAuthenticated.value).toBe(false)
    expect(auth.user.value).toBeNull()
    expect(auth.can('tenders.view')).toBe(false)
  })

  it('expires the session when a request fails and the refresh is rejected', async () => {
    authApi.login.mockResolvedValue(session())
    authApi.refreshSession.mockRejectedValue({ status: 401, message: 'x', errors: {} })
    const listener = vi.fn()
    onSessionExpired(listener)
    const auth = useAuth()
    await auth.login('editor@suplos.test', 'x')

    const originalAdapter = http.defaults.adapter
    http.defaults.adapter = async (config) => {
      const response = { status: 401, statusText: '', data: { message: 'expired' }, headers: {}, config }
      throw new AxiosError('fail', 'ERR_BAD_REQUEST', config, null, response)
    }
    try {
      await expect(http.get('/tenders')).rejects.toMatchObject({ status: 401 })
    } finally {
      http.defaults.adapter = originalAdapter
    }

    expect(auth.isAuthenticated.value).toBe(false)
    expect(listener).toHaveBeenCalledTimes(1)
    expect(useToasts().toasts.map((toast) => toast.message)).toContain('Su sesión expiró. Inicie sesión nuevamente.')
  })
})
