<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import CustomerLayout from "./CustomerLayout.vue";

import {
  getBookings,
  getBookingPayments,
  extractCollection,
  extractApiError,
} from "../services/bookingApi";

const bookings = ref([]);
const paymentRecords = ref([]);
const loading = ref(false);
const error = ref("");
const search = ref("");
const statusFilter = ref("");

const statuses = [
  { value: "", label: "Semua Status" },
  { value: "pending_verification", label: "Menunggu Verifikasi" },
  { value: "verified", label: "Terverifikasi" },
  { value: "rejected", label: "Ditolak" },
];

const filteredPayments = computed(() => {
  const keyword = search.value.trim().toLowerCase();

  return paymentRecords.value.filter((payment) => {
    const matchesSearch =
      !keyword ||
      [payment.booking_code, payment.booking_id, payment.id]
        .filter((value) => value !== null && value !== undefined)
        .join(" ")
        .toLowerCase()
        .includes(keyword);

    const matchesStatus =
      !statusFilter.value || payment.status === statusFilter.value;

    return matchesSearch && matchesStatus;
  });
});

const pendingCount = computed(
  () =>
    paymentRecords.value.filter(
      (payment) => payment.status === "pending_verification",
    ).length,
);

const verifiedCount = computed(
  () =>
    paymentRecords.value.filter((payment) => payment.status === "verified")
      .length,
);

const rejectedCount = computed(
  () =>
    paymentRecords.value.filter((payment) => payment.status === "rejected")
      .length,
);

function formatRupiah(value) {
  if (value === null || value === undefined || value === "") {
    return "-";
  }

  const amount = Number(value);

  if (!Number.isFinite(amount)) return "-";

  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(amount);
}

function formatDate(value) {
  if (!value) return "-";

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) return "-";

  return new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date);
}

function statusLabel(status) {
  const labels = {
    pending_verification: "Menunggu Verifikasi",
    verified: "Terverifikasi",
    rejected: "Ditolak",
  };

  return labels[status] || status || "-";
}

async function loadPayments() {
  loading.value = true;
  error.value = "";

  try {
    // Mengambil booking milik pengguna yang login.
    const bookingResponse = await getBookings();

    bookings.value = extractCollection(bookingResponse);

    // Mengambil pembayaran dari setiap booking.
    const results = await Promise.allSettled(
      bookings.value.map(async (booking) => {
        const response = await getBookingPayments(booking.id);

        return extractCollection(response).map((payment) => ({
          ...payment,
          booking_id: booking.id,
          booking_code: booking.booking_code,
          booking_status: booking.status,
        }));
      }),
    );

    const failed = results.filter((result) => result.status === "rejected");

    paymentRecords.value = results
      .filter((result) => result.status === "fulfilled")
      .flatMap((result) => result.value)
      .sort(
        (a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0),
      );

    if (failed.length) {
      error.value =
        `${failed.length} booking gagal dimuat pembayaran. ` +
        "Silakan perbarui data.";
    }
  } catch (err) {
    paymentRecords.value = [];
    error.value = extractApiError(err, "Gagal memuat riwayat pembayaran.");
  } finally {
    loading.value = false;
  }
}

onMounted(loadPayments);
</script>

