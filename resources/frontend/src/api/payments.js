import api from './client'

export default {
  initiateDeposit(appointmentId, phone) {
    return api.post(`/appointments/${appointmentId}/deposit`, { phone })
  },
  status(paymentId) {
    return api.get(`/payments/${paymentId}/status`)
  },
}
