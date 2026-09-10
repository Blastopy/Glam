import api from './client'

export default {
  status() {
    return api.get('/provider/subscription')
  },
  pay(phone) {
    return api.post('/provider/subscription/pay', { phone })
  },
  poll(paymentId) {
    return api.get(`/provider/subscription/${paymentId}/poll`)
  },
}
