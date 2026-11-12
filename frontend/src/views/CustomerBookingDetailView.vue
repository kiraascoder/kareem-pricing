
<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import CustomerLayout from '../layouts/CustomerLayout.vue'
import { getBooking, extractData } from '../services/bookingApi'

const route = useRoute()
const booking = ref(null)
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const response = await getBooking(route.params.id)
    const data = extractData(response)
    booking.value = data?.booking ?? data
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Gagal memuat detail booking.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <CustomerLayout>
    <RouterLink to="/customer/bookings" class="back">
      ← Kembali ke daftar booking
    </RouterLink>

    <div class="roman-eyebrow">BOOKING DETAILS</div>
    <h1 class="roman-title">Detail Booking</h1>

    <div v-if="loading" class="roman-card detail">
      Memuat detail booking...
    </div>

    <div v-else-if="error" class="roman-card detail">
      {{ error }}
    </div>

    <section v-else-if="booking" class="roman-card detail">
      <div class="detail-row">
        <span>Kode Booking</span>
        <strong>
          {{ booking.booking_code || booking.id }}
        </strong>
      </div>

      <div class="detail-row">
        <span>Status</span>
        <strong class="orange">{{ booking.status }}</strong>
      </div>

      <div class="detail-row">
        <span>Tanggal Event</span>
        <strong>{{ booking.event_date || '-' }}</strong>
      </div>

      <div class="detail-row">
        <span>Jumlah Peserta</span>
        <strong>
          {{
            booking.participants_count ??
            booking.participants ??
            '-'
          }}
        </strong>
      </div>

      <p class="note">
        Penawaran harga dan pembayaran akan diintegrasikan
        pada tahap frontend berikutnya.
      </p>
    </section>
  </CustomerLayout>
</template>

<style scoped>
.back {
  display: inline-block;
  margin-bottom: 28px;
  color: #fb923c;
  font-size: 13px;
}

h1 {
  margin: 12px 0 28px;
}

.detail {
  max-width: 750px;
  padding: 26px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 0;
  border-bottom: 1px solid #303036;
}

.detail-row span {
  color: #a1a1aa;
  font-size: 13px;
}

.detail-row strong {
  font-size: 13px;
  text-align: right;
}

.orange {
  color: #fb923c;
}

.note {
  margin-top: 24px;
  color: #a1a1aa;
  font-size: 12px;
  line-height: 1.8;
}
</style>
