
import api from './api'

export const bookingEndpoints = {
  packages: '/event-packages',
  bookings: '/bookings',

  // Cocokkan dengan route estimasi Laravel yang sudah ada.
  // Contoh: '/price-estimate' atau '/price-recommendations/estimate'
  estimate: null,
}

export function getPackages() {
  return api.get(bookingEndpoints.packages)
}

export function getBookings() {
  return api.get(bookingEndpoints.bookings)
}

export function createBooking(payload) {
  return api.post(bookingEndpoints.bookings, payload)
}

export function getBooking(id) {
  return api.get(`${bookingEndpoints.bookings}/${id}`)
}

export function estimatePrice(payload) {
  if (!bookingEndpoints.estimate) {
    throw new Error(
      'Endpoint estimasi AI belum dikonfigurasi di bookingApi.js.'
    )
  }

  return api.post(bookingEndpoints.estimate, payload)
}

export function extractData(response) {
  return response.data?.data ?? response.data
}

export function extractCollection(response) {
  const data = extractData(response)

  if (Array.isArray(data)) return data
  if (Array.isArray(data?.data)) return data.data

  throw new Error('Format daftar API tidak dikenali.')
}
