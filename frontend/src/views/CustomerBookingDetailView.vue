<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";

import CustomerLayout from "../layouts/CustomerLayout.vue";
import CustomerPriceQuote from "../components/CustomerPriceQuote.vue";
import CustomerPaymentForm from "../components/CustomerPaymentForm.vue";

import {
  getBooking,
  getPriceEstimate,
  extractData,
  extractApiError,
} from "../services/bookingApi";

import {
  getPriceQuote,
  getPayments,
  unwrap,
  unwrapList,
} from "../services/paymentApi";

const route = useRoute();

const booking = ref(null);
const quote = ref(null);
const payments = ref([]);
const estimate = ref(null);

const loading = ref(true);
const error = ref("");
const quoteError = ref("");
const paymentError = ref("");
const estimateError = ref("");

const canPay = computed(
  () =>
    booking.value?.status === "awaiting_payment" &&
    quote.value?.status === "accepted",
);

const statusLabels = {
  pending: "Menunggu Review",
  reviewed: "Sudah Direview",
  approved: "Disetujui",
  rejected: "Ditolak",
  quoted: "Penawaran Tersedia",
  awaiting_customer: "Menunggu Konfirmasi",
  awaiting_payment: "Menunggu Pembayaran",
  paid: "Pembayaran Terverifikasi",
  completed: "Selesai",
  cancelled: "Dibatalkan",
};

const statusLabel = computed(() => {
  const status = booking.value?.status;

  if (!status) return "-";

  return statusLabels[status] || status.replace(/_/g, " ");
});

const packageName = computed(() => {
  const item = booking.value;

  if (!item) return "-";

  return (
    item.event_package?.name ||
    item.package?.name ||
    item.eventPackage?.name ||
    "-"
  );
});

function formatTanggal(value) {
  if (!value) return "-";

  const datePart = String(value).slice(0, 10);
  const match = datePart.match(/^(\d{4})-(\d{2})-(\d{2})$/);

  if (!match) return "-";

  const year = Number(match[1]);
  const month = Number(match[2]);
  const day = Number(match[3]);

  const date = new Date(Date.UTC(year, month - 1, day));

  if (
    date.getUTCFullYear() !== year ||
    date.getUTCMonth() !== month - 1 ||
    date.getUTCDate() !== day
  ) {
    return "-";
  }

  return new Intl.DateTimeFormat("id-ID", {
    day: "numeric",
    month: "long",
    year: "numeric",
    timeZone: "UTC",
  }).format(date);
}

function formatRupiah(value) {
  if (value === null || value === undefined || value === "") {
    return "-";
  }

  const number = Number(value);

  if (!Number.isFinite(number)) {
    return "-";
  }

  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(number);
}

const estimateData = computed(() => {
  const data = estimate.value;

  if (!data || typeof data !== "object") {
    return null;
  }

  return data.price_estimate ?? data.estimate ?? data.recommendation ?? data;
});

const estimatePrice = computed(() => {
  const data = estimateData.value;

  if (!data) return null;

  return (
    data.recommended_price ??
    data.estimated_price ??
    data.predicted_price ??
    data.final_price ??
    null
  );
});

const estimateMethod = computed(() => {
  const data = estimateData.value;

  return data?.method ?? data?.model ?? data?.algorithm ?? null;
});

