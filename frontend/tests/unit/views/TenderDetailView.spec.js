import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import TenderDetailView from '@/views/TenderDetailView.vue'
import { getTender } from '@/api/tenders'

vi.mock('@/api/tenders', () => ({ getTender: vi.fn() }))

const tender = {
  id: 3,
  consecutive: 'O-0003-26',
  subject: 'Servicio de vigilancia',
  description: 'Seguridad privada para las sedes',
  currency: 'COP',
  budget: '250000000.00',
  activity: { id: 9, product_code: 92121504, product: 'Servicios de guardias', class_name: 'Guardias', family: 'Seguridad', segment: 'Servicios de defensa' },
  start_date: '2026-11-01',
  start_time: '08:00',
  closing_date: '2026-11-15',
  closing_time: '17:30',
  status: { value: 'scheduled', label: 'Programada' },
  documents: [
    {
      id: 1,
      title: 'Pliego',
      description: 'Pliego de condiciones',
      original_name: 'pliego.pdf',
      mime_type: 'application/pdf',
      size: 524288,
      created_at: '2026-10-22 10:15:00',
      download_url: '/api/tenders/3/documents/1/download',
    },
  ],
  created_at: '2026-10-22 10:00:00',
  updated_at: '2026-10-22 10:15:00',
}

async function mountView() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/licitaciones', name: 'tenders.index', component: { template: '<div />' } },
      { path: '/licitaciones/:id', name: 'tenders.show', component: TenderDetailView },
      { path: '/licitaciones/:id/editar', name: 'tenders.edit', component: { template: '<div />' } },
    ],
  })
  await router.push('/licitaciones/3')
  const wrapper = mount(TenderDetailView, { global: { plugins: [router] } })
  await flushPromises()

  return wrapper
}

describe('TenderDetailView', () => {
  beforeEach(() => getTender.mockReset().mockResolvedValue(tender))

  it('shows the header, status and an edit button', async () => {
    const wrapper = await mountView()

    expect(getTender).toHaveBeenCalledWith('3')
    expect(wrapper.text()).toContain('O-0003-26')
    expect(wrapper.text()).toContain('Servicio de vigilancia')
    expect(wrapper.text()).toContain('Programada')
    expect(wrapper.get('a[href="/licitaciones/3/editar"]').text()).toContain('Editar')
  })

  it('organizes the information in tabs', async () => {
    const wrapper = await mountView()

    const tabs = wrapper.findAll('[role="tab"]')
    expect(tabs.map((t) => t.text())).toEqual(['Información básica', 'Cronograma', 'Documentos 1'])
    expect(wrapper.text()).toContain('92121504')

    await tabs[1].trigger('click')
    expect(wrapper.text()).toContain('01/11/2026 08:00')
    expect(wrapper.text()).toContain('15/11/2026 17:30')

    await tabs[2].trigger('click')
    expect(wrapper.get('a[href="/api/tenders/3/documents/1/download"]').text()).toContain('pliego.pdf')
  })

  it('shows a not found message for unknown tenders', async () => {
    getTender.mockRejectedValue({ status: 404, message: 'La licitación solicitada no existe.', errors: {} })
    const wrapper = await mountView()

    expect(wrapper.text()).toContain('La licitación solicitada no existe.')
  })
})
