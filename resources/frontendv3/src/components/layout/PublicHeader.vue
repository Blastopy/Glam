<script setup>
import { useRoute } from 'vue-router'

const route = useRoute()
</script>

<template>
  <nav class="bg-paper/95 backdrop-blur border-b border-ink/10 sticky top-0 z-10">
    <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between">
      <RouterLink to="/" class="flex items-center gap-2 shrink-0">
        <img src="/logo.jpg" alt="SalonBook" class="w-8 h-8 rounded-full object-cover" />
        <span class="font-heading font-semibold text-lg text-ink">MyStyle</span>
      </RouterLink>

      <div class="flex items-center gap-3 text-sm">
        <RouterLink :to="{ path: '/login', query: { redirect: route.fullPath } }" class="text-ink-soft hover:text-clay-600">
          Log in
        </RouterLink>
        <RouterLink :to="{ path: '/register', query: { redirect: route.fullPath } }"
          class="px-3 py-1.5 rounded-lg bg-clay-600 text-white hover:bg-clay-700">
          Sign up
        </RouterLink>
      </div>
    </div>
  </nav>
</template>
