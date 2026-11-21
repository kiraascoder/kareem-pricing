import api from "./api";

const baseUrl = "/admin/payments";

export function getAdminPayments(params = {}) {
  return api.get(baseUrl, { params });
}

export function getAdminPaymentProof(id) {
  return api.get(`${baseUrl}/${encodeURIComponent(id)}/proof`, {
    responseType: "blob",
  });
}

export function verifyAdminPayment(id, payload) {
  return api.patch(`${baseUrl}/${encodeURIComponent(id)}/verify`, payload);
}

export function unwrapAdminPayments(response) {
  const result = response?.data?.data;

  if (Array.isArray(result)) {
    return {
      data: result,
      current_page: 1,
      last_page: 1,
    };
  }

  if (Array.isArray(result?.data)) {
    return result;
  }

  throw new Error("Format daftar pembayaran tidak dikenali.");
}
