
<script setup>
import { computed } from 'vue'

const props = defineProps({
  estimate: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['estimate'])

const price = computed(() => {
  if (!props.estimate) return null

  const value =
    props.estimate.recommended_price ??
    props.estimate.estimated_price ??
    props.estimate.price_recommendation?.recommended_price

  if (value == null || !Number.isFinite(Number(value))) {
    return null
  }

  return Number(value)
})

const formatRupiah = (value) =>
  new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value)
</script>

<template>
  <section class="estimate-card roman-card">
    <div class="roman-eyebrow">AI PRICE INTELLIGENCE</div>

    <h2 class="roman-title">Estimasi Harga Event</h2>

    <p class="description">
      Estimasi dihitung menggunakan model AI Kareem Pricing.
      Harga final tetap ditentukan melalui penawaran admin.
    </p>

    <div v-if="price !== null" class="estimate-result">
      <span>REKOMENDASI HARGA</span>
      <strong>{{ formatRupiah(price) }}</strong>
      <small>Estimasi, bukan tagihan final</small>
    </div>

    <div v-else class="estimate-empty">
      {{
        loading
          ? 'Menghitung estimasi harga...'
          : 'Estimasi belum tersedia.'
      }}
    </div>

    <p v-if="error" class="estimate-error" role="alert">
      {{ error }}
    </p>

    <button
      type="button"
      class="roman-button"
      :disabled="loading"
      @click="emit('estimate')"
    >
      {{ loading ? 'Memproses...' : 'Hitung Estimasi AI' }}
    </button>
  </section>
</template>

<style scoped>
.estimate-card {
  padding: 26px;
}

.estimate-card h2 {
  margin: 12px 0;
  font-size: 20px;
}

.description {
  color: #a1a1aa;
  font-size: 12px;
  line-height: 1.8;
}

.estimate-result,
.estimate-empty {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin: 22px 0;
  padding: 22px;
  border: 1px solid #f9731640;
  border-radius: 12px;
  background: #f973160c;
}

.estimate-result span,
.estimate-result small {
  color: #a1a1aa;
  font-size: 11px;
}

.estimate-result strong {
  color: #fb923c;
  font-size: clamp(22px, 3vw, 30px);
}

.estimate-empty {
  color: #a1a1aa;
  font-size: 13px;
}

.estimate-error {
  color: #fca5a5;
  font-size: 12px;
}

.estimate-card button {
  width: 100%;
}
</style>
