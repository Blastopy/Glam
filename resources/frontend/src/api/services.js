import api from './client'

export default {
  create(payload) {
    return api.post('/services', payload)
  },
  update(id, payload) {
    return api.patch(`/services/${id}`, payload)
  },
  remove(id) {
    return api.delete(`/services/${id}`)
  },
}
