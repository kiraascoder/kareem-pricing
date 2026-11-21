<script setup>
import { computed, onMounted, ref } from "vue";
import AdminLayout from "../layouts/AdminLayout.vue";
import {
  getAdminPackages,
  createAdminPackage,
  updateAdminPackage,
  deactivateAdminPackage,
  getAdminCategories,
  createAdminCategory,
  updateAdminCategory,
  deactivateAdminCategory,
} from "../services/adminEventCatalogApi";

const tab = ref("packages");
const packages = ref([]);
const categories = ref([]);
const loading = ref(false);
const saving = ref(false);
const error = ref("");
const success = ref("");
const search = ref("");
const currentPage = ref(1);
const lastPage = ref(1);

const modalOpen = ref(false);
const editingId = ref(null);

const emptyPackage = () => ({
  category_id: "",
  name: "",
  description: "",
  base_price: "",
  min_participants: "",
  max_participants: "",
  is_active: true,
});

const emptyCategory = () => ({
  name: "",
  description: "",
  is_active: true,
});

const form = ref(emptyPackage());

const activeCategories = computed(() =>
  categories.value.filter((item) => item.is_active),
);

const filteredPackages = computed(() => {
  const keyword = search.value.trim().toLowerCase();

  return packages.value.filter((item) =>
    [item.name, item.category?.name, item.description]
      .filter(Boolean)
      .join(" ")
      .toLowerCase()
      .includes(keyword),
  );
});

const filteredCategories = computed(() => {
  const keyword = search.value.trim().toLowerCase();

  return categories.value.filter((item) =>
    [item.name, item.slug, item.description]
      .filter(Boolean)
      .join(" ")
      .toLowerCase()
      .includes(keyword),
  );
});

function formatRupiah(value) {
  const amount = Number(value);

  if (!Number.isFinite(amount)) return "-";

  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(amount);
}

function showError(err) {
  const validation = err.response?.data?.errors;

  error.value =
    (validation && Object.values(validation).flat().join(" ")) ||
    err.response?.data?.message ||
    err.message ||
    "Terjadi kesalahan.";
}

async function loadData(page = 1) {
  loading.value = true;
  error.value = "";

  try {
    const [packageResponse, categoryResponse] = await Promise.all([
      getAdminPackages({ page }),
      getAdminCategories(),
    ]);

    const packageResult = packageResponse.data?.data;
    const categoryResult = categoryResponse.data?.data;

    packages.value = Array.isArray(packageResult)
      ? packageResult
      : packageResult?.data || [];

    categories.value = Array.isArray(categoryResult) ? categoryResult : [];

    currentPage.value = packageResult?.current_page || 1;
    lastPage.value = packageResult?.last_page || 1;
  } catch (err) {
    showError(err);
  } finally {
    loading.value = false;
  }
}

function switchTab(value) {
  tab.value = value;
  search.value = "";
  error.value = "";
  success.value = "";
}

function openCreate() {
  editingId.value = null;
  form.value = tab.value === "packages" ? emptyPackage() : emptyCategory();

  modalOpen.value = true;
  error.value = "";
}

function openEdit(item) {
  editingId.value = item.id;

  if (tab.value === "packages") {
    form.value = {
      category_id: item.category_id,
      name: item.name,
      description: item.description || "",
      base_price: item.base_price,
      min_participants: item.min_participants ?? "",
      max_participants: item.max_participants ?? "",
      is_active: Boolean(item.is_active),
    };
  } else {
    form.value = {
      name: item.name,
      description: item.description || "",
      is_active: Boolean(item.is_active),
    };
  }

  modalOpen.value = true;
  error.value = "";
}

function closeModal() {
  if (saving.value) return;
  modalOpen.value = false;
}

async function saveForm() {
  if (saving.value) return;

  saving.value = true;
  error.value = "";
  success.value = "";

  try {
    if (tab.value === "packages") {
      const payload = {
        ...form.value,
        category_id: Number(form.value.category_id),
        base_price: Number(form.value.base_price),
        min_participants:
          form.value.min_participants === ""
            ? null
            : Number(form.value.min_participants),
        max_participants:
          form.value.max_participants === ""
            ? null
            : Number(form.value.max_participants),
      };

      if (editingId.value) {
        await updateAdminPackage(editingId.value, payload);
      } else {
        await createAdminPackage(payload);
      }
    } else {
      if (editingId.value) {
        await updateAdminCategory(editingId.value, form.value);
      } else {
        await createAdminCategory(form.value);
      }
    }

    success.value = editingId.value
      ? "Data berhasil diperbarui."
      : "Data berhasil ditambahkan.";

    modalOpen.value = false;
    await loadData(currentPage.value);
  } catch (err) {
    showError(err);
  } finally {
    saving.value = false;
  }
}

