import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { AxiosError } from 'axios'
import { http, isSessionEndpoint, setAuthHandlers } from '@/api/http'

/**
 * Fake transport: answers each request with the next queued status and records the token sent.
 *
 * @param {number[]} statuses
 */
function fakeAdapter(statuses) {
  const queue = [...statuses]
  const calls = []
  const adapter = vi.fn(async (config) => {
    calls.push({ url: config.url, authorization: config.headers.Authorization ?? null })
    const status = queue.shift() ?? 200
    const data = status === 200 ? { data: 'ok' } : { message: 'x' }
    const response = { status, statusText: '', data, headers: {}, config }
    if (status >= 400) throw new AxiosError('fail', 'ERR_BAD_REQUEST', config, null, response)

    return response
  })

  return { adapter, calls }
}

describe('http client', () => {
  let token
  let handlers
  const originalAdapter = http.defaults.adapter

  beforeEach(() => {
    token = 'expired'
    handlers = {
      getAccessToken: () => token,
      refresh: vi.fn(async () => {
        token = 'fresh'
      }),
      onSessionExpired: vi.fn(),
    }
    setAuthHandlers(handlers)
  })

  afterEach(() => {
    http.defaults.adapter = originalAdapter
  })

  it('sends cookies and the bearer token', async () => {
    const { adapter, calls } = fakeAdapter([200])
    http.defaults.adapter = adapter
    token = 'abc'

    await http.get('/tenders')

    expect(http.defaults.withCredentials).toBe(true)
    expect(calls[0].authorization).toBe('Bearer abc')
  })

  it('refreshes the token once and retries the request after a 401', async () => {
    const { adapter, calls } = fakeAdapter([401, 200])
    http.defaults.adapter = adapter

    const response = await http.get('/tenders')

    expect(response.data).toEqual({ data: 'ok' })
    expect(handlers.refresh).toHaveBeenCalledTimes(1)
    expect(calls.map((call) => call.authorization)).toEqual(['Bearer expired', 'Bearer fresh'])
    expect(handlers.onSessionExpired).not.toHaveBeenCalled()
  })

  it('retries only once when the repeated request is also rejected', async () => {
    const { adapter, calls } = fakeAdapter([401, 401])
    http.defaults.adapter = adapter

    await expect(http.get('/tenders')).rejects.toMatchObject({ status: 401 })
    expect(calls).toHaveLength(2)
    expect(handlers.refresh).toHaveBeenCalledTimes(1)
  })

  it('ends the session when the refresh is rejected', async () => {
    const { adapter, calls } = fakeAdapter([401])
    http.defaults.adapter = adapter
    handlers.refresh.mockRejectedValue({ status: 401, message: 'x', errors: {} })

    await expect(http.get('/tenders')).rejects.toMatchObject({ status: 401 })
    expect(calls).toHaveLength(1)
    expect(handlers.onSessionExpired).toHaveBeenCalledTimes(1)
  })

  it('keeps the session when the refresh fails because of the network', async () => {
    http.defaults.adapter = fakeAdapter([401]).adapter
    handlers.refresh.mockRejectedValue({ status: 0, message: 'offline', errors: {} })

    await expect(http.get('/tenders')).rejects.toMatchObject({ status: 401 })
    expect(handlers.onSessionExpired).not.toHaveBeenCalled()
  })

  it('does not refresh for the session endpoints or for other errors', async () => {
    http.defaults.adapter = fakeAdapter([401, 403]).adapter

    await expect(http.post('/auth/login', {})).rejects.toMatchObject({ status: 401 })
    await expect(http.get('/users')).rejects.toMatchObject({ status: 403 })
    expect(handlers.refresh).not.toHaveBeenCalled()
  })

  it('recognizes the session endpoints', () => {
    expect(isSessionEndpoint('/auth/login')).toBe(true)
    expect(isSessionEndpoint('/api/auth/refresh')).toBe(true)
    expect(isSessionEndpoint('/auth/logout')).toBe(true)
    expect(isSessionEndpoint('/auth/me')).toBe(false)
    expect(isSessionEndpoint('/tenders')).toBe(false)
  })
})
