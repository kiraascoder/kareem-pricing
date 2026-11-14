<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";

import CustomerLayout from "../layouts/CustomerLayout.vue";
import EventPackageCard from "../components/EventPackageCard.vue";

import {
  getPackages,
  getCategories,
  extractCollection,
  extractApiError,
} from "../services/bookingApi";

const router = useRouter();

const packages = ref([]);
const categories = ref([]);

const loading = ref(true);
const error = ref("");

const search = ref("");
const selectedCategory = ref("");
const selectedPackage = ref(null);

const formatRupiah = (value) =>
  new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(Number(value || 0));

async function loadData() {
  loading.value = true;
  error.value = "";

  try {
    const [packageResponse, categoryResponse] = await Promise.all([
      getPackages(),
      getCategories(),
    ]);

    packages.value = extractCollection(packageResponse);
    categories.value = extractCollection(categoryResponse);
  } catch (err) {
    console.error("Gagal memuat katalog:", err);

    error.value = extractApiError(err, "Gagal memuat katalog paket event.");
  } finally {
    loading.value = false;
  }
}

const filteredPackages = computed(() => {
  const keyword = search.value.trim().toLowerCase();

  return packages.value.filter((item) => {
    const name = String(item.name || "").toLowerCase();

    const matchSearch = !keyword || name.includes(keyword);

    const matchCategory =
      !selectedCategory.value ||
      String(item.category_id) === String(selectedCategory.value);

    return matchSearch && matchCategory;
  });
});

function openDetail(item) {
  selectedPackage.value = item;
}

function closeDetail() {
  selectedPackage.value = null;
}

function bookPackage(item) {
  closeDetail();

  router.push({
    path: "/customer/bookings/create",
    query: {
      package_id: item.id,
    },
  });
}

onMounted(loadData);
</script>

<template>
  <CustomerLayout>
    <div class="catalog-header">
      <div>
        <div class="roman-eyebrow">OUR EXPERIENCES</div>
        <h1 class="roman-title">Event Packages</h1>
        <p>
          Temukan layanan event yang sesuai dengan kebutuhan dan rencana
          anggaran Anda.
        </p>
      </div>
    </div>

    <section class="catalog-intro roman-card">
      <div class="roman-eyebrow">CURATED FOR YOU</div>
      <h2 class="roman-title">Your Event, Our Excellence.</h2>
      <p>
        Pilih paket event dan lanjutkan ke proses booking untuk mendapatkan
        estimasi harga berbasis AI.
      </p>
    </section>

    <div class="catalog-toolbar">
      <input
        v-model="search"
        class="roman-input search-input"
        type="search"
        placeholder="Cari paket event..."
      />

      <select v-model="selectedCategory" class="roman-input category-select">
        <option value="">Semua Kategori</option>

        <option
          v-for="category in categories"
          :key="category.id"
          :value="String(category.id)"
        >
          {{ category.name }}
        </option>
      </select>
    </div>

    <p v-if="loading" class="state-message">Memuat paket event...</p>

    <div v-else-if="error" class="state-message error">
      <p>{{ error }}</p>
      <button class="roman-button" @click="loadData">Coba Lagi</button>
    </div>

    <template v-else>
      <div class="results-count">
        Menampilkan {{ filteredPackages.length }} paket
      </div>

      <div v-if="filteredPackages.length" class="package-grid">
        <EventPackageCard
          v-for="item in filteredPackages"
          :key="item.id"
          :package="item"
          @view-detail="openDetail"
        />
      </div>

      <div v-else class="empty-state roman-card">
        Tidak ada paket yang sesuai dengan pencarian Anda.
      </div>
    </template>

    <div
      v-if="selectedPackage"
      class="modal-backdrop"
      @click.self="closeDetail"
    >
      <section
        class="detail-modal roman-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="package-detail-title"
      >
        <button
          class="modal-close"
          aria-label="Tutup detail paket"
          @click="closeDetail"
        >
          ✕
        </button>

        <div class="roman-eyebrow">PACKAGE DETAILS</div>

        <h2 id="package-detail-title" class="roman-title">
          {{ selectedPackage.name }}
        </h2>

        <p class="detail-description">
          {{
            selectedPackage.description || "Paket event Kareem Entertainment."
          }}
        </p>

        <div class="detail-info">
          <div>
            <span>Kategori</span>
            <strong>
              {{ selectedPackage.category?.name || "-" }}
            </strong>
          </div>

          <div>
            <span>Minimum Peserta</span>
            <strong>
              {{ selectedPackage.min_participants ?? "-" }}
            </strong>
          </div>

          <div>
            <span>Maksimum Peserta</span>
            <strong>
              {{ selectedPackage.max_participants ?? "-" }}
            </strong>
          </div>

          <div>
            <span>Harga Dasar</span>
            <strong class="price">
              {{ formatRupiah(selectedPackage.base_price) }}
            </strong>
          </div>
        </div>

        <button
          class="roman-button booking-button"
          @click="bookPackage(selectedPackage)"
        >
          Pilih Paket & Lanjut Booking →
        </button>
      </section>
    </div>
  </CustomerLayout>
</template>

<style scoped>
.catalog-header {
  margin-bottom: 28px;
}

.catalog-header h1 {
  margin: 12px 0;
  font-size: clamp(24px, 4vw, 34px);
}

.catalog-header p,
.catalog-intro p {
  color: #a1a1aa;
  font-size: 13px;
  line-height: 1.8;
}

.catalog-intro {
  padding: 30px;
  background:
    radial-gradient(circle at 100% 50%, #f9731620, transparent 50%), #18181b;
}

.catalog-intro h2 {
  margin: 12px 0;
  font-size: 22px;
}

.catalog-toolbar {
  display: flex;
  gap: 14px;
  margin: 28px 0 16px;
}

.search-input {
  flex: 1;
}

.category-select {
  width: 220px;
}

.results-count {
  margin-bottom: 17px;
  color: #a1a1aa;
  font-size: 12px;
}

.package-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
}

.state-message,
.empty-state {
  padding: 36px;
  color: #a1a1aa;
  text-align: center;
}

.error {
  color: #fca5a5;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 100;
  display: grid;
  place-items: center;
  padding: 20px;
  background: #000b;
}

.detail-modal {
  position: relative;
  width: 100%;
  max-width: 530px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 32px;
  box-shadow: 0 25px 90px #0009;
}

.modal-close {
  position: absolute;
  top: 18px;
  right: 18px;
  border: 0;
  background: transparent;
  color: #a1a1aa;
  font-size: 20px;
}

.detail-modal h2 {
  margin: 16px 0;
  font-size: 24px;
}

.detail-description {
  color: #a1a1aa;
  line-height: 1.8;
  font-size: 13px;
}

.detail-info {
  display: grid;
  gap: 18px;
  margin: 26px 0;
  padding: 22px 0;
  border-top: 1px solid #303036;
  border-bottom: 1px solid #303036;
}

.detail-info div {
  display: flex;
  justify-content: space-between;
  gap: 15px;
}

.detail-info span {
  color: #a1a1aa;
  font-size: 12px;
}

.detail-info strong {
  font-size: 13px;
  text-align: right;
}

.detail-info .price {
  color: #fb923c;
}

.booking-button {
  width: 100%;
}

@media (max-width: 1100px) {
  .package-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 650px) {
  .package-grid {
    grid-template-columns: 1fr;
  }

  .catalog-toolbar {
    flex-direction: column;
  }

  .category-select {
    width: 100%;
  }

  .detail-modal {
    padding: 24px;
  }
}
</style>
