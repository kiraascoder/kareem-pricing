<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";
import AdminLayout from "../layouts/AdminLayout.vue";
import {
  getAdminPayments,
  getAdminPaymentProof,
  verifyAdminPayment,
  unwrapAdminPayments,
} from "../services/adminPaymentApi";

const payments = ref([]);
const loading = ref(false);
const processingId = ref(null);
const error = ref("");
const success = ref("");

const currentPage = ref(1);
const lastPage = ref(1);

const selectedPayment = ref(null);
const decision = ref("verified");
const adminNotes = ref("");

const proofUrl = ref("");
const proofLoading = ref(false);
const proofError = ref("");
const proofType = ref("");

function formatRupiah(value) {
  const number = Number(value);

  if (!Number.isFinite(number)) return "-";

  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(number);
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

function clearProof() {
  if (proofUrl.value) {
    URL.revokeObjectURL(proofUrl.value);
  }

  proofUrl.value = "";
  proofType.value = "";
  proofError.value = "";
}

async function loadPayments(page = 1) {
  loading.value = true;
  error.value = "";

  try {
    const response = await getAdminPayments({ page });
    const result = unwrapAdminPayments(response);

    payments.value = result.data;
    currentPage.value = result.current_page || 1;
    lastPage.value = result.last_page || 1;
  } catch (err) {
    error.value =
      err.response?.data?.message || err.message || "Gagal memuat pembayaran.";
  } finally {
    loading.value = false;
  }
}

async function showProof(payment) {
  clearProof();
  selectedPayment.value = payment;
  decision.value = "verified";
  adminNotes.value = "";
  proofLoading.value = true;

  try {
    const response = await getAdminPaymentProof(payment.id);

    proofType.value = response.data.type || "application/octet-stream";

    proofUrl.value = URL.createObjectURL(response.data);
  } catch (err) {
    proofError.value =
      err.response?.status === 404
        ? "Bukti transfer tidak ditemukan."
        : "Gagal mengambil bukti transfer.";
  } finally {
    proofLoading.value = false;
  }
}

function closeDetail() {
  if (processingId.value !== null) return;

  selectedPayment.value = null;
  clearProof();
}

async function submitVerification() {
  const payment = selectedPayment.value;

  if (!payment || processingId.value !== null) return;

  if (payment.status !== "pending_verification") return;

  if (payment.booking?.status !== "awaiting_payment") {
    error.value = "Status booking tidak valid untuk verifikasi pembayaran.";
    return;
  }

  if (decision.value === "rejected" && !adminNotes.value.trim()) {
    error.value = "Isi alasan penolakan pembayaran.";
    return;
  }

  const confirmed = window.confirm(
    decision.value === "verified"
      ? "Yakin ingin menyetujui pembayaran ini?"
      : "Yakin ingin menolak pembayaran ini?",
  );

  if (!confirmed) return;

  processingId.value = payment.id;
  error.value = "";
  success.value = "";

  try {
    const response = await verifyAdminPayment(payment.id, {
      decision: decision.value,
      admin_notes: adminNotes.value.trim() || null,
    });

    success.value = response.data?.message || "Verifikasi pembayaran berhasil.";

    selectedPayment.value = null;
    clearProof();

    await loadPayments(currentPage.value);
  } catch (err) {
    const validationErrors = err.response?.data?.errors;

    error.value =
      (validationErrors && Object.values(validationErrors).flat().join(" ")) ||
      err.response?.data?.message ||
      "Gagal memverifikasi pembayaran.";
  } finally {
    processingId.value = null;
  }
}

onMounted(() => loadPayments());

onBeforeUnmount(clearProof);
</script>

<template>
  <AdminLayout>
    <div class="payments-heading">
      <div>
        <div class="roman-eyebrow">PAYMENT MANAGEMENT</div>
        <h1 class="roman-title">Verifikasi Pembayaran</h1>
        <p>Periksa dan verifikasi bukti transfer pengguna.</p>
      </div>

      <button
        class="payment-button"
        :disabled="loading"
        @click="loadPayments(currentPage)"
      >
        {{ loading ? "Memuat..." : "Perbarui Data" }}
      </button>
    </div>

    <div v-if="error" class="payment-alert error">
      {{ error }}
    </div>

    <div v-if="success" class="payment-alert success">
      {{ success }}
    </div>

    <section class="roman-card payment-card">
      <div class="roman-eyebrow">PAYMENT RECORDS</div>
      <h2>Daftar Pembayaran</h2>

      <div v-if="loading" class="payment-empty">Memuat pembayaran...</div>

      <div v-else-if="!payments.length" class="payment-empty">
        Belum ada pembayaran.
      </div>

      <div v-else class="payment-table-wrapper">
        <table class="payment-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Kode Booking</th>
              <th>Nominal</th>
              <th>Tanggal</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="payment in payments" :key="payment.id">
              <td>#{{ payment.id }}</td>

              <td>
                {{ payment.booking?.booking_code || "-" }}
              </td>

              <td>
                {{ formatRupiah(payment.amount) }}
              </td>

              <td>
                {{ formatDate(payment.created_at) }}
              </td>

              <td>
                <span class="payment-status" :class="payment.status">
                  {{ statusLabel(payment.status) }}
                </span>
              </td>

              <td>
                <button class="payment-button" @click="showProof(payment)">
                  Detail
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="payment-pagination">
        <button
          class="payment-button"
          :disabled="loading || currentPage <= 1"
          @click="loadPayments(currentPage - 1)"
        >
          Sebelumnya
        </button>

        <span>Halaman {{ currentPage }} / {{ lastPage }}</span>

        <button
          class="payment-button"
          :disabled="loading || currentPage >= lastPage"
          @click="loadPayments(currentPage + 1)"
        >
          Berikutnya
        </button>
      </div>
    </section>

    <div v-if="selectedPayment" class="payment-modal-backdrop">
      <section class="payment-modal roman-card">
        <div class="payment-modal-header">
          <div>
            <div class="roman-eyebrow">PAYMENT REVIEW</div>
            <h2>Detail Pembayaran #{{ selectedPayment.id }}</h2>
          </div>

          <button
            class="payment-button"
            :disabled="processingId !== null"
            @click="closeDetail"
          >
            Tutup
          </button>
        </div>

        <p>
          <strong>Booking:</strong>
          {{ selectedPayment.booking?.booking_code || "-" }}
        </p>

        <p>
          <strong>Nominal:</strong>
          {{ formatRupiah(selectedPayment.amount) }}
        </p>

        <p>
          <strong>Status:</strong>
          {{ statusLabel(selectedPayment.status) }}
        </p>

        <h3>Bukti Transfer</h3>

        <p v-if="proofLoading">Memuat bukti transfer...</p>
        <p v-else-if="proofError">{{ proofError }}</p>

        <template v-else-if="proofUrl">
          <img
            v-if="proofType.startsWith('image/')"
            :src="proofUrl"
            alt="Bukti transfer"
            class="payment-proof"
          />

          <iframe
            v-else-if="proofType === 'application/pdf'"
            :src="proofUrl"
            title="Bukti transfer PDF"
            class="payment-proof-frame"
          />

          <a v-else :href="proofUrl" download="bukti-transfer">
            Unduh bukti transfer
          </a>
        </template>

        <div
          v-if="
            selectedPayment.status === 'pending_verification' &&
            selectedPayment.booking?.status === 'awaiting_payment'
          "
          class="payment-review"
        >
          <h3>Keputusan Admin</h3>

          <select v-model="decision" class="roman-input">
            <option value="verified">Setujui Pembayaran</option>
            <option value="rejected">Tolak Pembayaran</option>
          </select>

          <textarea
            v-model="adminNotes"
            class="roman-input payment-notes"
            placeholder="Catatan admin / alasan penolakan..."
            rows="3"
          />

          <button
            class="payment-button primary"
            :disabled="processingId !== null"
            @click="submitVerification"
          >
            {{ processingId !== null ? "Memproses..." : "Simpan Keputusan" }}
          </button>
        </div>

        <p v-else>Pembayaran ini tidak tersedia untuk diverifikasi.</p>
      </section>
    </div>
  </AdminLayout>
</template>

<style scoped>
.payments-heading,
.payment-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  flex-wrap: wrap;
  margin-bottom: 24px;
}

