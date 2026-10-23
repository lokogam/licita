import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import DocumentsSection from '@/components/tenders/DocumentsSection.vue'

vi.mock('@/api/tenders', () => ({ downloadDocument: vi.fn() }))
const auth = vi.hoisted(() => ({ abilities: [] }))
vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({ can: (ability) => auth.abilities.includes(ability) }),
}))

const ADMIN = ['tenders.view', 'tenders.create', 'tenders.update', 'documents.manage', 'users.manage']

const existing = [
  { id: 1, title: 'Pliego', description: 'Principal', original_name: 'pliego.pdf', size: 1024, download_url: '/api/x' },
]
const pendingDoc = {
  title: 'Anexo',
  description: 'Técnico',
  file: new File(['x'], 'anexo.zip', { type: 'application/zip' }),
}

describe('DocumentsSection', () => {
  beforeEach(() => {
    auth.abilities = ADMIN
  })

  it('requires at least one document', () => {
    const wrapper = mount(DocumentsSection, { props: { existing: [], modelValue: [] } })

    expect(wrapper.text()).toContain('Debe registrar al menos 1 documento.')
  })

  it('lists existing and pending documents', () => {
    const wrapper = mount(DocumentsSection, { props: { existing, modelValue: [pendingDoc] } })

    expect(wrapper.text()).toContain('pliego.pdf')
    expect(wrapper.text()).toContain('anexo.zip')
    expect(wrapper.text()).toContain('Pendiente')
    expect(wrapper.text()).not.toContain('Debe registrar al menos 1 documento.')
  })

  it('opens the modal and appends the added document', async () => {
    const wrapper = mount(DocumentsSection, { props: { existing: [], modelValue: [] } })

    await wrapper
      .findAll('button')
      .find((b) => b.text().includes('Agregar documento'))
      .trigger('click')
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)

    wrapper.findComponent({ name: 'DocumentModal' }).vm.$emit('add', pendingDoc)
    await wrapper.vm.$nextTick()

    expect(wrapper.emitted('update:modelValue')[0][0]).toEqual([pendingDoc])
  })

  it('removes a pending document', async () => {
    const wrapper = mount(DocumentsSection, { props: { existing, modelValue: [pendingDoc] } })

    await wrapper.get('button[aria-label="Quitar anexo.zip"]').trigger('click')

    expect(wrapper.emitted('update:modelValue')[0][0]).toEqual([])
  })

  it('asks the parent to edit or delete saved documents', async () => {
    const two = [...existing, { ...existing[0], id: 2, original_name: 'anexo.zip' }]
    const wrapper = mount(DocumentsSection, { props: { existing: two, modelValue: [] } })

    await wrapper.get('button[aria-label="Editar pliego.pdf"]').trigger('click')
    await wrapper.get('button[aria-label="Eliminar anexo.zip"]').trigger('click')

    expect(wrapper.emitted('edit-existing')[0][0].id).toBe(1)
    expect(wrapper.emitted('delete-existing')[0][0].id).toBe(2)
  })

  it('does not allow deleting the only saved document', () => {
    const wrapper = mount(DocumentsSection, { props: { existing, modelValue: [] } })

    expect(wrapper.get('button[aria-label="Eliminar pliego.pdf"]').attributes('disabled')).toBeDefined()
  })

  it('hides edit and delete of saved documents without documents.manage', () => {
    auth.abilities = ['tenders.view', 'tenders.update']
    const wrapper = mount(DocumentsSection, { props: { existing, modelValue: [] } })

    expect(wrapper.text()).toContain('pliego.pdf')
    expect(wrapper.find('button[aria-label="Editar pliego.pdf"]').exists()).toBe(false)
    expect(wrapper.find('button[aria-label="Eliminar pliego.pdf"]').exists()).toBe(false)
  })
})
