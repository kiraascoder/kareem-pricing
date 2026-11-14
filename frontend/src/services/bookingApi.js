import api from "./api";

/**
 * KAREEM PRICING
 * Customer Booking API Service
 *
 * Base URL:
 * http://127.0.0.1:8000/api/v1
 *
 * Semua endpoint di bawah mengikuti
 * route Laravel yang sudah terdaftar.
 */

export const bookingEndpoints = {
  categories: "/categories",
  packages: "/packages",
  bookings: "/bookings",
};

/* =========================================
   EVENT CATEGORIES
========================================= */

/**
 * Mengambil daftar kategori event.
 *
 * GET /api/v1/categories
 */
export function getCategories(params = {}) {
  return api.get(bookingEndpoints.categories, {
    params,
  });
}

/* =========================================
   EVENT PACKAGES
========================================= */

/**
 * Mengambil daftar paket event.
 *
 * GET /api/v1/packages
 */
export function getPackages(params = {}) {
  return api.get(bookingEndpoints.packages, {
    params,
  });
}

/**
 * Mengambil detail paket berdasarkan ID.
 *
 * GET /api/v1/packages/{id}
 */
export function getPackage(id) {
  return api.get(`${bookingEndpoints.packages}/${encodeURIComponent(id)}`);
}

/* =========================================
   CUSTOMER BOOKINGS
========================================= */

/**
 * Mengambil daftar booking customer.
 *
 * GET /api/v1/bookings
 */
export function getBookings(params = {}) {
  return api.get(bookingEndpoints.bookings, {
    params,
  });
}

/**
 * Mengambil detail booking berdasarkan ID.
 *
 * GET /api/v1/bookings/{booking}
 */
export function getBooking(id) {
  return api.get(`${bookingEndpoints.bookings}/${encodeURIComponent(id)}`);
}

/**
 * Membuat booking baru.
 *
 * POST /api/v1/bookings
 *
 * Payload harus mengikuti validasi
 * BookingController Laravel.
 */
export function createBooking(payload) {
  return api.post(bookingEndpoints.bookings, payload);
}

/* =========================================
   BOOKING HISTORY
========================================= */

/**
 * Mengambil riwayat aktivitas booking.
 *
 * GET /api/v1/bookings/{booking}/history
 */
export function getBookingHistory(bookingId) {
  return api.get(
    `${bookingEndpoints.bookings}/${encodeURIComponent(bookingId)}/history`,
  );
}

/* =========================================
   AI PRICE ESTIMATION
========================================= */

/**
 * Mengambil rekomendasi harga AI.
 *
 * GET /api/v1/bookings/{booking}/price-estimate
 *
 * Estimasi hanya dapat diminta setelah
 * booking berhasil dibuat.
 */
export function getPriceEstimate(bookingId) {
  return api.get(
    `${bookingEndpoints.bookings}/${encodeURIComponent(bookingId)}/price-estimate`,
  );
}

/* =========================================
   CUSTOMER PRICE QUOTE
========================================= */

/**
 * Mengambil penawaran harga dari admin.
 *
 * GET /api/v1/bookings/{booking}/price-quote
 */
export function getPriceQuote(bookingId) {
  return api.get(
    `${bookingEndpoints.bookings}/${encodeURIComponent(bookingId)}/price-quote`,
  );
}

/**
 * Customer memberikan respons
 * terhadap penawaran harga admin.
 *
 * PATCH /api/v1/bookings/{booking}/price-quote/respond
 *
 * Contoh payload harus disesuaikan
 * dengan validasi CustomerPriceQuoteController.
 */
export function respondToPriceQuote(bookingId, payload) {
  return api.patch(
    `${bookingEndpoints.bookings}/${encodeURIComponent(bookingId)}/price-quote/respond`,
    payload,
  );
}

/* =========================================
   CUSTOMER PAYMENTS
========================================= */

/**
 * Mengambil daftar pembayaran booking.
 *
 * GET /api/v1/bookings/{booking}/payments
 */
export function getBookingPayments(bookingId) {
  return api.get(
    `${bookingEndpoints.bookings}/${encodeURIComponent(bookingId)}/payments`,
  );
}

/**
 * Mengirim bukti pembayaran.
 *
 * POST /api/v1/bookings/{booking}/payments
 *
 * Menggunakan FormData agar file
 * dapat dikirim melalui multipart/form-data.
 */
export function submitBookingPayment(bookingId, payload) {
  const formData = new FormData();

  Object.entries(payload).forEach(([key, value]) => {
    if (value === null || value === undefined) {
      return;
    }

    if (Array.isArray(value)) {
      value.forEach((item) => {
        formData.append(`${key}[]`, item);
      });
    } else {
      formData.append(key, value);
    }
  });

  return api.post(
    `${bookingEndpoints.bookings}/${encodeURIComponent(bookingId)}/payments`,
    formData,
  );
}

/* =========================================
   RESPONSE HELPERS
========================================= */

/**
 * Mengambil data utama dari respons API.
 *
 * Mendukung:
 *
 * { data: {...} }
 * { data: [...] }
 * [...]
 */
export function extractData(response) {
  return response?.data?.data ?? response?.data;
}

/**
 * Mengambil array dari respons Laravel.
 *
 * Mendukung:
 *
 * 1. Array langsung
 * 2. Laravel API Resource Collection
 * 3. Laravel pagination
 */
export function extractCollection(response) {
  const data = extractData(response);

  if (Array.isArray(data)) {
    return data;
  }

  if (Array.isArray(data?.data)) {
    return data.data;
  }

  throw new Error("Format data API tidak dikenali. Periksa respons Laravel.");
}

/**
 * Mengambil metadata pagination.
 */
export function extractPagination(response) {
  const body = response?.data ?? {};
  const data = body.data ?? body;

  const pagination =
    data?.current_page !== undefined ? data : (body.meta ?? null);

  if (!pagination) {
    return null;
  }

  return {
    currentPage: pagination.current_page ?? 1,

    lastPage: pagination.last_page ?? 1,

    perPage: pagination.per_page ?? 10,

    total: pagination.total ?? 0,
  };
}

/**
 * Mengambil pesan error Laravel.
 *
 * Mendukung validation error 422
 * dan error API umum.
 */
export function extractApiError(
  error,
  fallback = "Terjadi kesalahan pada server.",
) {
  const response = error?.response?.data;

  if (!response) {
    return error?.message || fallback;
  }

  if (response.errors) {
    return Object.values(response.errors).flat().join(" ");
  }

  return response.message || fallback;
}

/* =========================================
   DEFAULT EXPORT
========================================= */

export default {
  getCategories,
  getPackages,
  getPackage,
  getBookings,
  getBooking,
  createBooking,
  getBookingHistory,
  getPriceEstimate,
  getPriceQuote,
  respondToPriceQuote,
  getBookingPayments,
  submitBookingPayment,
  extractData,
  extractCollection,
  extractPagination,
  extractApiError,
};
