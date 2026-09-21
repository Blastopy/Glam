<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NavBar from '@/components/layout/NavBar.vue'
import PublicHeader from '@/components/layout/PublicHeader.vue'
import Footer from '@/components/layout/Footer.vue'
import ToastHost from '@/components/shared/ToastHost.vue'
import LoadingBar from '@/components/shared/LoadingBar.vue'

const route = useRoute()
const auth = useAuthStore()

const showNav = computed(() => auth.isAuthenticated && !route.meta.guestOnly)
// Public pages (homepage, provider profile) have no requiresAuth/guestOnly meta at all —
// a guest browsing one of these gets a minimal header instead of the full authenticated nav.
const showPublicHeader = computed(() => !auth.isAuthenticated && !route.meta.requiresAuth && !route.meta.guestOnly)
const showContentContainer = computed(() => showNav.value || showPublicHeader.value)
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <LoadingBar />
    <NavBar v-if="showNav" />
    <PublicHeader v-else-if="showPublicHeader" />
    <main :class="[showContentContainer ? 'max-w-5xl mx-auto px-4 py-6 w-full' : '', 'flex-1']">
      <RouterView />
    </main>
    <Footer />
    <ToastHost />
  </div>
</template>
