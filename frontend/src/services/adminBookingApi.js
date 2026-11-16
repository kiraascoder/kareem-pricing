import api from "./api";

const baseUrl = "/admin/bookings";

export function getAdminBookings(params = {}) {
  return api.get(baseUrl, { params });
}

export function getAdminBooking(id) {
  return api.get(`${baseUrl}/${encodeURIComponent(id)}`);
}

export function reviewAdminBooking(id, payload) {
  return api.patch(`${baseUrl}/${encodeURIComponent(id)}/review`, payload);
}

export function createAdminPriceQuote(id, payload) {
  return api.post(`${baseUrl}/${encodeURIComponent(id)}/price-quote`, payload);
}

export function unwrapAdminBooking(response) {
  return response?.data?.data ?? response?.data;
}


export function unwrapAdminBookingList(response) {
  const data = unwrapAdminBooking(response);

  if (Array.isArray(data)) return data;
  if (Array.isArray(data?.data)) return data.data;
  if (Array.isArray(data?.bookings)) return data.bookings;
  if (Array.isArray(data?.bookings?.data)) {
    return data.bookings.data;
  }

  throw new Error("Format daftar booking dari Laravel tidak dikenali.");

}
