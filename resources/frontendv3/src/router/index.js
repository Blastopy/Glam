import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  { path: '/login', name: 'login', component: () => import('@/views/auth/LoginView.vue'), meta: { guestOnly: true } },
  { path: '/register', name: 'register', component: () => import('@/views/auth/RegisterView.vue'), meta: { guestOnly: true } },
  { path: '/forgot-password', name: 'forgot-password', component: () => import('@/views/auth/ForgotPasswordView.vue'), meta: { guestOnly: true } },
  { path: '/reset-password', name: 'reset-password', component: () => import('@/views/auth/ResetPasswordView.vue'), meta: { guestOnly: true } },

  // ── Public (browsable by anyone; booking itself still requires login) ──
  {
    path: '/',
    name: 'discover',
    component: () => import('@/views/client/DiscoverView.vue'),
  },
  {
    path: '/providers/:id',
    name: 'provider-profile',
    component: () => import('@/views/client/ProviderProfileView.vue'),
  },

  // ── Client-facing ────────────────────────────────
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
    path: '/provider/activate',
    name: 'provider-activate',
    component: () => import('@/views/provider/ActivateView.vue'),
    meta: { requiresAuth: true, role: 'provider' },
  },
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
  {
    path: '/admin/settings',
    name: 'admin-settings',
    component: () => import('@/views/admin/SettingsView.vue'),
    meta: { requiresAuth: true, role: 'admin' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

/** Where a logged-in user belongs by default — used for guest redirects and wrong-role bounces. */
export function homeRouteFor(role) {
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
    // Preserve where they were headed so login/register can send them back
    // (used by the "log in to book" prompt on the public provider page).
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.role && auth.user?.role !== to.meta.role) {
    // logged in but wrong role for this route — send them to their own home
    return homeRouteFor(auth.user?.role)
  }

  // Providers must have an active (paid) monthly subscription to use anything
  // under /dashboard — the activation page itself is exempt, or this would loop.
  // Note: is_subscription_active is true unconditionally while the admin has
  // system-wide payments switched off (ProviderProfile::isSubscriptionActive),
  // so this guard never fires in that case — nothing extra to check here for it.
  if (auth.isProvider && to.name !== 'provider-activate' && to.path.startsWith('/dashboard') && !auth.isProviderSubscriptionActive) {
    return { name: 'provider-activate' }
  }

  return true
})

export default router
