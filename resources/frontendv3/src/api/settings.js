import api from './client'

export default {
  status() {
    return api.get('/system-settings')
  },
}
