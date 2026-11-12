
<script setup>
import { computed, onMounted, ref } from 'vue'
import CustomerLayout from '../layouts/CustomerLayout.vue'
import api from '../services/api'

const bookings = ref([])
const loading = ref(true)
const error = ref('')

const user = computed(() => {
  try {
    return JSON.parse(
      sessionStorage.getItem('kareem_user') || '{}'
    )
  } catch {
    return {}
  }
})

const stats = computed(() => ({
  total: bookings.value.length,
  pending: bookings.value.filter(
    b => ['pending', 'reviewed'].includes(b.status)
  ).length,
  awaiting: bookings.value.filter(
    b => b.status === 'awaiting_payment'
  ).length,
  paid: bookings.value.filter(
    b => b.status === 'paid'
  ).length,
}))

async function loadBookings() {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/bookings')
    const payload = response.data.data

    if (Array.isArray(payload)) {
      bookings.value = payload
    } else if (Array.isArray(payload?.data)) {
      bookings.value = payload.data
    } else {
      throw new Error('Format data booking tidak dikenali.')
    }
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      err.message ||
      'Gagal memuat booking.'
  } finally {
    loading.value = false
  }
}

onMounted(loadBookings)
</script>

<template>
  <CustomerLayout>
    <div class="welcome">
      <div>
        <div class="roman-eyebrow">CUSTOMER OVERVIEW</div>
        <h1 class="roman-title">
          Welcome, {{ user.name || 'Customer' }}.
        </h1>
        <p>Kelola perjalanan event Anda dalam satu tempat.</p>
      </div>
    </div>

    <section class="hero roman-card">
      <div class="hero-copy">
        <div class="roman-eyebrow">
          THE ART OF GREAT EVENTS
        </div>
        <h2 class="roman-title">
          Every Great Event Begins Here.
        </h2>
        <p>
          Temukan paket event terbaik, rencanakan kebutuhan
          Anda, dan dapatkan estimasi harga berbasis AI.
        </p>
        <RouterLink
          to="/customer/packages"
          class="roman-button"
        >
          Jelajahi Paket Event →
        </RouterLink>
      </div>
      <div class="hero-decoration" aria-hidden="true">K</div>
    </section>

    <div class="section-heading">
      <h2>Ringkasan Booking</h2>
      <span>Aktivitas Anda</span>
    </div>

    <p v-if="loading" class="info">
      Memuat data booking...
    </p>

    <div v-else-if="error" class="error">
      {{ error }}
      <button @click="loadBookings">Coba Lagi</button>
    </div>

    <div v-else class="stats-grid">
      <div class="stat-card roman-card">
        <span>Total Booking</span>
        <strong>{{ stats.total }}</strong>
      </div>

      <div class="stat-card roman-card">
        <span>Dalam Review</span>
        <strong>{{ stats.pending }}</strong>
      </div>

      <div class="stat-card roman-card">
        <span>Menunggu Pembayaran</span>
        <strong>{{ stats.awaiting }}</strong>
      </div>

      <div class="stat-card roman-card">
        <span>Sudah Dibayar</span>
        <strong>{{ stats.paid }}</strong>
      </div>
    </div>

    <div class="section-heading">
      <h2>Booking Terbaru</h2>
    </div>

    <div v-if="!loading && !error" class="roman-card recent">
      <div v-if="bookings.length === 0" class="empty">
        Belum ada booking. Mulai dengan memilih paket event.
      </div>

      <div
        v-for="booking in bookings.slice(0, 5)"
        :key="booking.id"
        class="booking-row"
      >
        <div>
          <strong>
            {{ booking.booking_code || `Booking #${booking.id}` }}
          </strong>
          <small>
            {{ booking.event_date || 'Tanggal belum tersedia' }}
          </small>
        </div>
        <span class="status">
          {{ booking.status }}
        </span>
      </div>
    </div>
  </CustomerLayout>
</template>

<style scoped>
.welcome {
  margin-bottom: 28px;
}

.welcome h1 {
  margin: 12px 0 8px;
  font-size: clamp(22px, 3vw, 30px);
}

.welcome p,
.hero p {
  color: #a1a1aa;
  font-size: 13px;
  line-height: 1.8;
}

.hero {
  position: relative;
  display: flex;
  overflow: hidden;
  min-height: 230px;
  align-items: center;
  padding: 35px;
  background:
    radial-gradient(
      circle at 90% 50%,
      #f9731624,
      transparent 45%
    ),
    #19191c;
}

.hero-copy {
  position: relative;
  z-index: 1;
  max-width: 560px;
}

.hero h2 {
  margin: 15px 0 8px;
  font-size: clamp(20px, 3vw, 28px);
}

.hero .roman-button {
  margin-top: 14px;
  font-size: 12px;
}

.hero-decoration {
  position: absolute;
  right: 50px;
  color: #f9731612;
  font-family: 'Cinzel', serif;
  font-size: 220px;
  pointer-events: none;
}

.section-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 32px 0 18px;
}

.section-heading h2 {
  margin: 0;
  font-size: 17px;
}

.section-heading span {
  color: #a1a1aa;
  font-size: 12px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

.stat-card {
  display: flex;
  flex-direction: column;
  gap: 18px;
  padding: 24px;
}

.stat-card span {
  color: #a1a1aa;
  font-size: 12px;
}

.stat-card strong {
  color: #fb923c;
  font-size: 30px;
}

.recent {
  padding: 8px 22px;
}

.booking-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 18px 0;
  border-bottom: 1px solid #303036;
}

.booking-row:last-child {
  border-bottom: 0;
}

.booking-row strong,
.booking-row small {
  display: block;
}

.booking-row strong {
  font-size: 13px;
}

.booking-row small {
  margin-top: 7px;
  color: #a1a1aa;
  font-size: 11px;
}

.status {
  padding: 7px 10px;
  border-radius: 7px;
  background: #f973161a;
  color: #fb923c;
  font-size: 11px;
  white-space: nowrap;
}

.info {
  color: #a1a1aa;
}

.error {
  padding: 16px;
  border: 1px solid #7f1d1d;
  border-radius: 10px;
  color: #fca5a5;
}

.error button {
  margin-left: 12px;
  color: #fb923c;
  background: transparent;
  border: 0;
}

.empty {
  padding: 30px 0;
  color: #a1a1aa;
  font-size: 13px;
  text-align: center;
}

@media (max-width: 1050px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 550px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .hero {
    padding: 24px;
  }

  .hero-decoration {
    display: none;
  }
}
</style>
