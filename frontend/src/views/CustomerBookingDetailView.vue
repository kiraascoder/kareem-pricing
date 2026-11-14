<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import CustomerLayout from "../layouts/CustomerLayout.vue";
import CustomerPriceQuote from "../components/CustomerPriceQuote.vue";
import CustomerPaymentForm from "../components/CustomerPaymentForm.vue";
import { getBooking, extractData } from "../services/bookingApi";
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

const loading = ref(true);
const error = ref("");
const quoteError = ref("");
const paymentError = ref("");

const canPay = computed(
  () =>
    booking.value?.status === "awaiting_payment" &&
    quote.value?.status === "accepted",
);

async function loadData() {
  loading.value = true;
  error.value = "";
  quoteError.value = "";
  paymentError.value = "";

  try {
    const bookingResponse = await getBooking(route.params.id);
    const data = extractData(bookingResponse);
    booking.value = data?.booking ?? data;
  } catch (err) {
    error.value = err.response?.data?.message || "Gagal memuat booking.";
    loading.value = false;
    return;
  }

  try {
    const response = await getPriceQuote(route.params.id);
    const data = unwrap(response);
    quote.value = data?.price_quote ?? data?.quote ?? data;
  } catch (err) {
    quote.value = null;

    if (err.response?.status !== 404) {
      quoteError.value =
        err.response?.data?.message || "Gagal memuat penawaran harga.";
    }
  }

  try {
    const response = await getPayments(route.params.id);
    payments.value = unwrapList(response);
  } catch (err) {
    paymentError.value =
      err.response?.data?.message || "Gagal memuat riwayat pembayaran.";
  } finally {
    loading.value = false;
  }
}

onMounted(loadData);
</script>

<template>
  <CustomerLayout>
    <RouterLink to="/customer/bookings" class="back">
      ← Kembali ke Booking Saya
    </RouterLink>

    <div class="roman-eyebrow">EVENT RESERVATION</div>
    <h1 class="roman-title">Detail Booking</h1>

    <p v-if="loading" class="roman-card message">Memuat informasi booking...</p>

    <p v-else-if="error" class="roman-card message error">
      {{ error }}
    </p>

    <div v-else-if="booking" class="detail-grid">
      <div class="main-column">
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
            <strong class="orange">{{ booking.status }}</strong>
          </div>

          <div class="info-row">
            <span>Tanggal Event</span>
            <strong>{{ booking.event_date || "-" }}</strong>
          </div>

          <div class="info-row">
            <span>Jumlah Peserta</span>
            <strong>
              {{ booking.participants_count ?? booking.participants ?? "-" }}
            </strong>
          </div>
        </section>

        <p v-if="quoteError" class="message error">
          {{ quoteError }}
        </p>

        <CustomerPriceQuote
          v-else
          :booking-id="booking.id"
          :quote="quote"
          @updated="loadData"
        />

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
          <p>Booking Anda sudah berstatus paid.</p>
        </section>
      </div>

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
}

h1 {
  margin: 12px 0 28px;
}

.detail-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.7fr) minmax(250px, 1fr);
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
  gap: 20px;
  padding: 16px 0;
  border-bottom: 1px solid #303036;
}

.info-row span {
  color: #a1a1aa;
  font-size: 13px;
}

.info-row strong {
  font-size: 13px;
  text-align: right;
}

.orange {
  color: #fb923c;
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
</style>
