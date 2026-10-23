import { describe, expect, it } from 'vitest'
import { useTenderForm } from '@/composables/useTenderForm'

const filled = {
  subject: 'Compra',
  description: 'Equipos',
  currency: 'COP',
  budget: '100',
  activity: { id: 7, product_code: 43211503, product: 'Portátiles' },
  start_date: '2026-11-01',
  start_time: '08:00',
  closing_date: '2026-11-02',
  closing_time: '08:00',
}

describe('useTenderForm', () => {
  it('starts empty and invalid, hiding errors until a field is touched', () => {
    const { isValid, visibleError, touch } = useTenderForm()

    expect(isValid.value).toBe(false)
    expect(visibleError('subject')).toBe('')

    touch('subject')
    expect(visibleError('subject')).toBe('El campo objeto es obligatorio.')
  })

  it('becomes valid when every field is correct', () => {
    const { form, isValid } = useTenderForm()
    Object.assign(form, filled)

    expect(isValid.value).toBe(true)
  })

  it('prefills from an existing tender', () => {
    const { form, isValid } = useTenderForm({
      ...filled,
      id: 1,
      consecutive: 'O-0001-26',
      activity: { id: 7, product_code: 43211503, product: 'Portátiles' },
    })

    expect(form.subject).toBe('Compra')
    expect(form.activity.id).toBe(7)
    expect(isValid.value).toBe(true)
  })

  it('builds the api payload with the activity id', () => {
    const { form, payload } = useTenderForm()
    Object.assign(form, filled)

    expect(payload()).toEqual({
      subject: 'Compra',
      description: 'Equipos',
      currency: 'COP',
      budget: '100',
      activity_id: 7,
      start_date: '2026-11-01',
      start_time: '08:00',
      closing_date: '2026-11-02',
      closing_time: '08:00',
    })
  })

  it('shows server errors until the field changes', async () => {
    const { form, visibleError, setServerErrors } = useTenderForm()
    Object.assign(form, filled)

    setServerErrors({ activity_id: 'La actividad seleccionada no existe.' })
    expect(visibleError('activity_id')).toBe('La actividad seleccionada no existe.')

    form.activity = { id: 8, product_code: 1, product: 'Otra' }
    await Promise.resolve()
    expect(visibleError('activity_id')).toBe('')
  })
})
