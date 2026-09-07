import { defineStore } from 'pinia'

let nextId = 1

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({ toasts: [] }),

  actions: {
    push(message, type = 'error', timeoutMs = 5000) {
      const id = nextId++
      this.toasts.push({ id, message, type })
      if (timeoutMs) {
        setTimeout(() => this.dismiss(id), timeoutMs)
      }
      return id
    },

    error(message) {
      return this.push(message, 'error')
    },

    success(message) {
      return this.push(message, 'success', 3000)
    },

    dismiss(id) {
      this.toasts = this.toasts.filter((t) => t.id !== id)
    },
  },
})
