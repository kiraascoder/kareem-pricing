<script setup>
import { onMounted, ref } from "vue";
import {
  getPackages,
  extractCollection,
  extractApiError,
} from "../../services/bookingApi";

const packages = ref([]);
const loading = ref(true);
const error = ref("");

async function loadPackages() {
  loading.value = true;
  error.value = "";

  try {
    const response = await getPackages();
    packages.value = extractCollection(response);
  } catch (err) {
    error.value = extractApiError(err, "Gagal memuat paket event.");
  } finally {
    loading.value = false;
  }
}

function formatRupiah(value) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(Number(value || 0));
}

onMounted(loadPackages);
</script>

<template>
  <section class="public-page">
    <span class="eyebrow">OUR EXPERIENCES</span>
    <h1>Event Packages</h1>
    <p>Temukan paket event sesuai kebutuhan Anda.</p>

    <div v-if="loading" class="message">Memuat paket...</div>

    <div v-else-if="error" class="message">
      {{ error }}
      <button @click="loadPackages">Coba Lagi</button>
    </div>

    <div v-else class="grid">
      <article v-for="item in packages" :key="item.id" class="package-card">
        <span>EVENT PACKAGE</span>
        <h2>{{ item.name }}</h2>
        <p>{{ item.description }}</p>
        <strong>
          {{ formatRupiah(item.base_price) }}
        </strong>

        <RouterLink
          :to="{
            path: '/booking',
            query: { package_id: item.id },
          }"
        >
          Pilih Paket →
        </RouterLink>
      </article>

      <p v-if="!packages.length">Belum ada paket yang tersedia.</p>
    </div>
  </section>
</template>

<style scoped>
.public-page {
  min-height: 70vh;
  padding: 80px 8%;
  color: #fafaf9;
}

.eyebrow,
.package-card span {
  color: #f97316;
  font-size: 11px;
  letter-spacing: 2px;
}

h1 {
  font-family: Cinzel, serif;
  font-size: 38px;
}

.public-page > p,
.package-card p {
  color: #a1a1aa;
  line-height: 1.8;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 20px;
  margin-top: 35px;
}

.package-card {
  padding: 30px;
  border: 1px solid #303036;
  border-radius: 8px;
  background: #18181b;
}

.package-card h2 {
  margin: 20px 0;
  font-size: 20px;
}

.package-card strong {
  display: block;
  margin: 25px 0;
  color: #fb923c;
}

.package-card a {
  display: inline-block;
  padding: 12px 20px;
  background: #f97316;
  border-radius: 5px;
  color: white;
  text-decoration: none;
  font-size: 12px;
}

.message {
  margin-top: 30px;
  color: #a1a1aa;
}

.message button {
  margin-left: 12px;
  cursor: pointer;
}
</style>
