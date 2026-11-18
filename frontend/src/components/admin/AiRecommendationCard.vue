<script setup>
import { computed } from "vue";

const props = defineProps({
  prediction: {
    type: Object,
    required: true,
  },
});

const formatRupiah = (value) => {
  if (value === null || value === undefined) {
    return "Belum tersedia";
  }

  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(value));
};

const formatPercent = (value) => {
  if (value === null || value === undefined) {
    return "Belum tersedia";
  }

  return `${Number(value).toFixed(4)}%`;
};

const priceDifference = computed(() => {
  return (
    Number(props.prediction.recommended_price) -
    Number(props.prediction.base_price)
  );
});
</script>

<template>
  <section class="ai-card">
    <div class="ai-header">
      <div>
        <span class="eyebrow">KAREEM PRICING AI</span>
        <h2>Rekomendasi Harga Event</h2>
        <p>Hasil perhitungan Random Forest dan Fuzzy Sugeno.</p>
      </div>

      <span class="model-badge">
        {{ prediction.model_version }}
      </span>
    </div>

    <div class="price-highlight">
      <span>Harga Rekomendasi AI</span>
      <strong>
        {{ formatRupiah(prediction.recommended_price) }}
      </strong>
      <small> Estimasi sistem, bukan harga penawaran final. </small>
    </div>

    <div class="price-grid">
      <div class="price-item">
        <span>Harga Dasar Paket</span>
        <strong>
          {{ formatRupiah(prediction.base_price) }}
        </strong>
      </div>

      <div class="price-item">
        <span>Prediksi Random Forest</span>
        <strong>
          {{ formatRupiah(prediction.rf_predicted_price) }}
        </strong>
      </div>

      <div class="price-item">
        <span>Penyesuaian Fuzzy Sugeno</span>
        <strong>
          {{ formatPercent(prediction.fuzzy_adjustment_percent) }}
        </strong>
      </div>

      <div class="price-item">
        <span>Selisih dari Harga Dasar</span>
        <strong>
          {{ formatRupiah(priceDifference) }}
        </strong>
      </div>
    </div>

    <div class="ai-footer">
      <span>Metode</span>
      <strong>
        {{ prediction.calculation_method }}
      </strong>
    </div>
  </section>
</template>

<style scoped>
.ai-card {
  background: #151515;
  color: #f5f1e8;
  border: 1px solid #343434;
  border-radius: 18px;
  padding: 28px;
}

.ai-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 24px;
}

.eyebrow {
  color: #f97316;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 2px;
}

.ai-header h2 {
  font-size: 22px;
  margin: 8px 0;
}

.ai-header p {
  color: #aaa;
  font-size: 13px;
}

.model-badge {
  padding: 7px 12px;
  border: 1px solid #444;
  border-radius: 30px;
  font-size: 11px;
  white-space: nowrap;
}

.price-highlight {
  background: #222;
  padding: 24px;
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.price-highlight span {
  color: #bbb;
  font-size: 13px;
}

.price-highlight strong {
  font-size: clamp(24px, 4vw, 36px);
  color: #f97316;
}

.price-highlight small {
  color: #999;
}

.price-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
  margin-top: 20px;
}

.price-item {
  padding: 18px;
  border: 1px solid #333;
  border-radius: 12px;
}

.price-item span {
  display: block;
  color: #aaa;
  font-size: 12px;
  margin-bottom: 10px;
}

.price-item strong {
  font-size: 16px;
  overflow-wrap: anywhere;
}

.ai-footer {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-top: 22px;
  padding-top: 18px;
  border-top: 1px solid #333;
  font-size: 12px;
}

.ai-footer span {
  color: #aaa;
}

.ai-footer strong {
  overflow-wrap: anywhere;
  text-align: right;
}

@media (max-width: 640px) {
  .ai-card {
    padding: 18px;
  }

  .ai-header {
    flex-direction: column;
  }

  .price-grid {
    grid-template-columns: 1fr;
  }
}
</style>
