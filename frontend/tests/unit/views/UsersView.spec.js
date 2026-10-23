import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { computed } from 'vue'
import { createMemoryHistory, createRouter } from 'vue-router'
import UsersView from '@/views/UsersView.vue'
import { createUser, deleteUser, listUsers, updateUser } from '@/api/users'

vi.mock('@/api/users', () => ({ listUsers: vi.fn(), createUser: vi.fn(), updateUser: vi.fn(), deleteUser: vi.fn() }))
vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: computed(() => ({ id: 1, name: 'Admin Demo' })),
    can: () => true,
    refresh: vi.fn().mockResolvedValue({}),
  }),
}))

const admin = {
  id: 1,
  name: 'Admin Demo',
  email: 'admin@licita.test',
  role: { value: 'admin', label: 'Administrador' },
  abilities: [],
  created_at: '2026-10-20 08:00:00',
}
const viewer = {
  id: 3,
  name: 'Lector Demo',
  email: 'lector@licita.test',
  role: { value: 'viewer', label: 'Lector' },
  abilities: [],
  created_at: '2026-10-21 09:30:00',
}

async function mountView() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/usuarios', name: 'users.index', component: UsersView },
      { path: '/licitaciones', name: 'tenders.index', component: { template: '<div />' } },
    ],
  })
  await router.push('/usuarios')
  const wrapper = mount(UsersView, { global: { plugins: [router] }, attachTo: document.body })
  await flushPromises()

  return wrapper
}

const button = (wrapper, text) => wrapper.findAll('button').find((b) => b.text().includes(text))

describe('UsersView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    listUsers.mockResolvedValue([admin, viewer])
  })

  it('lists the users with their role and creation date', async () => {
    const wrapper = await mountView()

    expect(wrapper.findAll('th').map((th) => th.text())).toEqual(['Nombre', 'Correo', 'Rol', 'Creado', 'Acciones'])
    const rows = wrapper.findAll('tbody tr')
    expect(rows).toHaveLength(2)
    expect(rows[1].text()).toContain('lector@licita.test')
    expect(rows[1].text()).toContain('Lector')
    expect(rows[1].text()).toContain('21/10/2026')
    expect(rows[0].text()).toContain('Usted')
    wrapper.unmount()
  })

  it('creates a user after validating the form', async () => {
    const created = {
      ...viewer,
      id: 9,
      name: 'Nuevo Editor',
      email: 'nuevo@licita.test',
      role: { value: 'editor', label: 'Editor' },
    }
    createUser.mockResolvedValue(created)
    const wrapper = await mountView()

    await button(wrapper, 'Nuevo usuario').trigger('click')
    const save = wrapper.get('button[form="user-form"]')
    expect(save.attributes('disabled')).toBeDefined()

    await wrapper.get('#user-name').setValue('Nuevo Editor')
    await wrapper.get('#user-email').setValue('nuevo@licita.test')
    await wrapper.get('#user-role').setValue('editor')
    await wrapper.get('#user-password').setValue('corta')
    await wrapper.get('#user-password').trigger('blur')
    expect(wrapper.text()).toContain('El campo contraseña debe tener al menos 10 caracteres.')

    await wrapper.get('#user-password').setValue('Clave-Segura-2026')
    await wrapper.get('#user-form').trigger('submit')
    await flushPromises()

    expect(createUser).toHaveBeenCalledWith({
      name: 'Nuevo Editor',
      email: 'nuevo@licita.test',
      role: 'editor',
      password: 'Clave-Segura-2026',
    })
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    expect(wrapper.text()).toContain('nuevo@licita.test')
    wrapper.unmount()
  })

  it('shows API validation errors on the fields', async () => {
    createUser.mockRejectedValue({
      status: 422,
      message: 'Los datos enviados no son válidos.',
      errors: { email: 'Ya existe un usuario con ese correo.' },
    })
    const wrapper = await mountView()

    await button(wrapper, 'Nuevo usuario').trigger('click')
    await wrapper.get('#user-name').setValue('Duplicado')
    await wrapper.get('#user-email').setValue('lector@licita.test')
    await wrapper.get('#user-role').setValue('viewer')
    await wrapper.get('#user-password').setValue('Clave-Segura-2026')
    await wrapper.get('#user-form').trigger('submit')
    await flushPromises()

    expect(wrapper.get('#user-email-error').text()).toBe('Ya existe un usuario con ese correo.')
    wrapper.unmount()
  })

  it('edits a user without sending an empty password', async () => {
    updateUser.mockResolvedValue({ ...viewer, role: { value: 'editor', label: 'Editor' } })
    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Editar lector@licita.test"]').trigger('click')
    expect(wrapper.get('#user-name').element.value).toBe('Lector Demo')
    expect(wrapper.text()).toContain('Dejar en blanco para conservar la actual')

    await wrapper.get('#user-role').setValue('editor')
    await wrapper.get('#user-form').trigger('submit')
    await flushPromises()

    expect(updateUser).toHaveBeenCalledWith(3, { name: 'Lector Demo', email: 'lector@licita.test', role: 'editor' })
    wrapper.unmount()
  })

  it('does not allow deleting your own user', async () => {
    const wrapper = await mountView()

    const own = wrapper.get('button[aria-label="Eliminar admin@licita.test"]')
    expect(own.attributes('disabled')).toBeDefined()
    expect(own.attributes('title')).toBe('No puede eliminar su propio usuario.')
    expect(wrapper.get('button[aria-label="Eliminar lector@licita.test"]').attributes('disabled')).toBeUndefined()
    wrapper.unmount()
  })

  it('deletes another user after confirming', async () => {
    deleteUser.mockResolvedValue()
    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Eliminar lector@licita.test"]').trigger('click')
    expect(wrapper.get('[role="dialog"]').text()).toContain('Lector Demo')
    await wrapper.get('[role="dialog"] .btn-danger').trigger('click')
    await flushPromises()

    expect(deleteUser).toHaveBeenCalledWith(3)
    expect(wrapper.text()).not.toContain('lector@licita.test')
    wrapper.unmount()
  })

  it('shows the API message when the user cannot be deleted', async () => {
    deleteUser.mockRejectedValue({
      status: 422,
      message: 'Los datos enviados no son válidos.',
      errors: { user: 'No puede eliminar su propio usuario.' },
    })
    const wrapper = await mountView()

    await wrapper.get('button[aria-label="Eliminar lector@licita.test"]').trigger('click')
    await wrapper.get('[role="dialog"] .btn-danger').trigger('click')
    await flushPromises()

    expect(wrapper.get('[role="dialog"] .alert').text()).toBe('No puede eliminar su propio usuario.')
    wrapper.unmount()
  })
})
