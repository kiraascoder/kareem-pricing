<script setup>
import { onMounted, ref } from "vue";
import AdminLayout from "../layouts/AdminLayout.vue";
import AdminStatCard from "../components/AdminStatCard.vue";
import BookingTrendChart from "../components/BookingTrendChart.vue";
import {
  getAdminDashboard,
  getBookingTrend,
  unwrapAdminData,
} from "../services/adminApi";

const loading = ref(true);
const error = ref("");
const trendError = ref("");

const stats = ref({
  totalBookings: null,
  pendingBookings: null,
  pendingPayments: null,
  paidBookings: null,
});

const recentBookings = ref([]);
const trend = ref([]);
const days = ref(30);

function readNumber(value) {
  if (value === null || value === undefined || value === "") {
    return null;
  }
  const number = Number(value);
  return Number.isFinite(number) ? number : null;
}

function normalizeDashboard(data) {
  // Sesuaikan mapping ini dengan JSON AdminDashboardController.
  const summary = data.summary ?? data.stats ?? data;

  stats.value = {
    totalBookings: readNumber(summary.total_bookings ?? summary.bookings_total),
    pendingBookings: readNumber(
      summary.pending_bookings ?? summary.bookings_pending,
    ),
    pendingPayments: readNumber(
      summary.pending_payments ?? summary.pending_payment_verifications,
    ),
    paidBookings: readNumber(summary.paid_bookings ?? summary.bookings_paid),
  };

  const recent = data.recent_bookings ?? data.latest_bookings ?? [];
  recentBookings.value = Array.isArray(recent)
    ? recent
    : Array.isArray(recent.data)
      ? recent.data
      : [];
}

async function loadDashboard() {
  loading.value = true;
  error.value = "";

  try {
    const response = await getAdminDashboard();
    normalizeDashboard(unwrapAdminData(response));
  } catch (err) {
    error.value =
      err.response?.data?.message || "Gagal mengambil statistik dashboard.";
  } finally {
    loading.value = false;
  }
}

async function loadTrend() {
  trendError.value = "";
  trend.value = [];

  try {
    const response = await getBookingTrend(days.value);
    const payload = unwrapAdminData(response);

    const rows = Array.isArray(payload)
      ? payload
      : (payload.trend ?? payload.data ?? payload.daily_bookings ?? []);

    if (!Array.isArray(rows)) {
      throw new Error("Format data tren booking tidak dikenali.");
    }

    trend.value = rows.map((row) => ({
      date: row.date ?? row.day ?? "",
      total: Number(row.total ?? row.count ?? row.bookings_count ?? 0),
    }));
  } catch (err) {
    trendError.value =
      err.response?.data?.message || err.message || "Gagal memuat grafik.";
  }
}

async function refresh() {
  await Promise.all([loadDashboard(), loadTrend()]);
}

onMounted(refresh);
</script>

<template>
  <AdminLayout>
    <div class="dashboard-heading">
      <div>
        <div class="roman-eyebrow">ADMINISTRATIVE OVERVIEW</div>
        <h1 class="roman-title">Dashboard Overview</h1>
        <p>Pantau aktivitas booking dan pembayaran Kareem Pricing.</p>
      </div>

      <button class="refresh-button" @click="refresh">Perbarui Data</button>
    </div>

    <div v-if="error" class="error-box" role="alert">
      {{ error }}
    </div>

    <div class="stats-grid">
      <AdminStatCard
        title="Total Booking"
        :value="stats.totalBookings"
        description="Jumlah seluruh booking"
        symbol="▦"
        :loading="loading"
      />

      <AdminStatCard
        title="Menunggu Review"
        :value="stats.pendingBookings"
        description="Booking yang perlu diperiksa"
        symbol="◷"
        :loading="loading"
      />

      <AdminStatCard
        title="Verifikasi Pembayaran"
        :value="stats.pendingPayments"
        description="Bukti transfer menunggu admin"
        symbol="◇"
        :loading="loading"
      />

      <AdminStatCard
        title="Booking Dibayar"
        :value="stats.paidBookings"
        description="Booking dengan status paid"
        symbol="✓"
        :loading="loading"
      />
    </div>

    <div class="analytics-header">
      <h2>Analitik Booking</h2>

      <select
        v-model.number="days"
        class="roman-input period-select"
        @change="loadTrend"
      >
        <option :value="7">7 Hari</option>
        <option :value="30">30 Hari</option>
        <option :value="90">90 Hari</option>
      </select>
    </div>

    <p v-if="trendError" class="error-box" role="alert">
      {{ trendError }}
    </p>

    <BookingTrendChart :data="trend" />

    <div class="analytics-header">
      <h2>Booking Terbaru</h2>
      <RouterLink to="/admin/bookings" class="view-all">
        Lihat Semua →
      </RouterLink>
    </div>

    <section class="roman-card recent-card">
      <div v-if="loading" class="empty">Memuat booking terbaru...</div>

      <div v-else-if="!recentBookings.length" class="empty">
        Belum ada data booking terbaru yang tersedia.
      </div>

      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Booking</th>
              <th>Tanggal Event</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="booking in recentBookings" :key="booking.id">
              <td>
                {{ booking.booking_code || `#${booking.id}` }}
              </td>
              <td>{{ booking.event_date || "-" }}</td>
              <td>
                <span class="status">{{ booking.status }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </AdminLayout>
</template>

<style scoped>
.dashboard-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 28px;
}

.dashboard-heading h1 {
  margin: 12px 0;
  font-size: clamp(23px, 3vw, 30px);
}

.dashboard-heading p {
  color: #a1a1aa;
  font-size: 13px;
}

.refresh-button {
  padding: 12px 16px;
  border: 1px solid #f97316;
  border-radius: 9px;
  background: transparent;
  color: #fb923c;
  white-space: nowrap;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 18px;
}

.analytics-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  margin: 32px 0 18px;
}

.analytics-header h2 {
  margin: 0;
  font-size: 17px;
}

.period-select {
  width: 140px;
}

.view-all {
  color: #fb923c;
  font-size: 12px;
}

.recent-card {
  overflow: hidden;
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
  padding: 18px 22px;
  border-bottom: 1px solid #303036;
  font-size: 12px;
}

th {
  color: #a1a1aa;
  font-weight: 600;
}

tr:last-child td {
  border-bottom: 0;
}

.status {
  display: inline-block;
  padding: 7px 10px;
  border-radius: 7px;
  background: #f9731618;
  color: #fb923c;
  white-space: nowrap;
}

.empty {
  padding: 35px;
  color: #a1a1aa;
  text-align: center;
  font-size: 13px;
}

.error-box {
  margin-bottom: 20px;
  padding: 15px;
  border: 1px solid #7f1d1d;
  border-radius: 9px;
  color: #fca5a5;
  font-size: 13px;
}

@media (max-width: 1150px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 550px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .dashboard-heading {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
