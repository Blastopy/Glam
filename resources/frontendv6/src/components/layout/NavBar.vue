<script setup>
import { ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const menuOpen = ref(false)

async function handleLogout() {
  menuOpen.value = false
  await auth.logout()
  router.push({ name: 'login' })
}

// Close the mobile menu automatically whenever navigation happens, including
// via the browser back/forward buttons (not just a link click inside it).
watch(() => route.fullPath, () => {
  menuOpen.value = false
})
</script>

<template>
  <nav class="bg-paper/95 backdrop-blur border-b border-ink/10 sticky top-0 z-10">
    <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between">
      <RouterLink :to="auth.isAdmin ? '/admin' : auth.isProvider ? '/dashboard' : '/'" class="flex items-center gap-2 shrink-0">
        <img src="/logo.jpg" alt="SalonBook" class="w-8 h-8 rounded-full object-cover" />
        <span class="font-heading font-semibold text-lg text-ink">SalonBook</span>
      </RouterLink>

      <!-- Desktop nav — full link set, visible from md up -->
      <div class="hidden md:flex items-center gap-5 text-sm text-ink-soft">
        <template v-if="auth.isClient">
          <RouterLink to="/" class="hover:text-clay-600">Discover</RouterLink>
          <RouterLink to="/my-appointments" class="hover:text-clay-600">My appointments</RouterLink>
          <RouterLink to="/messages" class="hover:text-clay-600">Messages</RouterLink>
        </template>

        <template v-if="auth.isProvider">
          <RouterLink to="/dashboard" class="hover:text-clay-600">Bookings</RouterLink>
          <RouterLink to="/dashboard/catalogue" class="hover:text-clay-600">Catalogue</RouterLink>
          <RouterLink to="/dashboard/clients" class="hover:text-clay-600">Clients</RouterLink>
          <RouterLink to="/dashboard/profile" class="hover:text-clay-600">Profile</RouterLink>
        </template>

        <template v-if="auth.isAdmin">
          <RouterLink to="/admin" class="hover:text-clay-600">Overview</RouterLink>
          <RouterLink to="/admin/users" class="hover:text-clay-600">Users</RouterLink>
          <RouterLink to="/admin/providers" class="hover:text-clay-600">Providers</RouterLink>
          <RouterLink to="/admin/appointments" class="hover:text-clay-600">Appointments</RouterLink>
        </template>

        <span class="text-ink/20">|</span>
        <span class="max-w-[10rem] truncate">{{ auth.user?.name }}</span>
        <button @click="handleLogout" class="text-ink-soft hover:text-clay-600">Log out</button>
      </div>

      <!-- Mobile menu toggle — visible below md, 44px tap target -->
      <button @click="menuOpen = !menuOpen" class="md:hidden -mr-2 p-3 text-ink" :aria-expanded="menuOpen" aria-label="Toggle menu">
        <svg v-if="!menuOpen" viewBox="0 0 24 24" fill="none" class="w-5 h-5">
          <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
        <svg v-else viewBox="0 0 24 24" fill="none" class="w-5 h-5">
          <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
      </button>
    </div>

    <!-- Mobile dropdown panel -->
    <div v-if="menuOpen" class="md:hidden border-t border-ink/10 bg-paper px-4 py-3 flex flex-col text-sm">
      <template v-if="auth.isClient">
        <RouterLink to="/" class="py-2.5 text-ink-soft hover:text-clay-600">Discover</RouterLink>
        <RouterLink to="/my-appointments" class="py-2.5 text-ink-soft hover:text-clay-600">My appointments</RouterLink>
        <RouterLink to="/messages" class="py-2.5 text-ink-soft hover:text-clay-600">Messages</RouterLink>
      </template>

      <template v-if="auth.isProvider">
        <RouterLink to="/dashboard" class="py-2.5 text-ink-soft hover:text-clay-600">Bookings</RouterLink>
        <RouterLink to="/dashboard/catalogue" class="py-2.5 text-ink-soft hover:text-clay-600">Catalogue</RouterLink>
        <RouterLink to="/dashboard/clients" class="py-2.5 text-ink-soft hover:text-clay-600">Clients</RouterLink>
        <RouterLink to="/dashboard/profile" class="py-2.5 text-ink-soft hover:text-clay-600">Profile</RouterLink>
      </template>

      <template v-if="auth.isAdmin">
        <RouterLink to="/admin" class="py-2.5 text-ink-soft hover:text-clay-600">Overview</RouterLink>
        <RouterLink to="/admin/users" class="py-2.5 text-ink-soft hover:text-clay-600">Users</RouterLink>
        <RouterLink to="/admin/providers" class="py-2.5 text-ink-soft hover:text-clay-600">Providers</RouterLink>
        <RouterLink to="/admin/appointments" class="py-2.5 text-ink-soft hover:text-clay-600">Appointments</RouterLink>
      </template>

      <div class="border-t border-ink/10 mt-1 pt-3 flex items-center justify-between">
        <span class="text-ink-soft truncate">{{ auth.user?.name }}</span>
        <button @click="handleLogout" class="text-red-600 py-1">Log out</button>
      </div>
    </div>
  </nav>
</template>
