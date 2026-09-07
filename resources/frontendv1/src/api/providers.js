import api from './client'

export default {
  // params: { lat, lng, radius_km, type, open_only, page }
  list(params = {}) {
    return api.get('/providers', { params })
  },
  show(id) {
    return api.get(`/providers/${id}`)
  },
  services(providerId) {
    return api.get(`/providers/${providerId}/services`)
  },
  updateProfile(payload) {
    return api.patch('/provider/profile', payload)
  },
  toggleOpen(payload) {
    return api.patch('/provider/status', payload)
  },
}
