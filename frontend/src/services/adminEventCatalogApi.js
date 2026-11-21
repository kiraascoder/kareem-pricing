import api from "./api";

const packageUrl = "/admin/event-packages";
const categoryUrl = "/admin/event-categories";

// Paket Event
export const getAdminPackages = (params = {}) =>
  api.get(packageUrl, { params });

export const createAdminPackage = (data) => api.post(packageUrl, data);

export const updateAdminPackage = (id, data) =>
  api.put(`${packageUrl}/${encodeURIComponent(id)}`, data);

export const deactivateAdminPackage = (id) =>
  api.delete(`${packageUrl}/${encodeURIComponent(id)}`);

// Kategori Event
export const getAdminCategories = () => api.get(categoryUrl);

export const createAdminCategory = (data) => api.post(categoryUrl, data);

export const updateAdminCategory = (id, data) =>
  api.put(`${categoryUrl}/${encodeURIComponent(id)}`, data);

export const deactivateAdminCategory = (id) =>
  api.delete(`${categoryUrl}/${encodeURIComponent(id)}`);
