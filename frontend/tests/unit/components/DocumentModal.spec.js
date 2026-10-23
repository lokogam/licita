import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import DocumentModal from '@/components/tenders/DocumentModal.vue'

function selectFile(wrapper, file) {
  const input = wrapper.get('input[type="file"]')
  Object.defineProperty(input.element, 'files', { value: [file], configurable: true })
  return input.trigger('change')
}

const addButton = (wrapper) => wrapper.findAll('button').find((b) => b.text() === 'Agregar')

describe('DocumentModal', () => {
  it('enables "Agregar" only when title, description and a PDF/ZIP file are valid', async () => {
    const wrapper = mount(DocumentModal, { props: { show: true } })

    expect(addButton(wrapper).attributes('disabled')).toBeDefined()

    await wrapper.get('#document-title').setValue('Pliego')
    await wrapper.get('#document-description').setValue('Pliego de condiciones')
    await selectFile(wrapper, new File(['%PDF'], 'pliego.pdf', { type: 'application/pdf' }))

    expect(addButton(wrapper).attributes('disabled')).toBeUndefined()

    await addButton(wrapper).trigger('click')
    const [document] = wrapper.emitted('add')[0]
    expect(document.title).toBe('Pliego')
    expect(document.file.name).toBe('pliego.pdf')
  })

  it('rejects files that are not PDF or ZIP', async () => {
    const wrapper = mount(DocumentModal, { props: { show: true } })
    await wrapper.get('#document-title').setValue('Imagen')
    await wrapper.get('#document-description').setValue('Foto')
    await selectFile(wrapper, new File(['x'], 'foto.png', { type: 'image/png' }))

    expect(wrapper.text()).toContain('Solo se permiten archivos PDF o ZIP.')
    expect(addButton(wrapper).attributes('disabled')).toBeDefined()
  })

  it('shows character counters and limits typing', async () => {
    const wrapper = mount(DocumentModal, { props: { show: true } })

    expect(wrapper.get('#document-title').attributes('maxlength')).toBe('100')
    expect(wrapper.get('#document-description').attributes('maxlength')).toBe('200')

    await wrapper.get('#document-title').setValue('Pliego')
    expect(wrapper.text()).toContain('6/100')
  })

  it('emits close when cancelled', async () => {
    const wrapper = mount(DocumentModal, { props: { show: true } })

    await wrapper.findAll('button').find((b) => b.text() === 'Cancelar').trigger('click')

    expect(wrapper.emitted('close')).toHaveLength(1)
  })
})
