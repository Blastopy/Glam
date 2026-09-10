import api from './client'

export default {
  overview() {
    return api.get('/admin/analytics/overview')
  },
  revenueSeries(days = 30) {
    return api.get('/admin/analytics/revenue', { params: { days } })
  },
  bookingsSeries(days = 30) {
    return api.get('/admin/analytics/bookings', { params: { days } })
  },
  userGrowthSeries(days = 30) {
    return api.get('/admin/analytics/user-growth', { params: { days } })
  },
  topProviders() {
    return api.get('/admin/analytics/top-providers')
  },

  users(params = {}) {
    return api.get('/admin/users', { params })
  },
  suspendUser(id) {
    return api.patch(`/admin/users/${id}/suspend`)
  },
  reactivateUser(id) {
    return api.patch(`/admin/users/${id}/reactivate`)
  },

  providers(params = {}) {
    return api.get('/admin/providers', { params })
  },
  verifyProvider(id) {
    return api.patch(`/admin/providers/${id}/verify`)
  },
  unverifyProvider(id) {
    return api.patch(`/admin/providers/${id}/unverify`)
  },

  appointments(params = {}) {
    return api.get('/admin/appointments', { params })
  },
  cancelAppointment(id, reason) {
    return api.patch(`/admin/appointments/${id}/cancel`, { reason })
  },
}