<template>
  <CustomerLayout>
    <div class="payments-heading">
      <div>
        <div class="roman-eyebrow">KAREEM PRICING</div>
        <h1 class="roman-title">Pembayaran</h1>
        <p>Pantau riwayat dan status verifikasi pembayaran Anda.</p>
      </div>

      <button class="payment-button" :disabled="loading" @click="loadPayments">
        {{ loading ? "Memuat..." : "Perbarui Data" }}
      </button>
    </div>

    <div v-if="error" class="payment-alert">
      {{ error }}
    </div>

    <div class="payment-stats">
      <div class="roman-card stat-card">
        <span>Menunggu Verifikasi</span>
        <strong>{{ pendingCount }}</strong>
      </div>

      <div class="roman-card stat-card">
        <span>Terverifikasi</span>
        <strong>{{ verifiedCount }}</strong>
      </div>

      <div class="roman-card stat-card">
        <span>Ditolak</span>
        <strong>{{ rejectedCount }}</strong>
      </div>
    </div>

    <section class="roman-card payment-card">
      <div class="section-heading">
        <div>
          <div class="roman-eyebrow">PAYMENT HISTORY</div>
          <h2>Riwayat Pembayaran</h2>
        </div>
      </div>

      <div class="payment-toolbar">
        <input
          v-model="search"
          type="search"
          class="roman-input"
          placeholder="Cari kode booking atau ID pembayaran..."
        />

        <select v-model="statusFilter" class="roman-input">
          <option
            v-for="item in statuses"
            :key="item.value"
            :value="item.value"
          >
            {{ item.label }}
          </option>
        </select>
      </div>

      <div v-if="loading" class="payment-empty">
        Memuat riwayat pembayaran...
      </div>

      <div v-else-if="!filteredPayments.length" class="payment-empty">
        Tidak ada pembayaran ditemukan.
      </div>

      <div v-else class="payment-table-wrapper">
        <table class="payment-table">
          <thead>
            <tr>
              <th>Kode Booking</th>
              <th>Nominal</th>
              <th>Tanggal</th>
              <th>Status</th>
              <th>Catatan Admin</th>
              <th>Aksi</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="payment in filteredPayments" :key="payment.id">
              <td>{{ payment.booking_code || "-" }}</td>

              <td>{{ formatRupiah(payment.amount) }}</td>

              <td>{{ formatDate(payment.created_at) }}</td>

              <td>
                <span class="payment-status" :class="payment.status">
                  {{ statusLabel(payment.status) }}
                </span>
              </td>

              <td>{{ payment.admin_notes || "-" }}</td>

              <td>
                <RouterLink
                  :to="`/customer/bookings/${payment.booking_id}`"
                  class="payment-link"
                >
                  Detail Booking
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </CustomerLayout>
</template>

<style scoped>
.payments-heading,
.section-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
}

.payments-heading {
  margin-bottom: 25px;
}

.payments-heading p {
  color: #a1a1aa;
  line-height: 1.8;
}

.payment-stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  padding: 22px;
  display: grid;
  gap: 12px;
}

.stat-card span {
  color: #a1a1aa;
  font-size: 13px;
}

.stat-card strong {
  font-size: 30px;
  color: #fafafa;
}

.payment-card {
  padding: 26px;
}

.payment-card h2 {
  margin: 10px 0 22px;
}

.payment-toolbar {
  display: flex;
  gap: 12px;
  margin-bottom: 22px;
  flex-wrap: wrap;
}

.payment-toolbar input {
  flex: 1;
  min-width: 220px;
}

.payment-button {
  background: #292524;
  border: 1px solid #57534e;
  border-radius: 8px;
  padding: 10px 15px;
  color: #fafafa;
  cursor: pointer;
}

.payment-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.payment-table-wrapper {
  overflow-x: auto;
}

.payment-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.payment-table th,
.payment-table td {
  padding: 15px 12px;
  border-bottom: 1px solid #44403c;
  white-space: nowrap;
}

.payment-table th {
  color: #a1a1aa;
  font-size: 13px;
}

.payment-status {
  display: inline-block;
  padding: 6px 11px;
  border-radius: 20px;
  background: #292524;
  font-size: 12px;
}

.payment-status.pending_verification {
  color: #fdba74;
}

.payment-status.verified {
  color: #86efac;
}

.payment-status.rejected {
  color: #fca5a5;
}

.payment-link {
  color: #fb923c;
  text-decoration: none;
}

.payment-link:hover {
  text-decoration: underline;
}

.payment-empty {
  text-align: center;
  padding: 40px 0;
  color: #a1a1aa;
}

.payment-alert {
  padding: 14px;
  margin-bottom: 18px;
  border-radius: 8px;
  background: #450a0a;
  color: #fecaca;
}

@media (max-width: 768px) {
  .payment-stats {
    grid-template-columns: 1fr;
  }
}
</style>
