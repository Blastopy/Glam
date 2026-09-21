import { defineStore } from 'pinia'
import appointmentsApi from '@/api/appointments'

export const useAppointmentsStore = defineStore('appointments', {
  state: () => ({
    list: [],
    current: null,
    loading: false,
  }),

  actions: {
    async fetchList(params = {}) {
      this.loading = true
      try {
        const { data } = await appointmentsApi.list(params)
        this.list = data.data
        return data
      } finally {
        this.loading = false
      }
    },

    async book(payload) {
      const { data } = await appointmentsApi.book(payload)
      this.list.unshift(data)
      return data
    },

    async updateStatus(id, status, providerNotes = null) {
      const { data } = await appointmentsApi.updateStatus(id, {
        status,
        provider_notes: providerNotes,
      })
      const idx = this.list.findIndex((a) => a.id === id)
      if (idx !== -1) this.list[idx] = { ...this.list[idx], ...data }
      return data
    },

    async checkIn(id) {
      const { data } = await appointmentsApi.checkIn(id)
      const idx = this.list.findIndex((a) => a.id === id)
      if (idx !== -1) this.list[idx] = { ...this.list[idx], ...data }
      return data
    },
  },
})