async function deactivate(item) {
  if (!window.confirm(`Nonaktifkan "${item.name}"? Data tidak akan dihapus.`)) {
    return;
  }

  error.value = "";
  success.value = "";

  try {
    if (tab.value === "packages") {
      await deactivateAdminPackage(item.id);
    } else {
      await deactivateAdminCategory(item.id);
    }

    await loadData(currentPage.value);
    success.value = `"${item.name}" berhasil dinonaktifkan.`;
  } catch (err) {
    showError(err);
  }
}

onMounted(() => loadData());
</script>

<template>
  <AdminLayout>
    <div class="catalog-heading">
      <div>
        <div class="roman-eyebrow">EVENT CATALOG MANAGEMENT</div>
        <h1 class="roman-title">Manajemen Paket Event</h1>
        <p>Kelola paket dan kategori event Kareem Pricing.</p>
      </div>

      <button class="catalog-button" @click="loadData(currentPage)">
        Perbarui Data
      </button>
    </div>

    <div v-if="error && !modalOpen" class="catalog-alert error">
      {{ error }}
    </div>

    <div v-if="success" class="catalog-alert success">
      {{ success }}
    </div>

    <div class="catalog-tabs">
      <button
        :class="{ active: tab === 'packages' }"
        @click="switchTab('packages')"
      >
        Paket Event
      </button>
      <button
        :class="{ active: tab === 'categories' }"
        @click="switchTab('categories')"
      >
        Kategori Event
      </button>
    </div>

    <section class="roman-card catalog-card">
      <div class="catalog-toolbar">
        <input
          v-model="search"
          class="roman-input"
          type="search"
          :placeholder="
            tab === 'packages'
              ? 'Cari paket event...'
              : 'Cari kategori event...'
          "
        />

        <button class="catalog-button primary" @click="openCreate">
          {{ tab === "packages" ? "+ Tambah Paket" : "+ Tambah Kategori" }}
        </button>
      </div>

      <p v-if="loading">Memuat data...</p>

      <div v-else class="catalog-table-wrapper">
        <table class="catalog-table">
          <thead>
            <tr v-if="tab === 'packages'">
              <th>Nama Paket</th>
              <th>Kategori</th>
              <th>Harga Dasar</th>
              <th>Peserta</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
            <tr v-else>
              <th>Nama Kategori</th>
              <th>Slug</th>
              <th>Jumlah Paket</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>

          <tbody v-if="tab === 'packages'">
            <tr v-for="item in filteredPackages" :key="item.id">
              <td>{{ item.name }}</td>
              <td>{{ item.category?.name || "-" }}</td>
              <td>{{ formatRupiah(item.base_price) }}</td>
              <td>
                {{ item.min_participants ?? "-" }}
                -
                {{ item.max_participants ?? "-" }}
              </td>
              <td>
                <span :class="item.is_active ? 'active' : 'inactive'">
                  {{ item.is_active ? "Aktif" : "Nonaktif" }}
                </span>
              </td>
              <td class="actions">
                <button class="catalog-button" @click="openEdit(item)">
                  Edit
                </button>
                <button
                  v-if="item.is_active"
                  class="catalog-button danger"
                  @click="deactivate(item)"
                >
                  Nonaktifkan
                </button>
              </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr v-for="item in filteredCategories" :key="item.id">
              <td>{{ item.name }}</td>
              <td>{{ item.slug }}</td>
              <td>{{ item.packages_count ?? 0 }}</td>
              <td>
                <span :class="item.is_active ? 'active' : 'inactive'">
                  {{ item.is_active ? "Aktif" : "Nonaktif" }}
                </span>
              </td>
              <td class="actions">
                <button class="catalog-button" @click="openEdit(item)">
                  Edit
                </button>
                <button
                  v-if="item.is_active"
                  class="catalog-button danger"
                  @click="deactivate(item)"
                >
                  Nonaktifkan
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <p
          v-if="
            tab === 'packages'
              ? !filteredPackages.length
              : !filteredCategories.length
          "
          class="catalog-empty"
        >
          Tidak ada data ditemukan.
        </p>
      </div>

      <div v-if="tab === 'packages'" class="catalog-pagination">
        <button
          class="catalog-button"
          :disabled="loading || currentPage <= 1"
          @click="loadData(currentPage - 1)"
        >
          Sebelumnya
        </button>

        <span>Halaman {{ currentPage }} / {{ lastPage }}</span>

        <button
          class="catalog-button"
          :disabled="loading || currentPage >= lastPage"
          @click="loadData(currentPage + 1)"
        >
          Berikutnya
        </button>
      </div>
    </section>

    <div v-if="modalOpen" class="catalog-modal-backdrop">
      <section class="roman-card catalog-modal">
        <h2>
          {{ editingId ? "Edit" : "Tambah" }}
          {{ tab === "packages" ? "Paket Event" : "Kategori Event" }}
        </h2>

        <div v-if="error" class="catalog-alert error">
          {{ error }}
        </div>

        <form class="catalog-form" @submit.prevent="saveForm">
          <label v-if="tab === 'packages'">
            Kategori
            <select v-model="form.category_id" class="roman-input" required>
              <option value="" disabled>Pilih kategori</option>
              <option
                v-for="category in activeCategories"
                :key="category.id"
                :value="category.id"
              >
                {{ category.name }}
              </option>
            </select>
          </label>

          <label>
            Nama
            <input
              v-model.trim="form.name"
              class="roman-input"
              required
              :maxlength="tab === 'packages' ? 150 : 100"
            />
          </label>

          <label>
            Deskripsi
            <textarea v-model="form.description" class="roman-input" rows="3" />
          </label>

          <template v-if="tab === 'packages'">
            <label>
              Harga Dasar (Rp)
              <input
                v-model="form.base_price"
                type="number"
                min="0"
                step="0.01"
                class="roman-input"
                required
              />
            </label>

            <label>
              Minimal Peserta
              <input
                v-model="form.min_participants"
                type="number"
                min="1"
                class="roman-input"
              />
            </label>

            <label>
              Maksimal Peserta
              <input
                v-model="form.max_participants"
                type="number"
                min="1"
                class="roman-input"
              />
            </label>
          </template>

          <label class="catalog-checkbox">
            <input v-model="form.is_active" type="checkbox" />
            Aktif
          </label>

          <div class="catalog-form-actions">
            <button
              type="button"
              class="catalog-button"
              :disabled="saving"
              @click="closeModal"
            >
              Batal
            </button>

            <button
              type="submit"
              class="catalog-button primary"
              :disabled="saving"
            >
              {{ saving ? "Menyimpan..." : "Simpan" }}
            </button>
          </div>
        </form>
      </section>
    </div>
  </AdminLayout>
