<script setup>
defineProps({
  title: { type: String, required: true },
  value: { type: [Number, String], default: null },
  description: { type: String, default: "" },
  symbol: { type: String, default: "◈" },
  loading: { type: Boolean, default: false },
});
</script>

<template>
  <article class="stat-card roman-card">
    <div class="stat-header">
      <span>{{ title }}</span>
      <div class="stat-symbol" aria-hidden="true">
        {{ symbol }}
      </div>
    </div>

    <strong class="stat-value">
      {{ loading ? "..." : (value ?? "—") }}
    </strong>

    <p>{{ description }}</p>
  </article>
</template>

<style scoped>
.stat-card {
  padding: 24px;
}

.stat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  color: #a1a1aa;
  font-size: 12px;
}

.stat-symbol {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  border-radius: 9px;
  background: #f9731618;
  color: #fb923c;
  font-size: 19px;
}

.stat-value {
  display: block;
  margin: 18px 0 12px;
  color: #f5f0e8;
  font-size: 32px;
}

.stat-card p {
  margin: 0;
  color: #71717a;
  font-size: 11px;
}
</style>
