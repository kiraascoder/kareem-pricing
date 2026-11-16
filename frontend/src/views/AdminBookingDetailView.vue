<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";

import AdminLayout from "../layouts/AdminLayout.vue";

import {
  getAdminBooking,
  reviewAdminBooking,
  createAdminPriceQuote,
  unwrapAdminBooking,
} from "../services/adminBookingApi";

import {
  getPriceEstimate,
  extractData,
  extractApiError,
} from "../services/bookingApi";

const route = useRoute();

const booking = ref(null);
const estimate = ref(null);

const loading = ref(true);
const processing = ref(false);
const sendingQuote = ref(false);

const error = ref("");
const estimateError = ref("");
const actionError = ref("");
const successMessage = ref("");

const form = ref({
  final_price: "",
  admin_notes: "",
});

// Booking status
const isPending = computed(() => {
  return booking.value?.status === "pending";
});

const isReviewed = computed(() => {
  return booking.value?.status === "reviewed";
});

// Price recommendation
const estimateData = computed(() => {
  const data = estimate.value;

  return (
    data?.price_estimate ??
    data?.recommendation ??
    data?.estimate ??
    data ??
    null
  );
});

const recommendedPrice = computed(() => {
  return estimateData.value?.estimated_price ?? null;
});

const basePrice = computed(() => {
  return estimateData.value?.base_price ?? null;
});

const calculatedAt = computed(() => {
  return estimateData.value?.calculated_at ?? null;
});

// Price quotation
const priceQuote = computed(() => {
  return booking.value?.price_quote ?? null;
});

const hasPriceQuote = computed(() => {
  return Boolean(priceQuote.value);
});

const canCreateQuote = computed(() => {
  return (
    isReviewed.value && !hasPriceQuote.value && recommendedPrice.value !== null
  );
});

const eventPackage = computed(() => {
  return booking.value?.event_package ?? booking.value?.eventPackage ?? null;
});

// Format currency
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

// Format date without timezone shifting
function formatDate(value) {
  if (!value) return "-";

  const match = String(value)
    .slice(0, 10)
    .match(/^(\d{4})-(\d{2})-(\d{2})$/);

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

function formatDateTime(value) {
  if (!value) return "-";

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return "-";
  }

  return new Intl.DateTimeFormat("id-ID", {
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    timeZone: "Asia/Makassar",
  }).format(date);
}

function statusLabel(status) {
  const labels = {
    pending: "Menunggu Review",
    reviewed: "Sudah Direview",
    cancelled: "Dibatalkan",
    awaiting_payment: "Menunggu Pembayaran",
    paid: "Pembayaran Terverifikasi",
    completed: "Selesai",
  };

  return labels[status] || String(status || "-").replaceAll("_", " ");
}

function quoteStatusLabel(status) {
  const labels = {
    sent: "Terkirim",
    accepted: "Diterima Customer",
    rejected: "Ditolak Customer",
  };

  return labels[status] || String(status || "-").replaceAll("_", " ");
}

function getErrorMessage(err, fallback) {
  const errors = err.response?.data?.errors;

  if (errors && typeof errors === "object") {
    const firstError = Object.values(errors)
      .flat()
      .find((message) => typeof message === "string");

    if (firstError) return firstError;
  }

  return extractApiError(err, fallback);
}

function resetMessages() {
  error.value = "";
  estimateError.value = "";
  actionError.value = "";
  successMessage.value = "";
}

async function fetchBooking(id) {
  const response = await getAdminBooking(id);
  const data = unwrapAdminBooking(response);
  const result = data?.booking ?? data;

  if (!result?.id) {
    throw new Error("Data booking tidak ditemukan dalam respons API.");
  }

  return result;
}

async function fetchEstimate(id) {
  try {
    const response = await getPriceEstimate(id);
    estimate.value = extractData(response);
    estimateError.value = "";
  } catch (err) {
    estimate.value = null;

    if (err.response?.status === 404) {
      estimateError.value = "";
    } else {
      estimateError.value = getErrorMessage(
        err,
        "Gagal memuat rekomendasi harga AI.",
      );
    }
  }
}

