import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import DocumentsSection from '@/components/tenders/DocumentsSection.vue'

const existing = [
  { id: 1, title: 'Pliego', description: 'Principal', original_name: 'pliego.pdf', size: 1024, download_url: '/api/x' },
]
const pendingDoc = {
  title: 'Anexo',
  description: 'Técnico',
  file: new File(['x'], 'anexo.zip', { type: 'application/zip' }),
}

describe('DocumentsSection', () => {
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
})
