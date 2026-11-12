
<script setup>
import { computed } from 'vue'

const props = defineProps({
  package: {
    type: Object,
    required: true,
  },
})

defineEmits(['view-detail'])

const formatRupiah = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

const categoryName = computed(() => {
  return props.package.category?.name || 'Event Package'
})

const packageName = computed(() => {
  return props.package.name || 'Paket Event'
})
</script>

<template>
  <article class="package-card">
    <div class="package-top">
      <div class="package-ornament" aria-hidden="true">
        <span class="roman-arch"></span>
      </div>

      <span class="category-badge">
        {{ categoryName }}
      </span>
    </div>

    <div class="package-content">
      <div class="roman-eyebrow">KAREEM EXPERIENCE</div>

      <h3 class="roman-title">
        {{ packageName }}
      </h3>

      <p class="description">
        {{
          package.description ||
          'Paket layanan event profesional dari Kareem Entertainment.'
        }}
      </p>

      <div class="package-meta">
        <span v-if="package.min_participants != null">
          Min. {{ package.min_participants }} peserta
        </span>

        <span v-if="package.max_participants != null">
          Maks. {{ package.max_participants }} peserta
        </span>
      </div>

      <div class="package-footer">
        <div>
          <small>HARGA DASAR</small>
          <strong>
            {{ formatRupiah(package.base_price) }}
          </strong>
        </div>

        <button
          type="button"
          class="detail-button"
          @click="$emit('view-detail', package)"
        >
          Detail →
        </button>
      </div>
    </div>
  </article>
</template>

<style scoped>
.package-card {
  overflow: hidden;
  border: 1px solid #303036;
  border-radius: 15px;
  background: #18181b;
  transition: border-color .2s, transform .2s;
}

.package-card:hover {
  transform: translateY(-4px);
  border-color: #f97316;
}

.package-top {
  position: relative;
  display: grid;
  place-items: center;
  height: 175px;
  overflow: hidden;
  background:
    radial-gradient(
      circle at 50% 100%,
      #f9731626,
      transparent 65%
    ),
    linear-gradient(135deg, #202024, #111113);
}

.package-ornament {
  opacity: .7;
}

.roman-arch {
  display: block;
  width: 95px;
  height: 120px;
  border: 8px solid #f97316;
  border-bottom: 0;
  border-radius: 50px 50px 0 0;
  box-shadow:
    0 0 0 12px #f9731615,
    0 0 40px #f9731620;
}

.category-badge {
  position: absolute;
  top: 14px;
  left: 14px;
  padding: 7px 11px;
  border: 1px solid #f9731644;
  border-radius: 6px;
  background: #0b0b0dcc;
  color: #fb923c;
  font-size: 11px;
}

.package-content {
  padding: 23px;
}

.package-content h3 {
  min-height: 48px;
  margin: 12px 0;
  font-size: 18px;
}

.description {
  display: -webkit-box;
  min-height: 62px;
  overflow: hidden;
  color: #a1a1aa;
  font-size: 12px;
  line-height: 1.7;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
}

.package-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 18px 0;
}

.package-meta span {
  padding: 7px 9px;
  border: 1px solid #38383d;
  border-radius: 6px;
  color: #d4d4d8;
  font-size: 10px;
}

.package-footer {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 10px;
  padding-top: 20px;
  border-top: 1px solid #303036;
}

.package-footer small {
  display: block;
  margin-bottom: 7px;
  color: #a1a1aa;
  font-size: 10px;
}

.package-footer strong {
  color: #fb923c;
  font-size: 17px;
}

.detail-button {
  padding: 10px 13px;
  border: 1px solid #f97316;
  border-radius: 8px;
  background: transparent;
  color: #fb923c;
  font-size: 12px;
  font-weight: 600;
}

.detail-button:hover {
  background: #f97316;
  color: #111;
}
</style>
