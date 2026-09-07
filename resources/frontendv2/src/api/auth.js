import api from './client'

export default {
  register(payload) {
    return api.post('/register', payload)
  },
  login(payload) {
    return api.post('/login', payload)
  },
  logout() {
    return api.post('/logout')
  },
  me() {
    return api.get('/me')
  },
}
