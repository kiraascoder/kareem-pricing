
import api from './api'

export const adminBookingAiApi = {
  async getRecommendation(bookingId) {
    const response = await api.get(
      `/admin/bookings/${bookingId}/ai-recommendation`
    )

    return response.data
  },
}
