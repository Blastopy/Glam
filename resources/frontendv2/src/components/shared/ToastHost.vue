<script setup>
import { useNotificationsStore } from '@/stores/notifications'

const notifications = useNotificationsStore()

const typeStyles = {
  error: 'bg-clay-600',
  success: 'bg-sage-600',
  info: 'bg-ink',
}
</script>

<template>
  <div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-[calc(100%-2rem)] sm:w-auto">
    <TransitionGroup name="toast">
      <div v-for="t in notifications.toasts" :key="t.id"
        :class="['px-4 py-3 rounded-lg shadow-lg text-sm text-white flex items-start justify-between gap-3', typeStyles[t.type] || typeStyles.info]">
        <span class="leading-snug">{{ t.message }}</span>
        <button @click="notifications.dismiss(t.id)" class="text-white/70 hover:text-white leading-none text-base shrink-0">✕</button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.2s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.toast-leave-to {
  opacity: 0;
}
</style>