.payments-heading p {
  color: #a1a1aa;
}

.payment-card {
  padding: 26px;
}

.payment-card h2 {
  margin: 10px 0 22px;
}

.payment-button {
  background: #292524;
  border: 1px solid #57534e;
  border-radius: 8px;
  padding: 10px 15px;
  color: #fafafa;
  cursor: pointer;
}

.payment-button.primary {
  background: #c2410c;
  border-color: #ea580c;
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
  border-radius: 20px;
  padding: 6px 11px;
  background: #44403c;
  font-size: 12px;
}

.payment-status.verified {
  color: #86efac;
}

.payment-status.rejected {
  color: #fca5a5;
}

.payment-status.pending_verification {
  color: #fdba74;
}

.payment-empty {
  padding: 40px 0;
  text-align: center;
  color: #a1a1aa;
}

.payment-pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 25px;
}

.payment-alert {
  padding: 14px;
  border-radius: 8px;
  margin-bottom: 18px;
}

.payment-alert.error {
  background: #450a0a;
  color: #fecaca;
}

.payment-alert.success {
  background: #14532d;
  color: #bbf7d0;
}

.payment-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.78);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.payment-modal {
  width: min(700px, 100%);
  max-height: 90vh;
  overflow-y: auto;
  padding: 26px;
}

.payment-proof {
  display: block;
  width: 100%;
  max-height: 350px;
  object-fit: contain;
  margin: 15px 0;
}

.payment-proof-frame {
  width: 100%;
  height: 350px;
  border: none;
}

.payment-review {
  display: grid;
  gap: 14px;
  margin-top: 25px;
}

.payment-notes {
  width: 100%;
  resize: vertical;
}
</style>
