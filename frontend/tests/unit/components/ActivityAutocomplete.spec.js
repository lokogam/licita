import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import ActivityAutocomplete from '@/components/tenders/ActivityAutocomplete.vue'
import { searchActivities } from '@/api/activities'

vi.mock('@/api/activities', () => ({ searchActivities: vi.fn() }))

const laptops = { id: 1, product_code: 43211503, product: 'Computadores portátiles', class_name: 'Computadores' }
const pens = { id: 2, product_code: 44121701, product: 'Bolígrafos', class_name: 'Instrumentos de escritura' }

describe('ActivityAutocomplete', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    searchActivities.mockReset()
  })

  afterEach(() => vi.useRealTimers())

  it('is disabled until the initial activities are loaded', async () => {
    let resolve
    searchActivities.mockReturnValueOnce(new Promise((r) => (resolve = r)))
    const wrapper = mount(ActivityAutocomplete, { props: { modelValue: null, inputId: 'activity' } })

    const input = wrapper.get('input')
    expect(input.attributes('disabled')).toBeDefined()
    expect(input.attributes('placeholder')).toBe('Cargando actividades…')

    resolve([laptops, pens])
    await flushPromises()

    expect(input.attributes('disabled')).toBeUndefined()
    expect(searchActivities).toHaveBeenCalledWith('')
  })

  it('searches with debounce and emits the selected activity', async () => {
    searchActivities.mockResolvedValueOnce([laptops, pens]).mockResolvedValueOnce([laptops])
    const wrapper = mount(ActivityAutocomplete, { props: { modelValue: null, inputId: 'activity' } })
    await flushPromises()

    const input = wrapper.get('input')
    await input.setValue('compu')
    expect(searchActivities).toHaveBeenCalledTimes(1)

    vi.advanceTimersByTime(300)
    await flushPromises()
    expect(searchActivities).toHaveBeenLastCalledWith('compu')

    const options = wrapper.findAll('[role="option"]')
    expect(options).toHaveLength(1)
    await options[0].trigger('mousedown')

    expect(wrapper.emitted('update:modelValue')[0][0]).toEqual(laptops)
  })

  it('supports keyboard selection', async () => {
    searchActivities.mockResolvedValueOnce([laptops, pens])
    const wrapper = mount(ActivityAutocomplete, { props: { modelValue: null, inputId: 'activity' } })
    await flushPromises()

    const input = wrapper.get('input')
    await input.trigger('focus')
    await input.trigger('keydown', { key: 'ArrowDown' })
    await input.trigger('keydown', { key: 'ArrowDown' })
    await input.trigger('keydown', { key: 'Enter' })

    expect(wrapper.emitted('update:modelValue')[0][0]).toEqual(pens)
  })

  it('shows the selected activity and clears the value when the text changes', async () => {
    searchActivities.mockResolvedValue([laptops])
    const wrapper = mount(ActivityAutocomplete, { props: { modelValue: laptops, inputId: 'activity' } })
    await flushPromises()

    expect(wrapper.get('input').element.value).toBe('43211503 · Computadores portátiles')

    await wrapper.get('input').setValue('otra cosa')
    expect(wrapper.emitted('update:modelValue')[0][0]).toBeNull()
  })
})