async function loadData() {
  loading.value = true;

  error.value = "";
  quoteError.value = "";
  paymentError.value = "";
  estimateError.value = "";

  booking.value = null;
  quote.value = null;
  payments.value = [];
  estimate.value = null;

  const bookingId = route.params.id;

  try {
    const response = await getBooking(bookingId);
    const data = extractData(response);

    booking.value = data?.booking ?? data;

    if (!booking.value?.id) {
      throw new Error("Data booking tidak ditemukan dalam respons API.");
    }
  } catch (err) {
    error.value = extractApiError(err, "Gagal memuat booking.");

    loading.value = false;
    return;
  }

  const results = await Promise.allSettled([
    getPriceEstimate(bookingId),
    getPriceQuote(bookingId),
    getPayments(bookingId),
  ]);

  // Estimasi AI
  if (results[0].status === "fulfilled") {
    const data = extractData(results[0].value);

    estimate.value = data;
  } else {
    const err = results[0].reason;

    if (err.response?.status !== 404) {
      estimateError.value = extractApiError(
        err,
        "Gagal memuat estimasi harga AI.",
      );
    }
  }

  // Penawaran harga admin
  if (results[1].status === "fulfilled") {
    const data = unwrap(results[1].value);

    quote.value = data?.price_quote ?? data?.quote ?? data;
  } else {
    const err = results[1].reason;

    if (err.response?.status !== 404) {
      quoteError.value = extractApiError(err, "Gagal memuat penawaran harga.");
    }
  }

  // Riwayat pembayaran
  if (results[2].status === "fulfilled") {
    payments.value = unwrapList(results[2].value);
  } else {
    paymentError.value = extractApiError(
      results[2].reason,
      "Gagal memuat riwayat pembayaran.",
    );
  }

  loading.value = false;
}

onMounted(loadData);

watch(
  () => route.params.id,
  () => loadData(),
);
</script>

<template>
  <CustomerLayout>
    <RouterLink to="/customer/bookings" class="back">
      ← Kembali ke Booking Saya
    </RouterLink>

    <div class="roman-eyebrow">EVENT RESERVATION</div>

    <h1 class="roman-title">Detail Booking</h1>

    <p v-if="loading" class="roman-card message">Memuat informasi booking...</p>

    <div v-else-if="error" class="roman-card message error">
      <p>{{ error }}</p>

      <button class="refresh-button" @click="loadData">Coba Lagi</button>
    </div>

    <div v-else-if="booking" class="detail-grid">
      <div class="main-column">
        <!-- BOOKING INFORMATION -->
        <section class="roman-card section-card">
          <div class="roman-eyebrow">BOOKING INFORMATION</div>

          <h2>Informasi Pemesanan</h2>

          <div class="info-row">
            <span>Kode Booking</span>
            <strong>
              {{ booking.booking_code || booking.id }}
            </strong>
          </div>

          <div class="info-row">
            <span>Status</span>

            <strong class="orange">
              {{ statusLabel }}
            </strong>
          </div>

          <div class="info-row">
            <span>Paket Event</span>

            <strong>
              {{ packageName }}
            </strong>
          </div>

          <div class="info-row">
            <span>Tanggal Event</span>

            <strong>
              {{ formatTanggal(booking.event_date) }}
            </strong>
          </div>

          <div class="info-row">
            <span>Jumlah Peserta</span>

            <strong>
              {{ booking.participants ?? "-" }}
            </strong>
          </div>

          <div class="notes-section">
            <span>Catatan Customer</span>

            <p>
              {{ booking.customer_notes || "Tidak ada catatan tambahan." }}
            </p>
          </div>
        </section>

        <!-- AI PRICE ESTIMATION -->
        <section class="roman-card section-card">
          <div class="roman-eyebrow">AI PRICE ESTIMATION</div>

          <h2>Estimasi Harga AI</h2>

          <p v-if="estimateError" class="error">
            {{ estimateError }}
          </p>

          <template v-else-if="estimateData">
            <div v-if="estimatePrice !== null" class="estimate-result">
              <span>Rekomendasi Harga</span>

              <strong>
                {{ formatRupiah(estimatePrice) }}
              </strong>
            </div>

            <div v-if="estimateMethod" class="info-row">
              <span>Metode AI</span>
              <strong>
                {{ estimateMethod }}
              </strong>
            </div>

            <p v-if="estimatePrice === null" class="muted">
              Data estimasi tersedia, tetapi format nilai harga belum dikenali.
            </p>

            <p class="estimate-notice">
              Estimasi ini merupakan rekomendasi sistem AI dan bukan penawaran
              harga final dari admin.
            </p>
          </template>

          <p v-else class="muted">
            Estimasi harga AI belum tersedia untuk booking ini.
          </p>
        </section>

        <!-- PRICE QUOTATION -->
        <p v-if="quoteError" class="message error">
          {{ quoteError }}
        </p>

        <CustomerPriceQuote
          v-else
          :booking-id="booking.id"
          :quote="quote"
          @updated="loadData"
        />

        <!-- PAYMENT -->
        <p v-if="paymentError" class="message error">
          {{ paymentError }}
        </p>

        <CustomerPaymentForm
          v-else-if="canPay"
          :booking-id="booking.id"
          :payments="payments"
          @updated="loadData"
        />

        <section
          v-else-if="booking.status === 'paid'"
          class="roman-card section-card"
        >
          <div class="roman-eyebrow">PAYMENT COMPLETE</div>

          <h2 class="orange">Pembayaran Terverifikasi</h2>

          <p class="muted">Booking Anda sudah berstatus paid.</p>
        </section>
      </div>

      <!-- SIDEBAR -->
      <aside class="roman-card section-card sidebar-info">
        <div class="roman-eyebrow">YOUR EVENT JOURNEY</div>

        <h2>Proses Booking</h2>

        <div class="step">01 — Booking dibuat</div>

        <div class="step">02 — Review admin</div>

        <div class="step">03 — Penawaran harga</div>

        <div class="step">04 — Konfirmasi pembayaran</div>

        <div class="step">05 — Verifikasi admin</div>

        <button class="refresh-button" :disabled="loading" @click="loadData">
          Perbarui Status
        </button>
      </aside>
    </div>
  </CustomerLayout>
