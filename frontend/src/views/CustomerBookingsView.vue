

<script setup>
import { onMounted, ref } from 'vue'
import CustomerLayout from '../layouts/CustomerLayout.vue'
import { getBookings, extractCollection } from '../services/bookingApi'

const bookings = ref([])
const loading = ref(true)
const error = ref('')

async function loadBookings() {
  loading.value = true
  error.value = ''

  try {
    const response = await getBookings()
    bookings.value = extractCollection(response)
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Gagal memuat daftar booking.'
  } finally {
    loading.value = false
  }
}

onMounted(loadBookings)
</script>

<template>
  <CustomerLayout>
    <div class="heading">
      <div>
        <div class="roman-eyebrow">MY RESERVATIONS</div>
        <h1 class="roman-title">Booking Saya</h1>
      </div>
      <RouterLink
        to="/customer/bookings/create"
        class="roman-button"
      >
        + Booking Baru
      </RouterLink>
    </div>

    <div v-if="loading" class="roman-card state">
      Memuat daftar booking...
    </div>

    <div v-else-if="error" class="roman-card state">
      {{ error }}
      <button class="retry" @click="loadBookings">
        Coba Lagi
      </button>
    </div>

    <div v-else-if="bookings.length === 0" class="roman-card state">
      Belum ada booking.
    </div>

    <div v-else class="booking-list">
      <RouterLink
        v-for="booking in bookings"
        :key="booking.id"
        :to="`/customer/bookings/${booking.id}`"
        class="roman-card booking-item"
      >
        <div>
          <strong>
            {{ booking.booking_code || `Booking #${booking.id}` }}
          </strong>
          <p>
            {{ booking.event_date || 'Tanggal belum tersedia' }}
          </p>
        </div>
        <div class="booking-right">
          <span class="status">{{ booking.status }}</span>
          <span class="arrow">→</span>
        </div>
      </RouterLink>
    </div>
  </CustomerLayout>
</template>

<style scoped>
.heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  margin-bottom: 28px;
}

.heading h1 {
  margin: 12px 0;
  font-size: 27px;
}

.booking-list {
  display: grid;
  gap: 12px;
}

.booking-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 22px;
  transition: border-color .2s;
}

.booking-item:hover {
  border-color: #f97316;
}

.booking-item strong {
  font-size: 14px;
}

.booking-item p {
  margin-bottom: 0;
  color: #a1a1aa;
  font-size: 12px;
}

.booking-right {
  display: flex;
  align-items: center;
  gap: 20px;
}

.status {
  padding: 8px 12px;
  border-radius: 7px;
  background: #f973161a;
  color: #fb923c;
  font-size: 11px;
}

.arrow {
  color: #f97316;
}

.state {
  padding: 35px;
  color: #a1a1aa;
  text-align: center;
}

.retry {
  margin-left: 10px;
  border: 0;
  background: transparent;
  color: #fb923c;
}

@media (max-width: 550px) {
  .heading {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
