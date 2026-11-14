<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import CustomerLayout from "../layouts/CustomerLayout.vue";

import {
  getPackages,
  createBooking,
  extractData,
  extractCollection,
  extractApiError,
} from "../services/bookingApi";

const route = useRoute();
const router = useRouter();

const packages = ref([]);
const loadingPackages = ref(true);
const submitting = ref(false);
const error = ref("");

const form = ref({
  event_package_id: "",
  event_date: "",
  participants_count: 100,
});

const selectedPackage = computed(() =>
  packages.value.find(
    (item) => String(item.id) === String(form.value.event_package_id),
  ),
);

function getLocalToday() {
  const today = new Date();

  return [
    today.getFullYear(),
    String(today.getMonth() + 1).padStart(2, "0"),
    String(today.getDate()).padStart(2, "0"),
  ].join("-");
}

const localToday = getLocalToday();

function formatRupiah(value) {
  if (value === null || value === undefined) {
    return "-";
  }

  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(Number(value));
}

function buildPayload() {
  return {
    event_package_id: Number(form.value.event_package_id),
    event_date: form.value.event_date,
    participants_count: Number(form.value.participants_count),
  };
}

function validateForm() {
  error.value = "";

  if (!selectedPackage.value) {
    error.value = "Silakan pilih paket event.";
    return false;
  }

  if (!form.value.event_date || form.value.event_date < getLocalToday()) {
    error.value = "Pilih tanggal event yang valid.";
    return false;
  }

  const count = Number(form.value.participants_count);

  const min = selectedPackage.value.min_participants;
  const max = selectedPackage.value.max_participants;

  if (!Number.isInteger(count) || count < 1) {
    error.value = "Jumlah peserta harus berupa bilangan bulat positif.";
    return false;
  }

  if (min != null && count < Number(min)) {
    error.value = `Minimal ${min} peserta untuk paket ini.`;
    return false;
  }

  if (max != null && count > Number(max)) {
    error.value = `Maksimal ${max} peserta untuk paket ini.`;
    return false;
  }

  return true;
}

async function loadPackages() {
  loadingPackages.value = true;
  error.value = "";

  try {
    const response = await getPackages();

    packages.value = extractCollection(response);

    const requestedId = String(route.query.package_id || "");

    if (packages.value.some((item) => String(item.id) === requestedId)) {
      form.value.event_package_id = requestedId;
    }
  } catch (err) {
    error.value = extractApiError(err, "Gagal memuat paket event.");
  } finally {
    loadingPackages.value = false;
  }
}

async function submitBooking() {
  if (submitting.value || !validateForm()) {
    return;
  }

  submitting.value = true;
  error.value = "";

  try {
    const response = await createBooking(buildPayload());

    const data = extractData(response);

    const booking = data?.booking ?? data;
    const bookingId = booking?.id;

    if (!bookingId) {
      // Booking mungkin sudah tersimpan.
      // Jangan mengirim ulang secara otomatis.
      await router.replace("/customer/bookings");
      return;
    }

    await router.replace(`/customer/bookings/${bookingId}`);
  } catch (err) {
    error.value = extractApiError(err, "Booking gagal dibuat.");
  } finally {
    submitting.value = false;
  }
}

onMounted(loadPackages);
</script>

