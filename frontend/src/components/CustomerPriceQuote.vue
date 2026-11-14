<script setup>
import { computed, ref } from "vue";
import { respondToPriceQuote } from "../services/paymentApi";

const props = defineProps({
  bookingId: { type: [String, Number], required: true },
  quote: { type: Object, default: null },
});

const emit = defineEmits(["updated"]);

const notes = ref("");
const processing = ref(false);
const error = ref("");

const formatPrice = (value) =>
  new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(Number(value || 0));

const isSent = computed(() => props.quote?.status === "sent");

async function respond(decision) {
  if (processing.value) return;

  const message =
    decision === "accepted"
      ? "Terima penawaran harga ini?"
      : "Tolak penawaran ini? Booking akan dibatalkan.";

  if (!window.confirm(message)) return;

  processing.value = true;
  error.value = "";

  try {
    await respondToPriceQuote(props.bookingId, decision, notes.value);

    emit("updated");
  } catch (err) {
    error.value =
      Object.values(err.response?.data?.errors || {})
        .flat()
        .join(" ") ||
      err.response?.data?.message ||
      "Gagal memproses keputusan penawaran.";
  } finally {
    processing.value = false;
  }
}
</script>

<template>
  <section class="roman-card quote-card">
    <div class="roman-eyebrow">OFFICIAL PRICE QUOTATION</div>
    <h2 class="roman-title">Penawaran Harga</h2>

    <div v-if="!quote" class="muted">Belum ada penawaran harga dari admin.</div>

    <template v-else>
      <div class="quote-row">
        <span>Estimasi Awal</span>
        <strong>{{ formatPrice(quote.estimated_price) }}</strong>
      </div>

      <div class="quote-row">
        <span>Harga Final Admin</span>
        <strong class="orange">
          {{ formatPrice(quote.final_price) }}
        </strong>
      </div>

      <div class="quote-row">
        <span>Status Penawaran</span>
        <strong>{{ quote.status }}</strong>
      </div>

      <div v-if="quote.admin_notes" class="notes">
        <strong>Catatan Admin</strong>
        <p>{{ quote.admin_notes }}</p>
      </div>

      <template v-if="isSent">
        <label for="quote-notes">Catatan Customer (opsional)</label>
        <textarea
          id="quote-notes"
          v-model="notes"
          class="roman-input"
          rows="3"
          placeholder="Tambahkan catatan..."
        />

        <div class="actions">
          <button
            class="roman-button"
            :disabled="processing"
            @click="respond('accepted')"
          >
            Terima Penawaran
          </button>

          <button
            class="reject-button"
            :disabled="processing"
            @click="respond('rejected')"
          >
            Tolak
          </button>
        </div>
      </template>

      <p v-if="error" class="error" role="alert">
        {{ error }}
      </p>
    </template>
  </section>
</template>

<style scoped>
.quote-card {
  padding: 26px;
}

h2 {
  margin: 12px 0 24px;
}

.quote-row {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  padding: 16px 0;
  border-bottom: 1px solid #303036;
}

.quote-row span,
.muted,
.notes p {
  color: #a1a1aa;
  font-size: 13px;
}

.quote-row strong {
  text-align: right;
  font-size: 14px;
}

.orange {
  color: #fb923c;
}

.notes {
  margin: 20px 0;
  padding: 15px;
  border-left: 3px solid #f97316;
  background: #f973160b;
}

label {
  display: block;
  margin: 20px 0 10px;
  font-size: 12px;
}

textarea {
  resize: vertical;
}

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 18px;
}

.reject-button {
  padding: 12px 20px;
  border: 1px solid #ef4444;
  border-radius: 9px;
  background: transparent;
  color: #fca5a5;
}

.error {
  color: #fca5a5;
  font-size: 12px;
}
</style>
