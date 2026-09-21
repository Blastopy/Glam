<script setup>
import { useLoadingStore } from '@/stores/loading'

const loading = useLoadingStore()
</script>

<template>
  <div class="fixed top-0 left-0 right-0 z-[60] h-0.5 pointer-events-none">
    <div v-if="loading.isLoading" class="h-full bg-clay-500 animate-loading-bar" />
  </div>
</template>

<style scoped>
/* Sweeps left-to-right repeatedly while requests are in flight — indeterminate,
   since we don't track real progress, just "something is happening". */
@keyframes loading-sweep {
  0% { width: 0%; margin-left: 0%; }
  50% { width: 60%; margin-left: 20%; }
  100% { width: 0%; margin-left: 100%; }
}
.animate-loading-bar {
  animation: loading-sweep 1.1s ease-in-out infinite;
}
</style>