<template>
  <CustomerLayout>
    <div class="page-heading">
      <div class="roman-eyebrow">CREATE YOUR EVENT</div>

      <h1 class="roman-title">New Booking</h1>

      <p>Lengkapi kebutuhan event Anda untuk melanjutkan proses pemesanan.</p>
    </div>

    <div class="booking-grid">
      <section class="form-card roman-card">
        <div class="roman-eyebrow">01 / EVENT DETAILS</div>

        <h2>Informasi Event</h2>

        <form @submit.prevent="submitBooking">
          <div class="field">
            <label for="package"> Paket Event </label>

            <select
              id="package"
              v-model="form.event_package_id"
              class="roman-input"
              :disabled="loadingPackages || submitting"
              required
            >
              <option value="">Pilih paket event</option>

              <option
                v-for="item in packages"
                :key="item.id"
                :value="String(item.id)"
              >
                {{ item.name }}
              </option>
            </select>
          </div>

          <div v-if="selectedPackage" class="package-summary">
            <strong>
              {{ selectedPackage.name }}
            </strong>

            <span>
              Harga dasar:
              {{ formatRupiah(selectedPackage.base_price) }}
            </span>

            <span
              v-if="
                selectedPackage.min_participants != null ||
                selectedPackage.max_participants != null
              "
              class="participant-info"
            >
              Kapasitas:
              {{ selectedPackage.min_participants ?? 1 }}
              –
              {{ selectedPackage.max_participants ?? "Tidak dibatasi" }}
              peserta
            </span>
          </div>

          <div class="field">
            <label for="date"> Tanggal Event </label>

            <input
              id="date"
              v-model="form.event_date"
              class="roman-input"
              type="date"
              :min="localToday"
              :disabled="submitting"
              required
            />
          </div>

          <div class="field">
            <label for="participants"> Jumlah Peserta </label>

            <input
              id="participants"
              v-model.number="form.participants_count"
              class="roman-input"
              type="number"
              min="1"
              step="1"
              :disabled="submitting"
              required
            />
          </div>

          <div v-if="error" class="form-error" role="alert">
            {{ error }}
          </div>

          <button
            type="submit"
            class="roman-button submit-button"
            :disabled="submitting || loadingPackages || packages.length === 0"
          >
            {{
              submitting
                ? "Menyimpan Booking..."
                : "Konfirmasi & Buat Booking →"
            }}
          </button>
        </form>
      </section>

      <div class="side-column">
        <section class="roman-card estimate-info-card">
          <div class="roman-eyebrow">AI PRICE ESTIMATION</div>

          <h3>Estimasi Harga AI</h3>

          <div class="estimate-symbol">◈</div>

          <p>Estimasi harga menggunakan sistem AI Kareem Pricing.</p>

          <div class="estimate-notice">
            Estimasi AI dapat dilihat setelah booking berhasil dibuat.
          </div>

          <div class="estimate-steps">
            <div>
              <span>01</span>
              <p>Lengkapi informasi event</p>
            </div>

            <div>
              <span>02</span>
              <p>Konfirmasi dan buat booking</p>
            </div>

            <div>
              <span>03</span>
              <p>Lihat estimasi pada detail booking</p>
            </div>
          </div>
        </section>

        <section class="roman-card info-card">
          <div class="roman-eyebrow">BOOKING PROCESS</div>

          <h3>Alur Pemesanan</h3>

          <p>1. Customer membuat booking event.</p>
          <p>2. Sistem menyediakan estimasi AI.</p>
          <p>3. Admin memeriksa permintaan.</p>
          <p>4. Admin mengirim penawaran final.</p>
          <p>5. Customer menerima penawaran.</p>
          <p>6. Customer melakukan pembayaran.</p>
        </section>
      </div>
    </div>
  </CustomerLayout>
</template>

<style scoped>
.page-heading {
  margin-bottom: 28px;
}

.page-heading h1 {
  margin: 12px 0;
  font-size: 30px;
}

.page-heading p {
  color: #a1a1aa;
  font-size: 13px;
  line-height: 1.7;
}

.booking-grid {
  display: grid;
  grid-template-columns:
    minmax(0, 1.5fr)
    minmax(300px, 1fr);
  gap: 22px;
  align-items: start;
}

.form-card,
.info-card,
.estimate-info-card {
  padding: 28px;
}

.form-card h2 {
  margin: 14px 0 28px;
  font-size: 19px;
}

.field {
  margin-bottom: 22px;
}

.field label {
  display: block;
  margin-bottom: 10px;
  font-size: 12px;
  font-weight: 600;
}

.roman-input {
  width: 100%;
  min-height: 45px;
  box-sizing: border-box;
}

.package-summary {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin: -5px 0 22px;
  padding: 15px;
  border-left: 3px solid #f97316;
  background: #f973160b;
}

.package-summary strong {
  font-size: 13px;
}

.package-summary span {
  color: #fb923c;
  font-size: 12px;
}

.package-summary .participant-info {
  color: #a1a1aa;
}

.form-error {
  margin-bottom: 18px;
  padding: 12px;
  border: 1px solid #7f1d1d;
  border-radius: 8px;
  background: #ef44440a;
  color: #fca5a5;
  font-size: 12px;
  line-height: 1.7;
}

.submit-button {
  width: 100%;
  min-height: 46px;
}

.side-column {
  display: grid;
  gap: 20px;
}

.info-card h3,
.estimate-info-card h3 {
  margin: 14px 0;
  font-size: 16px;
}

.info-card p,
.estimate-info-card > p {
  color: #a1a1aa;
  font-size: 12px;
  line-height: 1.8;
}

.estimate-symbol {
  display: grid;
  place-items: center;
  height: 90px;
  margin: 15px 0;
  color: #f97316;
  font-size: 52px;
}

.estimate-notice {
  margin-top: 20px;
  padding: 15px;
  border: 1px solid #f9731633;
  border-radius: 8px;
  background: #f973160b;
  color: #fdba74;
  font-size: 12px;
  line-height: 1.8;
}

.estimate-steps {
  display: grid;
  gap: 15px;
  margin-top: 25px;
}

.estimate-steps > div {
  display: flex;
  align-items: center;
  gap: 12px;
}

.estimate-steps span {
  display: grid;
  place-items: center;
  width: 31px;
  height: 31px;
  flex-shrink: 0;
  border: 1px solid #f9731644;
  border-radius: 8px;
  color: #fb923c;
  font-size: 11px;
}

.estimate-steps p {
  margin: 0;
  color: #d4d4d8;
  font-size: 12px;
}

@media (max-width: 900px) {
  .booking-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .form-card,
  .info-card,
  .estimate-info-card {
    padding: 20px;
  }
}
</style>
