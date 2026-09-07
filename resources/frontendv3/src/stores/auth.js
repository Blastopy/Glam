import { defineStore } from 'pinia'
import authApi from '@/api/auth'
import { initEcho, disconnectEcho } from '@/echo'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('auth_user') || 'null'),
    token: localStorage.getItem('auth_token') || null,
    loading: false,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    isProvider: (state) => state.user?.role === 'provider',
    isClient: (state) => state.user?.role === 'client',
    isAdmin: (state) => state.user?.role === 'admin',
  },

  actions: {
    persist(user, token) {
      this.user = user
      this.token = token
      localStorage.setItem('auth_user', JSON.stringify(user))
      localStorage.setItem('auth_token', token)
      initEcho() // token is now in localStorage, safe to open the socket
    },

    async register(payload) {
      this.loading = true
      try {
        const { data } = await authApi.register(payload)
        this.persist(data.user, data.token)
        return data
      } finally {
        this.loading = false
      }
    },

    async login(payload) {
      this.loading = true
      try {
        const { data } = await authApi.login(payload)
        this.persist(data.user, data.token)
        return data
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await authApi.logout()
      } finally {
        this.user = null
        this.token = null
        localStorage.removeItem('auth_user')
        localStorage.removeItem('auth_token')
        disconnectEcho()
      }
    },

    async fetchMe() {
      const { data } = await authApi.me()
      this.user = data
      localStorage.setItem('auth_user', JSON.stringify(data))
      return data
    },
  },
})
