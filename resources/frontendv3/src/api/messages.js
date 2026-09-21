import api from './client'

export default {
  conversations() {
    return api.get('/messages')
  },
  thread(userId, params = {}) {
    return api.get(`/messages/${userId}`, { params })
  },
  send(payload) {
    return api.post('/messages', payload)
  },
}
