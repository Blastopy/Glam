import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  { path: '/login', name: 'login', component: () => import('@/views/auth/LoginView.vue'), meta: { guestOnly: true } },
  { path: '/register', name: 'register', component: () => import('@/views/auth/RegisterView.vue'), meta: { guestOnly: true } },

  // ── Client-facing ────────────────────────────────
  {
    path: '/',
    name: 'discover',
    component: () => import('@/views/client/DiscoverView.vue'),
    meta: { requiresAuth: true, role: 'client' },
  },
  {
    path: '/providers/:id',
    name: 'provider-profile',
    component: () => import('@/views/client/ProviderProfileView.vue'),
    meta: { requiresAuth: true, role: 'client' },
  },
  {
    path: '/my-appointments',
    name: 'my-appointments',
    component: () => import('@/views/client/MyAppointmentsView.vue'),
    meta: { requiresAuth: true, role: 'client' },
  },
  {
    path: '/messages/:userId?',
    name: 'messages',
    component: () => import('@/views/client/MessagesView.vue'),
    meta: { requiresAuth: true },
  },

  // ── Provider-facing ──────────────────────────────
  {
    path: '/dashboard',
    name: 'provider-dashboard',
    component: () => import('@/views/provider/DashboardView.vue'),
    meta: { requiresAuth: true, role: 'provider' },
  },
  {
    path: '/dashboard/catalogue',
    name: 'provider-catalogue',
    component: () => import('@/views/provider/CatalogueView.vue'),
    meta: { requiresAuth: true, role: 'provider' },
  },
  {
    path: '/dashboard/clients',
    name: 'provider-clients',
    component: () => import('@/views/provider/ClientsView.vue'),
    meta: { requiresAuth: true, role: 'provider' },
  },
  {
    path: '/dashboard/profile',
    name: 'provider-profile-edit',
    component: () => import('@/views/provider/ProfileEditView.vue'),
    meta: { requiresAuth: true, role: 'provider' },
  },

  // ── Admin-facing ──────────────────────────────────
  {
    path: '/admin',
    name: 'admin-dashboard',
    component: () => import('@/views/admin/DashboardView.vue'),
    meta: { requiresAuth: true, role: 'admin' },
  },
  {
    path: '/admin/users',
    name: 'admin-users',
    component: () => import('@/views/admin/UsersView.vue'),
    meta: { requiresAuth: true, role: 'admin' },
  },
  {
    path: '/admin/providers',
    name: 'admin-providers',
    component: () => import('@/views/admin/ProvidersView.vue'),
    meta: { requiresAuth: true, role: 'admin' },
  },
  {
    path: '/admin/appointments',
    name: 'admin-appointments',
    component: () => import('@/views/admin/AppointmentsView.vue'),
    meta: { requiresAuth: true, role: 'admin' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

/** Where a logged-in user belongs by default — used for guest redirects and wrong-role bounces. */
function homeRouteFor(role) {
  if (role === 'admin') return { name: 'admin-dashboard' }
  if (role === 'provider') return { name: 'provider-dashboard' }
  return { name: 'discover' }
}

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return homeRouteFor(auth.user?.role)
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.role && auth.user?.role !== to.meta.role) {
    // logged in but wrong role for this route — send them to their own home
    return homeRouteFor(auth.user?.role)
  }

  return true
})

export default router