</template>

<style scoped>
.catalog-heading,
.catalog-toolbar,
.catalog-form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.catalog-heading {
  margin-bottom: 25px;
}

.catalog-heading p {
  color: #a1a1aa;
}

.catalog-tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.catalog-tabs button,
.catalog-button {
  background: #292524;
  color: #fafafa;
  border: 1px solid #57534e;
  border-radius: 8px;
  padding: 10px 15px;
  cursor: pointer;
}

.catalog-tabs button.active,
.catalog-button.primary {
  background: #c2410c;
  border-color: #ea580c;
}

.catalog-button.danger {
  color: #fca5a5;
}

.catalog-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.catalog-card {
  padding: 25px;
}

.catalog-toolbar {
  margin-bottom: 22px;
}

.catalog-toolbar input {
  flex: 1;
  min-width: 220px;
}

.catalog-table-wrapper {
  overflow-x: auto;
}

.catalog-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.catalog-table th,
.catalog-table td {
  padding: 14px;
  border-bottom: 1px solid #44403c;
}

.catalog-table th {
  color: #a1a1aa;
  font-size: 13px;
}

.actions {
  display: flex;
  gap: 8px;
}

.active {
  color: #86efac;
}

.inactive {
  color: #fca5a5;
}

.catalog-empty {
  text-align: center;
  padding: 35px;
  color: #a1a1aa;
}

.catalog-pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}

.catalog-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.catalog-modal {
  width: min(600px, 100%);
  max-height: 90vh;
  overflow-y: auto;
  padding: 26px;
}

.catalog-form {
  display: grid;
  gap: 16px;
  margin-top: 20px;
}

.catalog-form label {
  display: grid;
  gap: 7px;
}

.catalog-form .roman-input {
  width: 100%;
}

.catalog-checkbox {
  display: flex !important;
  flex-direction: row;
  align-items: center;
}

.catalog-form-actions {
  justify-content: flex-end;
  margin-top: 10px;
}

.catalog-alert {
  padding: 13px;
  border-radius: 8px;
  margin-bottom: 18px;
}

.catalog-alert.error {
  background: #450a0a;
  color: #fecaca;
}

.catalog-alert.success {
  background: #14532d;
  color: #bbf7d0;
}
</style>
