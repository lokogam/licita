import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import TenderForm from '@/components/tenders/TenderForm.vue'
import { searchActivities } from '@/api/activities'

vi.mock('@/api/activities', () => ({ searchActivities: vi.fn() }))

const activity = { id: 7, product_code: 43211503, product: 'Computadores portátiles' }

const existing = {
  id: 1,
  consecutive: 'O-0001-26',
  subject: 'Compra de portátiles',
  description: 'Equipos para el área comercial',
  currency: 'USD',
  budget: '1500.50',
  activity,
  start_date: '2026-11-01',
  start_time: '08:00',
  closing_date: '2026-11-15',
  closing_time: '17:30',
}

const saveButton = (wrapper) => wrapper.get('button[type="submit"]')

describe('TenderForm', () => {
  beforeEach(() => {
    searchActivities.mockReset().mockResolvedValue([activity])
  })

  it('renders the two sections and keeps "Guardar" disabled while invalid', async () => {
    const wrapper = mount(TenderForm, { props: { submitLabel: 'Guardar' } })
    await flushPromises()

    expect(wrapper.text()).toContain('Información básica')
    expect(wrapper.text()).toContain('Cronograma')
    expect(saveButton(wrapper).attributes('disabled')).toBeDefined()
  })

  it('prefills an existing tender and submits the payload', async () => {
    const wrapper = mount(TenderForm, { props: { initial: existing, submitLabel: 'Guardar' } })
    await flushPromises()

    expect(wrapper.get('#subject').element.value).toBe('Compra de portátiles')
    expect(wrapper.get('#currency').element.value).toBe('USD')
    expect(saveButton(wrapper).attributes('disabled')).toBeUndefined()

    await wrapper.get('form').trigger('submit')

    expect(wrapper.emitted('submit')[0][0]).toMatchObject({ subject: 'Compra de portátiles', activity_id: 7 })
  })

  it('shows character counters and blocks text beyond the limit', async () => {
    const wrapper = mount(TenderForm, { props: { submitLabel: 'Guardar' } })

    expect(wrapper.get('#subject').attributes('maxlength')).toBe('150')
    expect(wrapper.get('#description').attributes('maxlength')).toBe('400')

    await wrapper.get('#subject').setValue('Compra')
    expect(wrapper.text()).toContain('6/150')
  })

  it('strips non numeric characters from the budget', async () => {
    const wrapper = mount(TenderForm, { props: { submitLabel: 'Guardar' } })

    await wrapper.get('#budget').setValue('12a3,4.567')

    expect(wrapper.get('#budget').element.value).toBe('1234.56')
  })

  it('validates the schedule across fields', async () => {
    const wrapper = mount(TenderForm, {
      props: { initial: { ...existing, closing_date: '2026-11-01', closing_time: '07:00' }, submitLabel: 'Guardar' },
    })
    await flushPromises()
    await wrapper.get('#closing_time').trigger('blur')

    expect(wrapper.text()).toContain(
      'La hora de cierre debe ser mayor a la hora de inicio cuando ambas fechas son iguales.',
    )
    expect(saveButton(wrapper).attributes('disabled')).toBeDefined()
  })

  it('shows server side errors next to the field', async () => {
    const wrapper = mount(TenderForm, {
      props: {
        initial: existing,
        submitLabel: 'Guardar',
        serverErrors: { subject: 'El campo objeto no debe superar 150 caracteres.' },
      },
    })
    await flushPromises()

    expect(wrapper.get('#subject').classes()).toContain('is-invalid')
    expect(wrapper.text()).toContain('El campo objeto no debe superar 150 caracteres.')
  })

  it('stays disabled when an extra section is invalid', async () => {
    const wrapper = mount(TenderForm, { props: { initial: existing, submitLabel: 'Guardar', extraValid: false } })
    await flushPromises()

    expect(saveButton(wrapper).attributes('disabled')).toBeDefined()
  })
})
