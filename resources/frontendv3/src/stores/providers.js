import { defineStore } from 'pinia'
import providersApi from '@/api/providers'

export const useProvidersStore = defineStore('providers', {
  state: () => ({
    list: [],
    current: null,
    loading: false,
    filters: { type: '', open_only: false, home_service: false, search: '', lat: null, lng: null, radius_km: 10 },
  }),

  actions: {
    async fetchList(params = {}) {
      this.loading = true
      try {
        const { data } = await providersApi.list({ ...this.filters, ...params })
        this.list = data.data // paginated response
        return data
      } finally {
        this.loading = false
      }
    },

    async fetchOne(id) {
      this.loading = true
      try {
        const { data } = await providersApi.show(id)
        this.current = data
        return data
      } finally {
        this.loading = false
      }
    },

    async useMyLocation() {
      return new Promise((resolve, reject) => {
        if (!navigator.geolocation) return reject(new Error('Geolocation not supported'))
        navigator.geolocation.getCurrentPosition(
          (pos) => {
            this.filters.lat = pos.coords.latitude
            this.filters.lng = pos.coords.longitude
            resolve()
          },
          reject
        )
      })
    },
  },
})
