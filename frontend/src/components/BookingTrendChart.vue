<script setup>
import { computed } from "vue";

const props = defineProps({
  data: { type: Array, default: () => [] },
});

const width = 700;
const height = 240;
const padding = 28;

const points = computed(() => {
  const values = props.data.map((item) => Number(item.total ?? 0));
  if (!values.length) return [];

  const max = Math.max(1, ...values);

  return values.map((value, index) => ({
    x:
      padding +
      (values.length === 1
        ? (width - padding * 2) / 2
        : (index * (width - padding * 2)) / (values.length - 1)),
    y: height - padding - (value / max) * (height - padding * 2),
    value,
    date: props.data[index].date,
  }));
});

const linePath = computed(() => {
  return points.value
    .map((p, i) => `${i === 0 ? "M" : "L"} ${p.x} ${p.y}`)
    .join(" ");
});

const areaPath = computed(() => {
  if (!points.value.length) return "";

  const first = points.value[0];
  const last = points.value[points.value.length - 1];

  return `${linePath.value} L ${last.x} ${height - padding} L ${first.x} ${height - padding} Z`;
});

const total = computed(() =>
  props.data.reduce((sum, item) => sum + Number(item.total || 0), 0),
);
</script>

<template>
  <section class="chart-card roman-card">
    <div class="chart-header">
      <div>
        <div class="roman-eyebrow">BOOKING ANALYTICS</div>
        <h2>Tren Booking</h2>
      </div>
      <div class="chart-total">
        <strong>{{ total }}</strong>
        <span>Booking pada periode grafik</span>
      </div>
    </div>

    <div v-if="!points.length" class="empty">Belum ada data grafik.</div>

    <div v-else class="chart-container">
      <svg
        :viewBox="`0 0 ${width} ${height}`"
        role="img"
        aria-label="Grafik jumlah booking berdasarkan tanggal"
      >
        <defs>
          <linearGradient id="bookingFill" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#f97316" stop-opacity=".3" />
            <stop offset="100%" stop-color="#f97316" stop-opacity="0" />
          </linearGradient>
        </defs>

        <line
          v-for="i in 4"
          :key="i"
          :x1="padding"
          :x2="width - padding"
          :y1="padding + ((i - 1) * (height - padding * 2)) / 3"
          :y2="padding + ((i - 1) * (height - padding * 2)) / 3"
          stroke="#34343a"
          stroke-dasharray="4 5"
        />

        <path :d="areaPath" fill="url(#bookingFill)" />

        <path
          :d="linePath"
          fill="none"
          stroke="#f97316"
          stroke-width="3"
          stroke-linejoin="round"
          stroke-linecap="round"
        />

        <circle
          v-for="(point, index) in points"
          :key="index"
          :cx="point.x"
          :cy="point.y"
          r="3"
          fill="#fb923c"
        >
          <title>{{ point.date }}: {{ point.value }} booking</title>
        </circle>
      </svg>
    </div>

    <div v-if="points.length" class="chart-labels">
      <span>{{ points[0].date }}</span>
      <span>{{ points[points.length - 1].date }}</span>
    </div>
  </section>
</template>

<style scoped>
.chart-card {
  padding: 26px;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  gap: 15px;
  align-items: center;
}

.chart-header h2 {
  margin: 12px 0;
  font-size: 18px;
}

.chart-total {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.chart-total strong {
  color: #fb923c;
  font-size: 24px;
}

.chart-total span,
.chart-labels {
  color: #a1a1aa;
  font-size: 11px;
}

.chart-container {
  margin-top: 20px;
}

svg {
  display: block;
  width: 100%;
  height: auto;
}

.chart-labels {
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

.empty {
  padding: 60px 0;
  color: #a1a1aa;
  text-align: center;
  font-size: 13px;
}

@media (max-width: 550px) {
  .chart-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .chart-total {
    align-items: flex-start;
  }
}
</style>
