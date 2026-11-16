<script setup>
import { computed, onMounted, ref } from "vue";
import AdminLayout from "../layouts/AdminLayout.vue";

import {
  getAdminBookings,
  unwrapAdminBookingList,
} from "../services/adminBookingApi";

const bookings = ref([]);
const loading = ref(true);
const error = ref("");

const search = ref("");
const selectedStatus = ref("");

const statuses = [
  { value: "", label: "Semua Status" },
  { value: "pending", label: "Pending" },
  { value: "reviewed", label: "Reviewed" },
  { value: "awaiting_payment", label: "Menunggu Pembayaran" },
  { value: "paid", label: "Paid" },
  { value: "completed", label: "Completed" },
  { value: "cancelled", label: "Cancelled" },
];

const filteredBookings = computed(() => {
  const keyword = search.value.trim().toLowerCase();

  return bookings.value.filter((booking) => {
    const searchable = [
      booking.booking_code,
      booking.id,
      booking.customer?.name,
      booking.user?.name,
      booking.event_package?.name,
      booking.package?.name,
    ]
      .filter(Boolean)
      .join(" ")
      .toLowerCase();

    const matchesSearch = !keyword || searchable.includes(keyword);

    const matchesStatus =
      !selectedStatus.value || booking.status === selectedStatus.value;

    return matchesSearch && matchesStatus;
  });
});

const totalBookings = computed(() => bookings.value.length);

const pendingBookings = computed(
  () => bookings.value.filter((item) => item.status === "pending").length,
);

const awaitingPayments = computed(
  () =>
    bookings.value.filter((item) => item.status === "awaiting_payment").length,
);

function formatDate(value) {
  if (!value) return "-";

  const datePart = String(value).slice(0, 10);
  const match = datePart.match(/^(\d{4})-(\d{2})-(\d{2})$/);

  if (!match) return "-";

  const date = new Date(`${datePart}T00:00:00Z`);

  if (Number.isNaN(date.getTime())) return "-";

  return new Intl.DateTimeFormat("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
    timeZone: "UTC",
  }).format(date);
}

function statusLabel(status) {
  const labels = {
    pending: "Menunggu Review",
    reviewed: "Direview",
    awaiting_payment: "Menunggu Pembayaran",
    paid: "Dibayar",
    completed: "Selesai",
    cancelled: "Dibatalkan",
  };

  return labels[status] || String(status || "-").replaceAll("_", " ");
}

async function loadBookings() {
  loading.value = true;
  error.value = "";

  try {
    const response = await getAdminBookings();

    bookings.value = unwrapAdminBookingList(response);
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      err.message ||
      "Gagal memuat data booking.";
  } finally {
    loading.value = false;
  }
}

onMounted(loadBookings);
</script>

