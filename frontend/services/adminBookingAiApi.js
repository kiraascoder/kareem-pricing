import api from './api'

export function getAdminAiRecommendation(bookingId) {
  return api.get(`/admin/bookings/${bookingId}/ai-recommendation`)
}
