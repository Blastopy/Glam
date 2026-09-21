import axios from 'axios'
import { useNotificationsStore } from '@/stores/notifications'
import { useLoadingStore } from '@/stores/loading'

const api = axios.create({
  baseURL: '/api/v1',
  headers: { Accept: 'application/json' },
  timeout: 20000, // fail fast rather than hanging forever on a dead connection
})

// Attach the bearer token from localStorage, and mark a request as in-flight
// for the global loading bar — every API call counts, so the indicator is
// accurate app-wide without any view having to manage it itself.
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  useLoadingStore().start()
  return config
})

// Global response handling. 401 forces re-login (unchanged behavior). Everything
// a form can't reasonably show inline — network failures, permission errors, and
// unexpected server errors — gets a toast here so the user always sees *something*
// instead of a silently-stuck spinner. 422 validation errors are deliberately left
// alone: those are shown inline next to the relevant field by the calling form.
api.interceptors.response.use(
  (response) => {
    useLoadingStore().stop()
    return response
  },
  (error) => {
    useLoadingStore().stop()
    const status = error.response?.status

    if (status === 401) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
      return Promise.reject(error)
    }

    if (!error.response) {
      useNotificationsStore().error('Could not reach the server. Check your connection and try again.')
    } else if (status === 403) {
      useNotificationsStore().error(error.response.data?.message || "You don't have permission to do that.")
    } else if (status >= 500) {
      useNotificationsStore().error('Something went wrong on our end. Please try again.')
    } else if (status === 429) {
      useNotificationsStore().error(error.response.data?.message || 'Too many attempts — please wait a moment.')
    }

    return Promise.reject(error)
  }
)

export default api
