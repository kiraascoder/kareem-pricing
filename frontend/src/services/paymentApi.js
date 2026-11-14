import api from "./api";

const bookingUrl = (id) => `/bookings/${encodeURIComponent(id)}`;

export const getPriceQuote = (bookingId) =>
  api.get(`${bookingUrl(bookingId)}/price-quote`);

export const respondToPriceQuote = (bookingId, decision, notes = "") =>
  api.patch(`${bookingUrl(bookingId)}/price-quote/respond`, {
    decision,
    customer_notes: notes,
  });

export const getPayments = (bookingId) =>
  api.get(`${bookingUrl(bookingId)}/payments`);

export const submitPayment = (bookingId, values, file) => {
  const form = new FormData();

  form.append("payment_method", values.payment_method);
  form.append("sender_name", values.sender_name);
  form.append("bank_name", values.bank_name);
  form.append(
    "transferred_at",
    values.transferred_at.replace("T", " ") + ":00",
  );
  form.append("customer_notes", values.customer_notes || "");
  form.append("proof", file);

  return api.post(`${bookingUrl(bookingId)}/payments`, form);
};

export function unwrap(response) {
  return response.data?.data ?? response.data;
}

export function unwrapList(response) {
  const data = unwrap(response);
  if (Array.isArray(data)) return data;
  if (Array.isArray(data?.data)) return data.data;
  return [];
}
