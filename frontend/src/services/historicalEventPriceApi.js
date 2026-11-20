import api from "./api";
const endpoint = "/admin/historical-event-prices";
export const historicalEventPriceApi = {
  list: (params = {}) => api.get(endpoint, { params }),
  categories: () => api.get(`${endpoint}/categories`),
  create: (payload) => api.post(endpoint, payload),
  update: (id, payload) => api.put(`${endpoint}/${id}`, payload),
  remove: (id) => api.delete(`${endpoint}/${id}`),
};
