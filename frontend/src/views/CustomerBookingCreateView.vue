
<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import CustomerLayout from '../layouts/CustomerLayout.vue'
import BookingEstimateCard from '../components/BookingEstimateCard.vue'
import {
  getPackages,
  createBooking,
  estimatePrice,
  extractCollection,
  extractData,
} from '../services/bookingApi'

const route = useRoute()
const router = useRouter()

const packages = ref([])
const loadingPackages = ref(true)
const submitting = ref(false)
const estimating = ref(false)

const error = ref('')
const estimateError = ref('')
const estimate = ref(null)

const form = ref({
  event_package_id: '',
  event_date: '',
  participants_count: 100,
})

const selectedPackage = computed(() =>
  packages.value.find(
    item => String(item.id) === String(form.value.event_package_id)
  )
)

const today = new Date()
const localToday = [
  today.getFullYear(),
  String(today.getMonth() + 1).padStart(2, '0'),
  String(today.getDate()).padStart(2, '0'),
].join('-')

const formatRupiah = value =>
  new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))

function buildPayload() {
  return {
    event_package_id: Number(form.value.event_package_id),
    event_date: form.value.event_date,
    participants_count: Number(form.value.participants_count),
  }
}

function validateForm() {
  error.value = ''

  if (!selectedPackage.value) {
    error.value = 'Silakan pilih paket event.'
    return false
  }

  if (!form.value.event_date ||
      form.value.event_date < localToday) {
    error.value = 'Pilih tanggal event yang valid.'
    return false
  }

  const count = Number(form.value.participants_count)
  const min = selectedPackage.value.min_participants
  const max = selectedPackage.value.max_participants

  if (!Number.isInteger(count) || count < 1) {
    error.value = 'Jumlah peserta harus berupa bilangan positif.'
    return false
  }

  if (min != null && count < Number(min)) {
    error.value = `Minimal ${min} peserta untuk paket ini.`
    return false
  }

  if (max != null && count > Number(max)) {
    error.value = `Maksimal ${max} peserta untuk paket ini.`
    return false
  }

  return true
}

async function loadPackages() {
  loadingPackages.value = true

  try {
    const response = await getPackages()
    packages.value = extractCollection(response)

    const requestedId = String(route.query.package_id || '')

    if (packages.value.some(
      item => String(item.id) === requestedId
    )) {
      form.value.event_package_id = requestedId
    }
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Gagal memuat paket event.'
  } finally {
    loadingPackages.value = false
  }
}

watch(form, () => {
  estimate.value = null
  estimateError.value = ''
}, { deep: true })

async function calculateEstimate() {
  if (!validateForm()) return

  estimating.value = true
  estimateError.value = ''

  try {
    const response = await estimatePrice(buildPayload())
    estimate.value = extractData(response)
  } catch (err) {
    estimateError.value =
      err.response?.data?.message ||
      err.message ||
      'Estimasi AI gagal diproses.'
  } finally {
    estimating.value = false
  }
}

async function submitBooking() {
  if (!validateForm() || submitting.value) return

  submitting.value = true

  try {
    const response = await createBooking(buildPayload())
    const data = extractData(response)
    const booking = data?.booking ?? data

    if (booking?.id) {
      router.replace(`/customer/bookings/${booking.id}`)
    } else {
      router.replace('/customer/bookings')
    }
  } catch (err) {
    const errors = err.response?.data?.errors

    error.value = errors
      ? Object.values(errors).flat().join(' ')
      : err.response?.data?.message ||
        'Booking gagal dibuat.'
  } finally {
    submitting.value = false
  }
}

onMounted(loadPackages)
</script>

<template>
  <CustomerLayout>
    <div class="page-heading">
      <div class="roman-eyebrow">CREATE YOUR EVENT</div>
      <h1 class="roman-title">New Booking</h1>
      <p>
        Lengkapi kebutuhan event Anda untuk melanjutkan
        proses pemesanan.
      </p>
    </div>

    <div class="booking-grid">
      <section class="form-card roman-card">
        <div class="roman-eyebrow">01 / EVENT DETAILS</div>
        <h2>Informasi Event</h2>

        <form @submit.prevent="submitBooking">
          <div class="field">
            <label for="package">Paket Event</label>
            <select
              id="package"
              v-model="form.event_package_id"
              class="roman-input"
              :disabled="loadingPackages"
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
            <strong>{{ selectedPackage.name }}</strong>
            <span>
              Harga dasar:
              {{ formatRupiah(selectedPackage.base_price) }}
            </span>
          </div>

          <div class="field">
            <label for="date">Tanggal Event</label>
            <input
              id="date"
              v-model="form.event_date"
              class="roman-input"
              type="date"
              :min="localToday"
              required
            />
          </div>

          <div class="field">
            <label for="participants">Jumlah Peserta</label>
            <input
              id="participants"
              v-model.number="form.participants_count"
              class="roman-input"
              type="number"
              min="1"
              step="1"
              required
            />
          </div>

          <div v-if="error" class="form-error" role="alert">
            {{ error }}
          </div>

          <button
            type="submit"
            class="roman-button submit-button"
            :disabled="submitting || loadingPackages"
          >
            {{
              submitting
                ? 'Menyimpan Booking...'
                : 'Konfirmasi & Buat Booking →'
            }}
          </button>
        </form>
      </section>

      <div class="side-column">
        <BookingEstimateCard
          :estimate="estimate"
          :loading="estimating"
          :error="estimateError"
          @estimate="calculateEstimate"
        />

        <section class="roman-card info-card">
          <div class="roman-eyebrow">BOOKING PROCESS</div>
          <h3>Alur Pemesanan</h3>
          <p>1. Buat booking event.</p>
          <p>2. Admin memeriksa permintaan.</p>
          <p>3. Admin mengirim penawaran final.</p>
          <p>4. Customer menerima penawaran.</p>
          <p>5. Customer melakukan pembayaran.</p>
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
}

.booking-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.5fr) minmax(300px, 1fr);
  gap: 22px;
  align-items: start;
}

.form-card,
.info-card {
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

.form-error {
  margin-bottom: 18px;
  padding: 12px;
  border: 1px solid #7f1d1d;
  border-radius: 8px;
  color: #fca5a5;
  font-size: 12px;
}

.submit-button {
  width: 100%;
}

.side-column {
  display: grid;
  gap: 20px;
}

.info-card h3 {
  margin: 14px 0;
  font-size: 16px;
}

.info-card p {
  color: #a1a1aa;
  font-size: 12px;
  line-height: 1.8;
}

@media (max-width: 900px) {
  .booking-grid {
    grid-template-columns: 1fr;
  }
}
</style>
