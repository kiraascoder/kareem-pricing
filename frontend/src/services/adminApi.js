import api from "./api";

export const adminEndpoints = {
  dashboard: "/admin/dashboard",
  bookingTrend: "/admin/dashboard/booking-trend",
};

export async function getAdminDashboard() {
  return api.get(adminEndpoints.dashboard);
}

export async function getBookingTrend(days = 30) {
  return api.get(adminEndpoints.bookingTrend, {
    params: { days },
  });
}

export function unwrapAdminData(response) {
  return response.data?.data ?? response.data;
}