async function loadBooking() {
  const id = route.params.id;

  loading.value = true;
  resetMessages();

  booking.value = null;
  estimate.value = null;

  try {
    booking.value = await fetchBooking(id);
    await fetchEstimate(id);
  } catch (err) {
    error.value = getErrorMessage(err, "Gagal memuat detail booking.");
  } finally {
    loading.value = false;
  }
}

// Review / cancel booking
async function submitReview(status) {
  if (!isPending.value || processing.value || sendingQuote.value) {
    return;
  }

  const message =
    status === "reviewed"
      ? "Tandai booking ini sebagai sudah direview?"
      : "Yakin ingin membatalkan booking ini?";

  if (!window.confirm(message)) return;

  processing.value = true;
  actionError.value = "";
  successMessage.value = "";

  const id = booking.value.id;

  try {
    const response = await reviewAdminBooking(id, {
      status,
    });

    // Gunakan respons mutation terlebih dahulu,
    // sehingga status tetap diperbarui jika
    // refresh berikutnya mengalami gangguan.
    const updated = unwrapAdminBooking(response);

    booking.value = {
      ...booking.value,
      ...(updated?.booking ?? updated),
    };

    successMessage.value =
      status === "reviewed"
        ? "Booking berhasil direview."
        : "Booking berhasil dibatalkan.";

    try {
      booking.value = await fetchBooking(id);
      await fetchEstimate(id);
    } catch {
      // Mutation sudah berhasil.
      // Admin tetap dapat menekan Perbarui Detail.
    }
  } catch (err) {
    actionError.value = getErrorMessage(
      err,
      "Gagal memperbarui status booking.",
    );
  } finally {
    processing.value = false;
  }
}

// Send final quotation
async function submitPriceQuote() {
  if (sendingQuote.value || processing.value) return;

  actionError.value = "";
  successMessage.value = "";

  if (!canCreateQuote.value) {
    actionError.value =
      "Booking harus berstatus reviewed, memiliki estimasi AI, dan belum mempunyai penawaran.";
    return;
  }

  const price = Number(form.value.final_price);

  if (!Number.isFinite(price) || price <= 0) {
    actionError.value = "Harga final harus lebih dari 0.";
    return;
  }

  if (price > 9999999999999.99) {
    actionError.value = "Harga final melebihi batas yang diizinkan.";
    return;
  }

  const notes = form.value.admin_notes.trim();

  if (notes.length > 2000) {
    actionError.value = "Catatan admin maksimal 2000 karakter.";
    return;
  }

  const confirmed = window.confirm(
    `Kirim penawaran sebesar ${formatRupiah(price)} kepada customer?`,
  );

  if (!confirmed) return;

  sendingQuote.value = true;

  const id = booking.value.id;

  try {
    const response = await createAdminPriceQuote(id, {
      final_price: price,
      admin_notes: notes || null,
    });

    const createdQuote = unwrapAdminBooking(response);

    // Segera tandai penawaran telah dibuat agar
    // form tidak bisa digunakan untuk mengirim ulang.
    booking.value = {
      ...booking.value,
      price_quote:
        createdQuote?.price_quote ?? createdQuote?.quote ?? createdQuote,
    };

    form.value.final_price = "";
    form.value.admin_notes = "";

    successMessage.value = "Penawaran harga berhasil dikirim kepada customer.";

    try {
      booking.value = await fetchBooking(id);
    } catch {
      // Penawaran berhasil dibuat.
      // Jangan tampilkan pesan kegagalan mutation.
    }
  } catch (err) {
    actionError.value = getErrorMessage(err, "Gagal mengirim penawaran harga.");
  } finally {
    sendingQuote.value = false;
  }
}

function useRecommendedPrice() {
  if (recommendedPrice.value === null) return;

  form.value.final_price = String(recommendedPrice.value);
}

onMounted(loadBooking);

watch(
  () => route.params.id,
  () => loadBooking(),
);
</script>

