import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import LoginView from '@/views/LoginView.vue'

const auth = vi.hoisted(() => ({ login: null }))
vi.mock('@/composables/useAuth', () => ({ useAuth: () => ({ login: auth.login }) }))

async function mountView(query = {}) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/login', name: 'login', component: LoginView },
      { path: '/licitaciones', name: 'tenders.index', component: { template: '<div />' } },
      { path: '/licitaciones/:id', name: 'tenders.show', component: { template: '<div />' } },
    ],
  })
  await router.push({ name: 'login', query })
  const wrapper = mount(LoginView, { global: { plugins: [router] } })

  return { wrapper, router }
}

async function fill(wrapper, email, password) {
  await wrapper.get('#login-email').setValue(email)
  await wrapper.get('#login-password').setValue(password)
  await wrapper.get('form').trigger('submit')
  await flushPromises()
}

describe('LoginView', () => {
  beforeEach(() => {
    auth.login = vi.fn().mockResolvedValue({ id: 1 })
  })

  afterEach(() => vi.unstubAllEnvs())

  it('validates the fields before calling the API', async () => {
    const { wrapper } = await mountView()

    await fill(wrapper, 'correo-invalido', '')

    expect(auth.login).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('El campo correo debe ser un correo electrónico válido.')
    expect(wrapper.text()).toContain('El campo contraseña es obligatorio.')
    expect(wrapper.get('#login-email').attributes('aria-invalid')).toBe('true')
  })

  it('logs in and goes to the requested page', async () => {
    const { wrapper, router } = await mountView({ redirect: '/licitaciones/7' })

    await fill(wrapper, 'admin@suplos.test', 'Admin-Demo-2026')

    expect(auth.login).toHaveBeenCalledWith('admin@suplos.test', 'Admin-Demo-2026')
    expect(router.currentRoute.value.fullPath).toBe('/licitaciones/7')
  })

  it('ignores external redirects and goes to the list', async () => {
    const { wrapper, router } = await mountView({ redirect: 'https://evil.test' })

    await fill(wrapper, 'admin@suplos.test', 'Admin-Demo-2026')

    expect(router.currentRoute.value.name).toBe('tenders.index')
  })

  it('shows the API message for wrong credentials and clears the password', async () => {
    auth.login.mockRejectedValue({ status: 401, message: 'Correo o contraseña incorrectos.', errors: {} })
    const { wrapper, router } = await mountView()

    await fill(wrapper, 'admin@suplos.test', 'otra-clave')

    const alert = wrapper.get('[data-test="login-alert"]')
    expect(alert.text()).toBe('Correo o contraseña incorrectos.')
    expect(alert.classes()).toContain('alert-danger')
    expect(wrapper.get('#login-password').element.value).toBe('')
    expect(router.currentRoute.value.name).toBe('login')
  })

  it('shows the lockout message when there are too many attempts', async () => {
    auth.login.mockRejectedValue({
      status: 429,
      message: 'Demasiados intentos fallidos. Intente de nuevo en 15 minutos.',
      errors: {},
    })
    const { wrapper } = await mountView()

    await fill(wrapper, 'admin@suplos.test', 'otra-clave')

    const alert = wrapper.get('[data-test="login-alert"]')
    expect(alert.text()).toContain('Demasiados intentos fallidos')
    expect(alert.classes()).toContain('alert-warning')
  })

  it('maps validation errors from the API to the fields', async () => {
    auth.login.mockRejectedValue({
      status: 422,
      message: 'Los datos enviados no son válidos.',
      errors: { email: 'El campo correo no debe superar 150 caracteres.' },
    })
    const { wrapper } = await mountView()

    await fill(wrapper, 'admin@suplos.test', 'x')

    expect(wrapper.get('#login-email-error').text()).toBe('El campo correo no debe superar 150 caracteres.')
  })

  it('toggles the password visibility', async () => {
    const { wrapper } = await mountView()
    const toggle = wrapper.get('button[aria-label="Mostrar contraseña"]')

    expect(wrapper.get('#login-password').attributes('type')).toBe('password')
    await toggle.trigger('click')
    expect(wrapper.get('#login-password').attributes('type')).toBe('text')
    expect(wrapper.find('button[aria-label="Ocultar contraseña"]').exists()).toBe(true)
  })

  it('shows a loading state while signing in', async () => {
    let resolve
    auth.login.mockReturnValue(new Promise((r) => (resolve = r)))
    const { wrapper } = await mountView()

    await wrapper.get('#login-email').setValue('admin@suplos.test')
    await wrapper.get('#login-password').setValue('Admin-Demo-2026')
    await wrapper.get('form').trigger('submit')

    const button = wrapper.get('button[type="submit"]')
    expect(button.attributes('disabled')).toBeDefined()
    expect(button.text()).toContain('Ingresando')
    resolve({ id: 1 })
    await flushPromises()
  })

  it('does not list the demo users by default', async () => {
    const { wrapper } = await mountView()

    expect(wrapper.find('[data-test="demo-users"]').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('admin@suplos.test')
  })

  it('lists the demo users when enabled and fills the form with one', async () => {
    vi.stubEnv('VITE_SHOW_DEMO_USERS', 'true')
    const { wrapper } = await mountView()

    await wrapper.get('.demo-users-toggle').trigger('click')
    await wrapper.get('button[aria-label="Usar cuenta Lector"]').trigger('click')

    expect(wrapper.get('#login-email').element.value).toBe('lector@suplos.test')
    expect(wrapper.get('#login-password').element.value).toBe('Lector-Demo-2026')
  })
})
