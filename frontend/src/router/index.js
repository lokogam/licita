import { createRouter, createWebHistory } from 'vue-router'
import TenderListView from '@/views/TenderListView.vue'
import { onSessionExpired, useAuth } from '@/composables/useAuth'
import { useToasts } from '@/composables/useToasts'
import { checkAccess } from './guard'

const routes = [
  { path: '/', redirect: { name: 'tenders.index' } },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { title: 'Iniciar sesión', public: true, guestOnly: true, layout: 'blank' },
  },
  { path: '/licitaciones', name: 'tenders.index', component: TenderListView, meta: { title: 'Licitaciones' } },
  {
    path: '/licitaciones/nueva',
    name: 'tenders.create',
    component: () => import('@/views/TenderCreateView.vue'),
    meta: { title: 'Nueva licitación', ability: 'tenders.create' },
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
    meta: { title: 'Editar licitación', ability: 'tenders.update' },
  },
  {
    path: '/usuarios',
    name: 'users.index',
    component: () => import('@/views/UsersView.vue'),
    meta: { title: 'Usuarios', ability: 'users.manage' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { public: true },
  },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

const auth = useAuth()

router.beforeEach(async (to) => {
  // The access token is only in memory: after a reload the session comes back from the refresh cookie.
  await auth.restoreSession()

  const result = checkAccess(to, { isAuthenticated: auth.isAuthenticated.value, can: auth.can })
  if (result.allow) return true

  if (result.reason === 'forbidden') useToasts().notify('No tiene permisos para acceder a esa sección.', 'danger')

  return result.redirect
})

router.afterEach((to) => {
  document.title = `${to.meta.title ?? 'Licitaciones'} | Suplos`
})

onSessionExpired(() => {
  const current = router.currentRoute.value
  if (current.meta.public) return
  router.push({ name: 'login', query: { redirect: current.fullPath } })
})