<template>
  <AdminLayout>
    <RouterLink to="/admin/bookings" class="back-link">
      ← Kembali ke Manajemen Booking
    </RouterLink>

    <div class="roman-eyebrow">BOOKING MANAGEMENT</div>

    <h1 class="roman-title">Detail Booking Admin</h1>

    <!-- Loading -->
    <div v-if="loading" class="roman-card state-box">
      Memuat informasi booking...
    </div>

    <!-- Error -->
    <div v-else-if="error" class="roman-card state-box">
      <p class="error">{{ error }}</p>

      <button class="refresh-button" @click="loadBooking">Coba Lagi</button>
    </div>

    <!-- Main content -->
    <div v-else-if="booking" class="detail-grid">
      <div class="main-column">
        <!-- BOOKING INFORMATION -->
        <section class="roman-card section-card">
          <div class="roman-eyebrow">EVENT INFORMATION</div>

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
              {{ statusLabel(booking.status) }}
            </strong>
          </div>

          <div class="info-row">
            <span>Customer</span>

            <strong>
              {{ booking.user?.name || "-" }}
            </strong>
          </div>

          <div class="info-row">
            <span>Email Customer</span>

            <strong>
              {{ booking.user?.email || "-" }}
            </strong>
          </div>

          <div class="info-row">
            <span>Telepon</span>

            <strong>
              {{ booking.user?.phone || "-" }}
            </strong>
          </div>

          <div class="info-row">
            <span>Paket Event</span>

            <strong>
              {{ eventPackage?.name || "-" }}
            </strong>
          </div>

          <div class="info-row">
            <span>Kategori Event</span>

            <strong>
              {{ eventPackage?.category?.name || "-" }}
            </strong>
          </div>

          <div class="info-row">
            <span>Tanggal Event</span>

            <strong>
              {{ formatDate(booking.event_date) }}
            </strong>
          </div>

          <div class="info-row">
            <span>Lokasi Event</span>

            <strong>
              {{ booking.event_location || "-" }}
            </strong>
          </div>

          <div class="info-row">
            <span>Jumlah Peserta</span>

            <strong>
              {{ booking.participants ?? "-" }}
            </strong>
          </div>

          <div class="notes">
            <span>Catatan Customer</span>

            <p>
              {{ booking.customer_notes || "Tidak ada catatan tambahan." }}
            </p>
          </div>
        </section>

        <!-- AI RECOMMENDATION -->
        <section class="roman-card section-card">
          <div class="roman-eyebrow">AI PRICE RECOMMENDATION</div>

          <h2>Rekomendasi Harga AI</h2>

          <p v-if="estimateError" class="error">
            {{ estimateError }}
          </p>

          <template v-else-if="recommendedPrice !== null">
            <div class="estimate-box">
              <span>Harga Rekomendasi AI</span>

              <strong>
                {{ formatRupiah(recommendedPrice) }}
              </strong>
            </div>

            <div class="info-row">
              <span>Harga Dasar</span>

              <strong>
                {{ formatRupiah(basePrice) }}
              </strong>
            </div>

            <div v-if="calculatedAt" class="info-row">
              <span>Waktu Perhitungan</span>

              <strong>
                {{ formatDateTime(calculatedAt) }}
              </strong>
            </div>

            <p class="muted">
              Rekomendasi AI digunakan sebagai bahan pertimbangan admin sebelum
              menetapkan harga final.
            </p>
          </template>

          <p v-else class="muted">
            Estimasi harga AI belum tersedia untuk booking ini.
          </p>
        </section>

        <!-- EXISTING QUOTATION -->
        <section v-if="hasPriceQuote" class="roman-card section-card">
          <div class="roman-eyebrow">PRICE QUOTATION</div>

          <h2>Penawaran Harga Terkirim</h2>

          <div class="info-row">
            <span>Harga Estimasi</span>

            <strong>
              {{ formatRupiah(priceQuote.estimated_price) }}
            </strong>
          </div>

          <div class="info-row">
            <span>Harga Final</span>

            <strong class="orange">
              {{ formatRupiah(priceQuote.final_price) }}
            </strong>
          </div>

          <div class="info-row">
            <span>Status Penawaran</span>

            <strong class="orange">
              {{ quoteStatusLabel(priceQuote.status) }}
            </strong>
          </div>

          <div v-if="priceQuote.created_at" class="info-row">
            <span>Tanggal Penawaran</span>

            <strong>
              {{ formatDateTime(priceQuote.created_at) }}
            </strong>
          </div>

          <div class="notes">
            <span>Catatan Admin</span>

            <p>
              {{ priceQuote.admin_notes || "Tidak ada catatan admin." }}
            </p>
          </div>

          <p class="muted">
            Penawaran sudah tersimpan dan tidak dapat dikirim ulang untuk
            booking yang sama.
          </p>
        </section>

        <!-- CREATE QUOTATION -->
        <section v-else-if="isReviewed" class="roman-card section-card">
          <div class="roman-eyebrow">OFFICIAL PRICE QUOTATION</div>

          <h2>Buat Penawaran Harga</h2>

          <p class="muted">
            Tentukan harga final berdasarkan rekomendasi AI dan kebutuhan event.
          </p>

          <form @submit.prevent="submitPriceQuote">
            <div class="field">
              <label for="final_price"> Harga Final (Rp) </label>

              <div class="price-input-row">
                <input
                  id="final_price"
                  v-model="form.final_price"
                  class="roman-input"
                  type="number"
                  min="0.01"
                  step="any"
                  placeholder="Masukkan harga final"
                  :disabled="sendingQuote || !canCreateQuote"
                  required
                />

                <button
                  type="button"
                  class="use-estimate-button"
                  :disabled="sendingQuote || recommendedPrice === null"
                  @click="useRecommendedPrice"
                >
                  Gunakan Harga AI
                </button>
              </div>
            </div>

            <div class="field">
              <label for="admin_notes"> Catatan Admin (Opsional) </label>

              <textarea
                id="admin_notes"
                v-model="form.admin_notes"
                class="roman-input notes-input"
                rows="5"
                maxlength="2000"
                placeholder="Tuliskan rincian atau ketentuan penawaran..."
                :disabled="sendingQuote || !canCreateQuote"
              ></textarea>

              <span class="character-count">
                {{ form.admin_notes.length }}/2000
              </span>
            </div>

            <p v-if="!canCreateQuote" class="warning-text">
              Penawaran hanya dapat dikirim setelah rekomendasi harga AI
              tersedia.
            </p>

            <button
              type="submit"
              class="primary-button"
              :disabled="sendingQuote || !canCreateQuote"
            >
              {{
                sendingQuote
                  ? "Mengirim Penawaran..."
                  : "Kirim Penawaran Harga →"
              }}
            </button>
          </form>
        </section>

        <div v-if="successMessage" class="success-box" role="status">
          {{ successMessage }}
        </div>

        <div v-if="actionError" class="error-box" role="alert">
          {{ actionError }}
        </div>
      </div>

      <!-- ADMIN ACTIONS -->
      <aside class="roman-card section-card">
        <div class="roman-eyebrow">ADMIN ACTIONS</div>

        <h2>Tindak Lanjut Booking</h2>

        <p class="muted">
          Periksa informasi pemesanan dan catatan customer sebelum mengambil
          keputusan.
        </p>

        <template v-if="isPending">
          <button
            class="primary-button"
            :disabled="processing || sendingQuote"
            @click="submitReview('reviewed')"
          >
            {{ processing ? "Memproses..." : "Setujui Review Booking" }}
          </button>

          <button
            class="danger-button"
            :disabled="processing || sendingQuote"
            @click="submitReview('cancelled')"
          >
            Batalkan Booking
          </button>
        </template>

        <div v-else class="status-info">
          <span>Status Booking</span>

          <strong class="orange">
            {{ statusLabel(booking.status) }}
          </strong>
        </div>

        <div v-if="hasPriceQuote" class="status-info">
          <span>Status Penawaran</span>

          <strong>
            {{ quoteStatusLabel(priceQuote.status) }}
          </strong>
        </div>

        <button
          class="refresh-button"
          :disabled="loading || processing || sendingQuote"
          @click="loadBooking"
        >
          Perbarui Detail
        </button>
      </aside>
    </div>
  </AdminLayout>
