<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import messagesApi from '@/api/messages'
import appointmentsApi from '@/api/appointments'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const menuOpen = ref(false)
const unreadMessages = ref(0)
const pendingBookings = ref(0)
let badgeTimer = null

async function handleLogout() {
  menuOpen.value = false
  await auth.logout()
  router.push({ name: 'login' })
}

watch(() => route.fullPath, () => {
  menuOpen.value = false
  refreshBadges()
})

async function refreshBadges() {
  if (!auth.isAuthenticated) return
  try {
    if (auth.isClient || auth.isProvider) {
      const { data } = await messagesApi.conversations()
      unreadMessages.value = data.reduce((sum, c) => sum + (c.unread_count || 0), 0)
    }
    if (auth.isProvider) {
      const { data } = await appointmentsApi.list({ status: 'pending' })
      pendingBookings.value = data.total ?? 0
    }
  } catch {
    // badges are a nice-to-have on top of the real UI, not worth a toast of their own
  }
}

onMounted(() => {
  refreshBadges()
  badgeTimer = setInterval(refreshBadges, 30000)
})
onBeforeUnmount(() => badgeTimer && clearInterval(badgeTimer))
</script>

<template>
  <nav class="bg-paper/95 backdrop-blur border-b border-ink/10 sticky top-0 z-10">
    <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between">
      <RouterLink :to="auth.isAdmin ? '/admin' : auth.isProvider ? '/dashboard' : '/'" class="flex items-center gap-2 shrink-0">
        <img src="/logo.jpg" alt="SalonBook" class="w-8 h-8 rounded-full object-cover" />
        <span class="font-heading font-semibold text-lg text-ink">MyStyle</span>
      </RouterLink>

      <div class="hidden md:flex items-center gap-5 text-sm text-ink-soft">
        <template v-if="auth.isClient">
          <RouterLink to="/" class="hover:text-clay-600">Discover</RouterLink>
          <RouterLink to="/my-appointments" class="hover:text-clay-600">My appointments</RouterLink>
          <RouterLink to="/messages" class="hover:text-clay-600 relative">
            Messages
            <span v-if="unreadMessages" class="absolute -top-1.5 -right-3 bg-clay-600 text-white text-[10px] leading-none rounded-full px-1.5 py-0.5">{{ unreadMessages }}</span>
          </RouterLink>
        </template>

        <template v-if="auth.isProvider">
          <RouterLink to="/dashboard" class="hover:text-clay-600 relative">
            Bookings
            <span v-if="pendingBookings" class="absolute -top-1.5 -right-3 bg-clay-600 text-white text-[10px] leading-none rounded-full px-1.5 py-0.5">{{ pendingBookings }}</span>
          </RouterLink>
          <RouterLink to="/dashboard/catalogue" class="hover:text-clay-600">Catalogue</RouterLink>
          <RouterLink to="/dashboard/clients" class="hover:text-clay-600">Clients</RouterLink>
          <RouterLink to="/messages" class="hover:text-clay-600 relative">
            Messages
            <span v-if="unreadMessages" class="absolute -top-1.5 -right-3 bg-clay-600 text-white text-[10px] leading-none rounded-full px-1.5 py-0.5">{{ unreadMessages }}</span>
          </RouterLink>
          <RouterLink to="/dashboard/profile" class="hover:text-clay-600">Profile</RouterLink>
        </template>

        <template v-if="auth.isAdmin">
          <RouterLink to="/admin" class="hover:text-clay-600">Overview</RouterLink>
          <RouterLink to="/admin/users" class="hover:text-clay-600">Users</RouterLink>
          <RouterLink to="/admin/providers" class="hover:text-clay-600">Providers</RouterLink>
          <RouterLink to="/admin/appointments" class="hover:text-clay-600">Appointments</RouterLink>
          <RouterLink to="/admin/settings" class="hover:text-clay-600">Settings</RouterLink>
        </template>

        <span class="text-ink/20">|</span>
        <span class="max-w-[10rem] truncate">{{ auth.user?.name }}</span>
        <button @click="handleLogout" class="text-ink-soft hover:text-clay-600">Log out</button>
      </div>

      <button @click="menuOpen = !menuOpen" class="md:hidden -mr-2 p-3 text-ink relative" :aria-expanded="menuOpen" aria-label="Toggle menu">
        <svg v-if="!menuOpen" viewBox="0 0 24 24" fill="none" class="w-5 h-5">
          <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
        <svg v-else viewBox="0 0 24 24" fill="none" class="w-5 h-5">
          <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
        <span v-if="!menuOpen && (unreadMessages || pendingBookings)" class="absolute top-1.5 right-1.5 w-2 h-2 bg-clay-600 rounded-full"></span>
      </button>
    </div>

    <div v-if="menuOpen" class="md:hidden border-t border-ink/10 bg-paper px-4 py-3 flex flex-col text-sm">
      <template v-if="auth.isClient">
        <RouterLink to="/" class="py-2.5 text-ink-soft hover:text-clay-600">Discover</RouterLink>
        <RouterLink to="/my-appointments" class="py-2.5 text-ink-soft hover:text-clay-600">My appointments</RouterLink>
        <RouterLink to="/messages" class="py-2.5 text-ink-soft hover:text-clay-600 flex items-center gap-2">
          Messages
          <span v-if="unreadMessages" class="bg-clay-600 text-white text-[10px] leading-none rounded-full px-1.5 py-0.5">{{ unreadMessages }}</span>
        </RouterLink>
      </template>

      <template v-if="auth.isProvider">
        <RouterLink to="/dashboard" class="py-2.5 text-ink-soft hover:text-clay-600 flex items-center gap-2">
          Bookings
          <span v-if="pendingBookings" class="bg-clay-600 text-white text-[10px] leading-none rounded-full px-1.5 py-0.5">{{ pendingBookings }}</span>
        </RouterLink>
        <RouterLink to="/dashboard/catalogue" class="py-2.5 text-ink-soft hover:text-clay-600">Catalogue</RouterLink>
        <RouterLink to="/dashboard/clients" class="py-2.5 text-ink-soft hover:text-clay-600">Clients</RouterLink>
        <RouterLink to="/messages" class="py-2.5 text-ink-soft hover:text-clay-600 flex items-center gap-2">
          Messages
          <span v-if="unreadMessages" class="bg-clay-600 text-white text-[10px] leading-none rounded-full px-1.5 py-0.5">{{ unreadMessages }}</span>
        </RouterLink>
        <RouterLink to="/dashboard/profile" class="py-2.5 text-ink-soft hover:text-clay-600">Profile</RouterLink>
      </template>

      <template v-if="auth.isAdmin">
        <RouterLink to="/admin" class="py-2.5 text-ink-soft hover:text-clay-600">Overview</RouterLink>
        <RouterLink to="/admin/users" class="py-2.5 text-ink-soft hover:text-clay-600">Users</RouterLink>
        <RouterLink to="/admin/providers" class="py-2.5 text-ink-soft hover:text-clay-600">Providers</RouterLink>
        <RouterLink to="/admin/appointments" class="py-2.5 text-ink-soft hover:text-clay-600">Appointments</RouterLink>
        <RouterLink to="/admin/settings" class="py-2.5 text-ink-soft hover:text-clay-600">Settings</RouterLink>
      </template>

      <div class="border-t border-ink/10 mt-1 pt-3 flex items-center justify-between">
        <span class="text-ink-soft truncate">{{ auth.user?.name }}</span>
        <button @click="handleLogout" class="text-red-600 py-1">Log out</button>
      </div>
    </div>
  </nav>
</template>
