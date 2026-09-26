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
    // Eloquent serializes the providerProfile relation as snake_case — defensive
    // fallback either way rather than assuming which shape the API returned.
    providerProfile: (state) => state.user?.providerProfile || state.user?.provider_profile || null,
    isProviderSubscriptionActive: (state) => {
      const profile = state.user?.providerProfile || state.user?.provider_profile
      return !!profile?.is_subscription_active
    },
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

    async loginWithGoogle(credential) {
      this.loading = true
      try {
        const { data } = await authApi.google(credential)
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

    /** Refreshes the cached user from the server — call after anything that
     * changes role-relevant state (e.g. a subscription payment) so router
     * guards see it without a re-login. */
    async fetchMe() {
      const { data } = await authApi.me()
      this.user = data
      localStorage.setItem('auth_user', JSON.stringify(data))
      return data
    },

    async refreshUser() {
      return this.fetchMe()
    },

    /**
     * Patches the cached provider profile's subscription fields immediately,
     * without a network round-trip. Exists because the router guard that
     * gates /dashboard reads auth.user's cached providerProfile synchronously
     * — if a post-payment refetch (fetchMe/refreshUser) is slow or drops,
     * the guard would still see the old, unpaid state and bounce a provider
     * straight back to /provider/activate the instant they click
     * "Go to dashboard", even though the payment genuinely succeeded. Calling
     * this right after a confirmed payment makes the local state correct
     * immediately, independent of whether the follow-up refetch succeeds.
     */
    setProviderSubscriptionActive(expiresAt) {
      if (!this.user) return

      const patch = { is_subscription_active: true, subscription_expires_at: expiresAt }

      if (this.user.providerProfile) {
        this.user = { ...this.user, providerProfile: { ...this.user.providerProfile, ...patch } }
      } else if (this.user.provider_profile) {
        this.user = { ...this.user, provider_profile: { ...this.user.provider_profile, ...patch } }
      } else {
        return // no provider profile cached at all — nothing to patch
      }

      localStorage.setItem('auth_user', JSON.stringify(this.user))
    },
  },
})