<template>
  <AdminLayout>
    <div class="page-heading">
      <div>
        <div class="roman-eyebrow">BOOKING MANAGEMENT</div>

        <h1 class="roman-title">Manajemen Booking</h1>

        <p>Kelola permintaan booking event dari customer.</p>
      </div>

      <button class="refresh-button" :disabled="loading" @click="loadBookings">
        {{ loading ? "Memuat..." : "Perbarui Data" }}
      </button>
    </div>

    <div class="stats-grid">
      <div class="roman-card stat-card">
        <span>Total Booking</span>
        <strong>{{ loading ? "..." : totalBookings }}</strong>
      </div>

      <div class="roman-card stat-card">
        <span>Menunggu Review</span>
        <strong>{{ loading ? "..." : pendingBookings }}</strong>
      </div>

      <div class="roman-card stat-card">
        <span>Menunggu Pembayaran</span>
        <strong>{{ loading ? "..." : awaitingPayments }}</strong>
      </div>
    </div>

    <section class="roman-card list-card">
      <div class="list-heading">
        <div>
          <div class="roman-eyebrow">EVENT RESERVATIONS</div>
          <h2>Daftar Booking</h2>
        </div>
      </div>

      <div class="toolbar">
        <input
          v-model="search"
          type="search"
          class="roman-input search-input"
          placeholder="Cari kode booking, customer, atau paket..."
        />

        <select v-model="selectedStatus" class="roman-input status-select">
          <option
            v-for="item in statuses"
            :key="item.value"
            :value="item.value"
          >
            {{ item.label }}
          </option>
        </select>
      </div>

      <div v-if="loading" class="empty">Memuat daftar booking...</div>

      <div v-else-if="error" class="error-box">
        {{ error }}
      </div>

      <div v-else-if="!filteredBookings.length" class="empty">
        Tidak ada booking yang sesuai.
      </div>

      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Kode Booking</th>
              <th>Customer</th>
              <th>Tanggal Event</th>
              <th>Peserta</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="item in filteredBookings" :key="item.id">
              <td class="booking-code">
                {{ item.booking_code || `#${item.id}` }}
              </td>

              <td>
                {{ item.customer?.name || item.user?.name || "-" }}
              </td>

              <td>
                {{ formatDate(item.event_date) }}
              </td>

              <td>
                {{ item.participants ?? "-" }}
              </td>

              <td>
                <span class="status-badge">
                  {{ statusLabel(item.status) }}
                </span>
              </td>

              <td>
                <RouterLink
                  :to="`/admin/bookings/${item.id}`"
                  class="detail-link"
                >
                  Lihat Detail →
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p class="table-note">
        Menampilkan {{ filteredBookings.length }} dari
        {{ totalBookings }} booking yang dimuat.
      </p>
    </section>
  </AdminLayout>
</template>

<style scoped>
.page-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 28px;
}

.page-heading h1 {
  margin: 12px 0;
  font-size: 30px;
}

.page-heading p {
  color: #a1a1aa;
  font-size: 13px;
}

.refresh-button {
  padding: 12px 18px;
  border: 1px solid #f97316;
  border-radius: 9px;
  background: transparent;
  color: #fb923c;
  cursor: pointer;
}

.refresh-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px;
  margin-bottom: 25px;
}

.stat-card {
  padding: 25px;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.stat-card span {
  color: #a1a1aa;
  font-size: 12px;
}

.stat-card strong {
  color: #fb923c;
  font-size: 30px;
}

.list-card {
  overflow: hidden;
}

.list-heading {
  padding: 25px 25px 0;
}

.list-heading h2 {
  margin: 12px 0;
  font-size: 19px;
}

.toolbar {
  display: flex;
  gap: 15px;
  padding: 20px 25px;
}

.search-input {
  flex: 1;
}

.status-select {
  width: 220px;
}

.table-wrapper {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

th,
td {
  padding: 17px 20px;
  border-bottom: 1px solid #303036;
  font-size: 12px;
}

th {
  color: #a1a1aa;
  font-weight: 600;
}

td {
  color: #d4d4d8;
}

.booking-code {
  color: #fafaf9;
  font-weight: 600;
}

.status-badge {
  display: inline-block;
  padding: 7px 10px;
  border-radius: 7px;
  background: #f9731618;
  color: #fb923c;
  white-space: nowrap;
}

.detail-link {
  color: #fb923c;
  text-decoration: none;
  white-space: nowrap;
}

.detail-link:hover {
  text-decoration: underline;
}

.empty {
  padding: 45px;
  color: #a1a1aa;
  text-align: center;
  font-size: 13px;
}

.error-box {
  margin: 20px 25px;
  padding: 15px;
  border: 1px solid #7f1d1d;
  border-radius: 8px;
  color: #fca5a5;
  font-size: 13px;
}

.table-note {
  padding: 10px 25px 20px;
  color: #71717a;
  font-size: 11px;
}

@media (max-width: 900px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .toolbar {
    flex-direction: column;
  }

  .status-select {
    width: 100%;
  }

  .page-heading {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