</template>

<style scoped>
.back {
  display: inline-block;
  margin-bottom: 28px;
  color: #fb923c;
  font-size: 13px;
  text-decoration: none;
}

h1 {
  margin: 12px 0 28px;
}

.detail-grid {
  display: grid;
  grid-template-columns:
    minmax(0, 1.7fr)
    minmax(250px, 1fr);
  gap: 22px;
  align-items: start;
}

.main-column {
  display: grid;
  gap: 22px;
}

.section-card {
  padding: 26px;
}

.section-card h2 {
  margin: 14px 0 24px;
  font-size: 19px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
  padding: 16px 0;
  border-bottom: 1px solid #303036;
}

.info-row span,
.notes-section > span {
  color: #a1a1aa;
  font-size: 13px;
}

.info-row strong {
  font-size: 13px;
  text-align: right;
  overflow-wrap: anywhere;
}

.orange {
  color: #fb923c;
}

.notes-section {
  padding-top: 20px;
}

.notes-section p {
  margin: 12px 0 0;
  padding: 16px;
  border: 1px solid #303036;
  border-radius: 8px;
  background: #111113;
  color: #d4d4d8;
  font-size: 13px;
  line-height: 1.8;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.estimate-result {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 20px;
  border: 1px solid #f9731633;
  border-radius: 8px;
  background: #f973160b;
}

.estimate-result span {
  color: #a1a1aa;
  font-size: 12px;
}

.estimate-result strong {
  color: #fb923c;
  font-size: clamp(22px, 3vw, 30px);
}

.estimate-notice {
  margin-top: 20px;
  padding: 14px;
  border-left: 3px solid #f97316;
  background: #f973160b;
  color: #fdba74;
  font-size: 12px;
  line-height: 1.8;
}

.muted {
  color: #a1a1aa;
  font-size: 13px;
  line-height: 1.8;
}

.step {
  padding: 16px 0;
  border-bottom: 1px solid #303036;
  color: #d4d4d8;
  font-size: 12px;
}

.refresh-button {
  width: 100%;
  margin-top: 24px;
  padding: 12px;
  border: 1px solid #f97316;
  border-radius: 9px;
  background: transparent;
  color: #fb923c;
  cursor: pointer;
  transition: 0.2s;
}

.refresh-button:hover {
  background: #f9731615;
}

.refresh-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.message {
  padding: 22px;
  color: #a1a1aa;
}

.error {
  color: #fca5a5;
}

@media (max-width: 900px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .section-card {
    padding: 20px;
  }

  .info-row {
    gap: 12px;
  }
}
</style>
