import api from './client'

export default {
  list(params = {}) {
    return api.get('/appointments', { params })
  },
  show(id) {
    return api.get(`/appointments/${id}`)
  },
  book(payload) {
    return api.post('/appointments', payload)
  },
  updateStatus(id, payload) {
    return api.patch(`/appointments/${id}/status`, payload)
  },
  checkIn(id) {
    return api.post(`/appointments/${id}/check-in`)
  },
  review(id, payload) {
    return api.post(`/appointments/${id}/review`, payload)
  },
}
