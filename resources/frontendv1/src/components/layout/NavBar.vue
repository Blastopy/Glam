<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <nav class="bg-paper/95 backdrop-blur border-b border-ink/10 sticky top-0 z-10">
    <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between">
      <RouterLink :to="auth.isProvider ? '/dashboard' : '/'" class="font-heading font-semibold text-lg text-ink">
        SalonBook
      </RouterLink>

      <div class="flex items-center gap-5 text-sm text-ink-soft">
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

        <span class="text-ink/20">|</span>
        <span>{{ auth.user?.name }}</span>
        <button @click="handleLogout" class="text-ink-soft hover:text-clay-600">Log out</button>
      </div>
    </div>
  </nav>
</template>