</template>

<style scoped>
.back-link {
  display: inline-block;
  margin-bottom: 25px;
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
    minmax(280px, 1fr);
  gap: 22px;
  align-items: start;
}

.main-column {
  display: grid;
  gap: 22px;
}

.section-card {
  padding: 28px;
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
  padding: 17px 0;
  border-bottom: 1px solid #303036;
}

.info-row span,
.notes > span {
  color: #a1a1aa;
  font-size: 13px;
}

.info-row strong {
  color: #fafaf9;
  font-size: 13px;
  text-align: right;
  overflow-wrap: anywhere;
}

.info-row strong.orange {
  color: #fb923c;
}

.notes {
  padding-top: 22px;
}

.notes p {
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

.estimate-box {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 22px;
  border: 1px solid #f9731633;
  border-radius: 8px;
  background: #f973160b;
}

.estimate-box span {
  color: #a1a1aa;
  font-size: 12px;
}

.estimate-box strong {
  color: #fb923c;
  font-size: clamp(22px, 3vw, 30px);
}

.muted {
  color: #a1a1aa;
  font-size: 13px;
  line-height: 1.8;
}

.field {
  margin-bottom: 22px;
}

.field label {
  display: block;
  margin-bottom: 10px;
  font-size: 12px;
  font-weight: 600;
}

.field .roman-input {
  width: 100%;
  box-sizing: border-box;
}

.price-input-row {
  display: flex;
  gap: 10px;
  align-items: stretch;
}

.price-input-row input {
  min-width: 0;
  flex: 1;
}

.use-estimate-button {
  flex-shrink: 0;
  padding: 10px 14px;
  border: 1px solid #f97316;
  border-radius: 8px;
  background: transparent;
  color: #fb923c;
  font-size: 12px;
  cursor: pointer;
}

.notes-input {
  min-height: 120px;
  padding: 14px;
  resize: vertical;
  font-family: inherit;
  line-height: 1.7;
}

.character-count {
  display: block;
  margin-top: 8px;
  color: #71717a;
  font-size: 11px;
  text-align: right;
}

.primary-button,
.danger-button,
.refresh-button {
  width: 100%;
  padding: 13px 16px;
  border-radius: 9px;
  cursor: pointer;
  font-size: 13px;
  transition: 0.2s;
}

.primary-button {
  border: 1px solid #f97316;
  background: #f97316;
  color: #fff;
  font-weight: 600;
}

.danger-button {
  margin-top: 12px;
  border: 1px solid #7f1d1d;
  background: transparent;
  color: #fca5a5;
}

.refresh-button {
  margin-top: 24px;
  border: 1px solid #f97316;
  background: transparent;
  color: #fb923c;
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.error-box,
.success-box {
  padding: 14px;
  border-radius: 8px;
  font-size: 12px;
  line-height: 1.7;
}

.error-box {
  border: 1px solid #7f1d1d;
  color: #fca5a5;
}

.success-box {
  border: 1px solid #166534;
  color: #86efac;
}

.state-box {
  padding: 30px;
  color: #a1a1aa;
}

.error {
  color: #fca5a5;
}

.warning-text {
  margin-bottom: 20px;
  color: #fdba74;
  font-size: 12px;
  line-height: 1.7;
}

.status-info {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 18px 0;
  border-bottom: 1px solid #303036;
}

.status-info span {
  color: #a1a1aa;
  font-size: 12px;
}

.status-info strong {
  font-size: 13px;
}

.orange {
  color: #fb923c;
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

  .price-input-row {
    flex-direction: column;
  }

  .use-estimate-button {
    width: 100%;
  }
}
</style>
