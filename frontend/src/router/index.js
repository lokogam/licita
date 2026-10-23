import { createRouter, createWebHistory } from 'vue-router'
import TenderListView from '@/views/TenderListView.vue'

const routes = [
  { path: '/', redirect: { name: 'tenders.index' } },
  { path: '/licitaciones', name: 'tenders.index', component: TenderListView, meta: { title: 'Licitaciones' } },
  {
    path: '/licitaciones/nueva',
    name: 'tenders.create',
    component: () => import('@/views/TenderCreateView.vue'),
    meta: { title: 'Nueva licitación' },
  },
  {
    path: '/licitaciones/:id(\\d+)',
    name: 'tenders.show',
    component: () => import('@/views/TenderDetailView.vue'),
    meta: { title: 'Detalle de licitación' },
  },
  {
    path: '/licitaciones/:id(\\d+)/editar',
    name: 'tenders.edit',
    component: () => import('@/views/TenderEditView.vue'),
    meta: { title: 'Editar licitación' },
  },
  { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/views/NotFoundView.vue') },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.afterEach((to) => {
  document.title = `${to.meta.title ?? 'Licitaciones'} | Suplos`
})
