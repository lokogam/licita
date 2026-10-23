import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import TenderListView from '@/views/TenderListView.vue'
import { exportTenders, listTenders } from '@/api/tenders'

vi.mock('@/api/tenders', () => ({ listTenders: vi.fn(), exportTenders: vi.fn() }))
vi.mock('@/utils/download', () => ({ saveBlob: vi.fn() }))

const page = (data, meta = {}) => ({
  data,
  meta: { current_page: 1, per_page: 10, total: data.length, last_page: 1, from: 1, to: data.length, ...meta },
})

const row = {
  id: 5,
  consecutive: 'O-0005-26',
  subject: 'Compra de portátiles',
  description: 'Equipos para el área comercial',
  start_date: '2026-11-01',
  closing_date: '2026-11-15',
  status: { value: 'open', label: 'Abierta' },
}

async function mountView(query = {}) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/licitaciones', name: 'tenders.index', component: TenderListView },
      { path: '/licitaciones/nueva', name: 'tenders.create', component: { template: '<div />' } },
      { path: '/licitaciones/:id', name: 'tenders.show', component: { template: '<div />' } },
    ],
  })
  await router.push({ name: 'tenders.index', query })
  const wrapper = mount(TenderListView, { global: { plugins: [router] } })
  await flushPromises()

  return { wrapper, router }
}

describe('TenderListView', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    listTenders.mockReset()
    exportTenders.mockReset()
  })

  afterEach(() => vi.useRealTimers())

  it('lists tenders with the required columns and a detail action', async () => {
    listTenders.mockResolvedValue(page([row]))
    const { wrapper } = await mountView()

    const headers = wrapper.findAll('th').map((th) => th.text())
    expect(headers).toEqual([
      'Consecutivo',
      'Objeto',
      'Descripción',
      'Fecha de inicio',
      'Fecha de cierre',
      'Estado',
      'Acciones',
    ])
    expect(wrapper.text()).toContain('O-0005-26')
    expect(wrapper.text()).toContain('01/11/2026')
    expect(wrapper.text()).toContain('Abierta')
    expect(wrapper.get('a[aria-label="Ver detalle de O-0005-26"]').attributes('href')).toBe('/licitaciones/5')
  })

  it('applies debounced filters and keeps them in the URL', async () => {
    listTenders.mockResolvedValue(page([row]))
    const { wrapper, router } = await mountView()

    await wrapper.get('#filter-subject').setValue('portátiles')
    vi.advanceTimersByTime(350)
    await flushPromises()

    expect(listTenders).toHaveBeenLastCalledWith({ page: 1, per_page: 10, subject: 'portátiles' })
    expect(router.currentRoute.value.query.subject).toBe('portátiles')
  })

  it('filters by status immediately', async () => {
    listTenders.mockResolvedValue(page([row]))
    const { wrapper, router } = await mountView()

    await wrapper.get('#filter-status').setValue('open')
    await flushPromises()

    expect(listTenders).toHaveBeenLastCalledWith({ page: 1, per_page: 10, status: 'open' })
    expect(router.currentRoute.value.query.status).toBe('open')
    expect(wrapper.findAll('#filter-status option').map((o) => o.text())).toEqual([
      'Todos',
      'Programada',
      'Abierta',
      'Cerrada',
    ])
  })

  it('changes page with the pagination bar', async () => {
    listTenders.mockResolvedValue(page([row], { total: 25, last_page: 3, to: 10 }))
    const { wrapper } = await mountView()

    await wrapper.get('button[aria-label="Página 2"]').trigger('click')
    await flushPromises()

    expect(listTenders).toHaveBeenLastCalledWith({ page: 2, per_page: 10 })
    expect(wrapper.text()).toContain('Mostrando 1–10 de 25')
  })

  it('shows an empty state when nothing matches', async () => {
    listTenders.mockResolvedValue(page([]))
    const { wrapper } = await mountView({ subject: 'nada' })

    expect(wrapper.text()).toContain('No encontramos licitaciones')
  })

  it('exports the filtered list to Excel', async () => {
    listTenders.mockResolvedValue(page([row]))
    exportTenders.mockResolvedValue({ blob: new Blob(['x']), filename: 'licitaciones.xlsx' })
    const { wrapper } = await mountView({ subject: 'compra' })

    await wrapper
      .findAll('button')
      .find((b) => b.text().includes('Exportar Excel'))
      .trigger('click')
    await flushPromises()

    expect(exportTenders).toHaveBeenCalledWith({ subject: 'compra' })
  })
})
