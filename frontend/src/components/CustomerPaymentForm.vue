<script setup>
import { computed, ref } from "vue";
import { submitPayment } from "../services/paymentApi";

const props = defineProps({
  bookingId: { type: [String, Number], required: true },
  payments: { type: Array, default: () => [] },
});

const emit = defineEmits(["updated"]);

const form = ref({
  payment_method: "bank_transfer",
  sender_name: "",
  bank_name: "",
  transferred_at: "",
  customer_notes: "",
});

const proof = ref(null);
const loading = ref(false);
const error = ref("");
const success = ref("");

const activePayment = computed(() =>
  props.payments.find((p) =>
    ["pending_verification", "verified"].includes(p.status),
  ),
);

function handleFile(event) {
  proof.value = event.target.files?.[0] || null;
}

async function submit() {
  error.value = "";
  success.value = "";

  if (!proof.value) {
    error.value = "Pilih bukti pembayaran.";
    return;
  }

  if (proof.value.size > 5 * 1024 * 1024) {
    error.value = "Ukuran file maksimal 5 MB.";
    return;
  }

  const allowed = ["image/jpeg", "image/png", "application/pdf"];

  if (!allowed.includes(proof.value.type)) {
    error.value = "File harus JPG, PNG, atau PDF.";
    return;
  }

  if (!form.value.transferred_at) {
    error.value = "Isi waktu transfer.";
    return;
  }

  loading.value = true;

  try {
    await submitPayment(props.bookingId, form.value, proof.value);
    success.value = "Bukti pembayaran berhasil dikirim.";
    emit("updated");
  } catch (err) {
    error.value =
      Object.values(err.response?.data?.errors || {})
        .flat()
        .join(" ") ||
      err.response?.data?.message ||
      "Gagal mengirim pembayaran.";
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <section class="roman-card payment-card">
    <div class="roman-eyebrow">SECURE PAYMENT</div>
    <h2 class="roman-title">Konfirmasi Pembayaran</h2>

    <div v-if="activePayment" class="payment-status">
      <strong>
        {{
          activePayment.status === "verified"
            ? "Pembayaran Terverifikasi"
            : "Menunggu Verifikasi Admin"
        }}
      </strong>
      <p>
        {{
          activePayment.status === "verified"
            ? "Pembayaran Anda telah diverifikasi."
            : "Bukti transfer telah diterima dan sedang diperiksa."
        }}
      </p>
    </div>

    <form v-else @submit.prevent="submit">
      <div class="field">
        <label>Metode Pembayaran</label>
        <select v-model="form.payment_method" class="roman-input">
          <option value="bank_transfer">Transfer Bank</option>
        </select>
      </div>

      <div class="field">
        <label>Nama Pengirim</label>
        <input v-model="form.sender_name" class="roman-input" required />
      </div>

      <div class="field">
        <label>Bank Pengirim</label>
        <input
          v-model="form.bank_name"
          class="roman-input"
          placeholder="Contoh: BCA"
          required
        />
      </div>

      <div class="field">
        <label>Waktu Transfer</label>
        <input
          v-model="form.transferred_at"
          class="roman-input"
          type="datetime-local"
          required
        />
      </div>

      <div class="field">
        <label>Bukti Transfer (JPG, PNG, PDF)</label>
        <input
          class="roman-input"
          type="file"
          accept=".jpg,.jpeg,.png,.pdf"
          required
          @change="handleFile"
        />
      </div>

      <div class="field">
        <label>Catatan (opsional)</label>
        <textarea v-model="form.customer_notes" class="roman-input" rows="3" />
      </div>

      <p v-if="error" class="error" role="alert">{{ error }}</p>
      <p v-if="success" class="success" role="status">{{ success }}</p>

      <button class="roman-button submit" :disabled="loading">
        {{ loading ? "Mengirim..." : "Kirim Bukti Pembayaran" }}
      </button>
    </form>
  </section>
</template>

<style scoped>
.payment-card {
  padding: 26px;
}

h2 {
  margin: 12px 0 24px;
}

.field {
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 9px;
  font-size: 12px;
  font-weight: 600;
}

textarea {
  resize: vertical;
}

.submit {
  width: 100%;
}

.payment-status {
  padding: 20px;
  border: 1px solid #f9731655;
  border-radius: 10px;
  background: #f9731610;
}

.payment-status strong {
  color: #fb923c;
}

.payment-status p {
  color: #a1a1aa;
  font-size: 13px;
}

.error {
  color: #fca5a5;
}

.success {
  color: #86efac;
}
</style>
