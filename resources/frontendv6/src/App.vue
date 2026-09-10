<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NavBar from '@/components/layout/NavBar.vue'
import ToastHost from '@/components/shared/ToastHost.vue'
import LoadingBar from '@/components/shared/LoadingBar.vue'

const route = useRoute()
const auth = useAuthStore()

const showNav = computed(() => auth.isAuthenticated && !route.meta.guestOnly)
</script>

<template>
  <div class="min-h-screen">
    <LoadingBar />
    <NavBar v-if="showNav" />
    <main :class="showNav ? 'max-w-5xl mx-auto px-4 py-6' : ''">
      <RouterView />
    </main>
    <ToastHost />
  </div>
</template>
