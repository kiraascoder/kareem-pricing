<script setup>
import { computed } from 'vue'
const props = defineProps({ prediction: { type: Object, required: true } })
function rupiah(value) {
  if (value === null || value === undefined || value === '') return 'Belum tersedia'
  const number = Number(value)
  return Number.isFinite(number) ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(number) : 'Belum tersedia'
}
function percent(value) {
  if (value === null || value === undefined) return 'Belum tersedia'
  return `${Number(value).toFixed(4)}%`
}
const difference = computed(() => Number(props.prediction.recommended_price) - Number(props.prediction.base_price))
const hasDifference = computed(() => props.prediction.recommended_price != null && props.prediction.base_price != null)
</script>
<template>
  <div class="ai-card">
    <div class="ai-header"><div><span class="eyebrow">KAREEM PRICING AI</span><h3>Random Forest + Fuzzy Sugeno</h3></div><span class="model-badge">{{ prediction.model_version || 'Tidak diketahui' }}</span></div>
    <div class="highlight"><span>Harga Rekomendasi AI</span><strong>{{ rupiah(prediction.recommended_price) }}</strong><small>Estimasi internal, bukan harga penawaran final.</small></div>
    <div class="price-grid">
      <div class="price-item"><span>Harga Dasar Paket</span><strong>{{ rupiah(prediction.base_price) }}</strong></div>
      <div class="price-item"><span>Prediksi Random Forest</span><strong>{{ rupiah(prediction.rf_predicted_price) }}</strong></div>
      <div class="price-item"><span>Penyesuaian Fuzzy Sugeno</span><strong>{{ percent(prediction.fuzzy_adjustment_percent) }}</strong></div>
      <div class="price-item"><span>Selisih terhadap Harga Dasar</span><strong>{{ hasDifference ? rupiah(difference) : 'Belum tersedia' }}</strong></div>
    </div>
    <p v-if="prediction.rf_predicted_price == null" class="note">Rincian prediksi Random Forest tidak tersedia pada rekomendasi model lama.</p>
    <div class="meta"><span>Metode</span><strong>{{ prediction.calculation_method || '-' }}</strong></div>
    <div v-if="prediction.input_features && Object.keys(prediction.input_features).length" class="features"><h4>Fitur Input Model</h4><div v-for="(value, key) in prediction.input_features" :key="key" class="feature-row"><span>{{ String(key).replaceAll('_', ' ') }}</span><strong>{{ value ?? '-' }}</strong></div></div>
  </div>
</template>
<style scoped>
.ai-card{border:1px solid #34343a;border-radius:14px;background:#141416;color:#f5f1e8;padding:22px}.ai-header{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:20px}.eyebrow{color:#fb923c;font-size:10px;letter-spacing:1.8px;font-weight:700}.ai-header h3{font-size:17px;margin:8px 0 0}.model-badge{border:1px solid #444;border-radius:24px;padding:7px 10px;font-size:11px;white-space:nowrap}.highlight{display:flex;flex-direction:column;gap:8px;padding:20px;background:#f973160c;border:1px solid #f9731633;border-radius:10px}.highlight span,.highlight small{color:#a1a1aa;font-size:12px}.highlight strong{font-size:clamp(23px,3vw,32px);color:#fb923c;overflow-wrap:anywhere}.price-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:16px}.price-item{border:1px solid #303036;border-radius:10px;padding:16px;min-width:0}.price-item span{display:block;color:#a1a1aa;font-size:12px;margin-bottom:10px}.price-item strong{font-size:14px;overflow-wrap:anywhere}.meta{display:flex;justify-content:space-between;gap:12px;border-top:1px solid #303036;padding-top:16px;margin-top:18px;font-size:12px}.meta span,.feature-row span{color:#a1a1aa}.meta strong{text-align:right;overflow-wrap:anywhere}.features{margin-top:22px}.features h4{font-size:13px}.feature-row{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid #303036;font-size:12px}.feature-row strong{overflow-wrap:anywhere;text-align:right}.note{font-size:12px;color:#fdba74;line-height:1.7}@media(max-width:600px){.ai-card{padding:15px}.ai-header{flex-direction:column}.price-grid{grid-template-columns:1fr}}
</style>
