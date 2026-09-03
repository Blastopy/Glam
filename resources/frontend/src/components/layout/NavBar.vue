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
  <nav class="bg-white border-b border-gray-200 sticky top-0 z-10">
    <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between">
      <RouterLink :to="auth.isProvider ? '/dashboard' : '/'" class="font-semibold text-brand-600">
        SalonBook
      </RouterLink>

      <div class="flex items-center gap-5 text-sm">
        <template v-if="auth.isClient">
          <RouterLink to="/" class="hover:text-brand-600">Discover</RouterLink>
          <RouterLink to="/my-appointments" class="hover:text-brand-600">My appointments</RouterLink>
          <RouterLink to="/messages" class="hover:text-brand-600">Messages</RouterLink>
        </template>

        <template v-if="auth.isProvider">
          <RouterLink to="/dashboard" class="hover:text-brand-600">Bookings</RouterLink>
          <RouterLink to="/dashboard/catalogue" class="hover:text-brand-600">Catalogue</RouterLink>
          <RouterLink to="/dashboard/clients" class="hover:text-brand-600">Clients</RouterLink>
          <RouterLink to="/dashboard/profile" class="hover:text-brand-600">Profile</RouterLink>
        </template>

        <span class="text-gray-400">|</span>
        <span class="text-gray-600">{{ auth.user?.name }}</span>
        <button @click="handleLogout" class="text-gray-500 hover:text-red-600">Log out</button>
      </div>
    </div>
  </nav>
</template>
