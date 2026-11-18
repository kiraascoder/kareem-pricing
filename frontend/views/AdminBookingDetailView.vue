<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import AdminLayout from '../layouts/AdminLayout.vue'
import { getAdminBooking, reviewAdminBooking, createAdminPriceQuote, unwrapAdminBooking } from '../services/adminBookingApi'
import { getPriceEstimate, extractData, extractApiError } from '../services/bookingApi'
import { getAdminAiRecommendation } from '../services/adminBookingAiApi'
import AiRecommendationCard from '../components/admin/AiRecommendationCard.vue'

const route = useRoute()
const booking = ref(null)
const estimate = ref(null)
const aiPrediction = ref(null)
const loading = ref(true)
const aiLoading = ref(false)
const processing = ref(false)
const sendingQuote = ref(false)
const error = ref('')
const estimateError = ref('')
const aiError = ref('')
const actionError = ref('')
const successMessage = ref('')
const form = ref({ final_price: '', admin_notes: '' })
let loadSequence = 0

const isPending = computed(() => booking.value?.status === 'pending')
const isReviewed = computed(() => booking.value?.status === 'reviewed')
const estimateData = computed(() => {
  const data = estimate.value
  return data?.price_estimate ?? data?.recommendation ?? data?.estimate ?? data ?? null
})
const recommendedPrice = computed(() => estimateData.value?.estimated_price ?? aiPrediction.value?.recommended_price ?? null)
const basePrice = computed(() => estimateData.value?.base_price ?? aiPrediction.value?.base_price ?? null)
const calculatedAt = computed(() => estimateData.value?.calculated_at ?? aiPrediction.value?.calculated_at ?? null)
const priceQuote = computed(() => booking.value?.price_quote ?? booking.value?.priceQuote ?? null)
const hasPriceQuote = computed(() => Boolean(priceQuote.value))
const canCreateQuote = computed(() => isReviewed.value && !hasPriceQuote.value && recommendedPrice.value !== null)
const eventPackage = computed(() => booking.value?.event_package ?? booking.value?.eventPackage ?? null)

function formatRupiah(value) {
  if (value === null || value === undefined || value === '') return '-'
  const number = Number(value)
  if (!Number.isFinite(number)) return '-'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(number)
}
function formatDate(value) {
  if (!value) return '-'
  const match = String(value).slice(0, 10).match(/^(\d{4})-(\d{2})-(\d{2})$/)
  if (!match) return '-'
  const [_, y, m, d] = match
  const date = new Date(Date.UTC(+y, +m - 1, +d))
  if (date.getUTCFullYear() !== +y || date.getUTCMonth() !== +m - 1 || date.getUTCDate() !== +d) return '-'
  return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' }).format(date)
}
function formatDateTime(value) {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '-'
  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Makassar' }).format(date)
}
function statusLabel(status) {
  return ({ pending: 'Menunggu Review', reviewed: 'Sudah Direview', cancelled: 'Dibatalkan', awaiting_payment: 'Menunggu Pembayaran', paid: 'Pembayaran Terverifikasi', completed: 'Selesai' })[status] || String(status || '-').replaceAll('_', ' ')
}
function quoteStatusLabel(status) {
  return ({ sent: 'Terkirim', accepted: 'Diterima Customer', rejected: 'Ditolak Customer' })[status] || String(status || '-').replaceAll('_', ' ')
}
function getErrorMessage(err, fallback) {
  const errors = err.response?.data?.errors
  if (errors && typeof errors === 'object') {
    const first = Object.values(errors).flat().find(x => typeof x === 'string')
    if (first) return first
  }
  return extractApiError(err, fallback)
}
async function fetchBooking(id) {
  const response = await getAdminBooking(id)
  const data = unwrapAdminBooking(response)
  const result = data?.booking ?? data
  if (!result?.id) throw new Error('Data booking tidak ditemukan dalam respons API.')
  return result
}
async function fetchEstimate(id, seq = loadSequence) {
  try {
    const response = await getPriceEstimate(id)
    if (seq !== loadSequence) return
    estimate.value = extractData(response)
    estimateError.value = ''
  } catch (err) {
    if (seq !== loadSequence) return
    estimate.value = null
    estimateError.value = err.response?.status === 404 ? '' : getErrorMessage(err, 'Gagal memuat rekomendasi harga AI.')
  }
}
async function loadAiRecommendation(id, seq = loadSequence) {
  aiLoading.value = true
  aiError.value = ''
  try {
    const response = await getAdminAiRecommendation(id)
    if (seq !== loadSequence) return
    aiPrediction.value = response.data?.data?.prediction ?? null
  } catch (err) {
    if (seq !== loadSequence) return
    aiPrediction.value = null
    aiError.value = err.response?.status === 404 ? '' : getErrorMessage(err, 'Gagal memuat rincian AI.')
  } finally {
    if (seq === loadSequence) aiLoading.value = false
  }
}
async function loadBooking() {
  const seq = ++loadSequence
  const id = route.params.id
  loading.value = true
  error.value = ''
  estimateError.value = ''
  aiError.value = ''
  actionError.value = ''
  successMessage.value = ''
  booking.value = null
  estimate.value = null
  aiPrediction.value = null
  try {
    const result = await fetchBooking(id)
    if (seq !== loadSequence) return
    booking.value = result
    await Promise.all([fetchEstimate(id, seq), loadAiRecommendation(id, seq)])
  } catch (err) {
    if (seq === loadSequence) error.value = getErrorMessage(err, 'Gagal memuat detail booking.')
  } finally {
    if (seq === loadSequence) loading.value = false
  }
}
async function submitReview(status) {
  if (!isPending.value || processing.value || sendingQuote.value) return
  const message = status === 'reviewed' ? 'Tandai booking ini sebagai sudah direview?' : 'Yakin ingin membatalkan booking ini?'
  if (!window.confirm(message)) return
  processing.value = true
  actionError.value = ''
  successMessage.value = ''
  const id = booking.value.id
  try {
    const response = await reviewAdminBooking(id, { status })
    const updated = unwrapAdminBooking(response)
    booking.value = { ...booking.value, ...(updated?.booking ?? updated) }
    successMessage.value = status === 'reviewed' ? 'Booking berhasil direview.' : 'Booking berhasil dibatalkan.'
    try {
      booking.value = await fetchBooking(id)
      await Promise.all([fetchEstimate(id), loadAiRecommendation(id)])
    } catch { /* Mutasi berhasil, pertahankan status terbaru. */ }
  } catch (err) {
    actionError.value = getErrorMessage(err, 'Gagal memperbarui status booking.')
  } finally { processing.value = false }
}
async function submitPriceQuote() {
  if (sendingQuote.value || processing.value) return
  actionError.value = ''
  successMessage.value = ''
  if (!canCreateQuote.value) {
    actionError.value = 'Booking harus berstatus reviewed, memiliki estimasi AI, dan belum mempunyai penawaran.'
    return
  }
  const price = Number(form.value.final_price)
  if (!Number.isFinite(price) || price <= 0 || price > 9999999999999.99) {
    actionError.value = 'Harga final tidak valid atau melebihi batas yang diizinkan.'
    return
  }
  const notes = form.value.admin_notes.trim()
  if (notes.length > 2000) {
    actionError.value = 'Catatan admin maksimal 2000 karakter.'
    return
  }
  if (!window.confirm(`Kirim penawaran sebesar ${formatRupiah(price)} kepada customer?`)) return
  sendingQuote.value = true
  const id = booking.value.id
  try {
    const response = await createAdminPriceQuote(id, { final_price: price, admin_notes: notes || null })
    const created = unwrapAdminBooking(response)
    booking.value = { ...booking.value, price_quote: created?.price_quote ?? created?.quote ?? created }
    form.value = { final_price: '', admin_notes: '' }
    successMessage.value = 'Penawaran harga berhasil dikirim kepada customer.'
    try { booking.value = await fetchBooking(id) } catch { /* Mutasi berhasil. */ }
  } catch (err) {
    actionError.value = getErrorMessage(err, 'Gagal mengirim penawaran harga.')
  } finally { sendingQuote.value = false }
}
function useRecommendedPrice() {
  if (recommendedPrice.value !== null) form.value.final_price = String(recommendedPrice.value)
}
onMounted(loadBooking)
watch(() => route.params.id, loadBooking)
</script>

<template>
  <AdminLayout>
    <RouterLink to="/admin/bookings" class="back-link">← Kembali ke Manajemen Booking</RouterLink>
    <div class="roman-eyebrow">BOOKING MANAGEMENT</div>
    <h1 class="roman-title">Detail Booking Admin</h1>

    <div v-if="loading" class="roman-card state-box">Memuat informasi booking...</div>
    <div v-else-if="error" class="roman-card state-box">
      <p class="error">{{ error }}</p>
      <button class="refresh-button" @click="loadBooking">Coba Lagi</button>
    </div>
    <div v-else-if="booking" class="detail-grid">
      <div class="main-column">
        <section class="roman-card section-card">
          <div class="roman-eyebrow">EVENT INFORMATION</div>
          <h2>Informasi Pemesanan</h2>
          <div class="info-row"><span>Kode Booking</span><strong>{{ booking.booking_code || booking.id }}</strong></div>
          <div class="info-row"><span>Status</span><strong class="orange">{{ statusLabel(booking.status) }}</strong></div>
          <div class="info-row"><span>Customer</span><strong>{{ booking.user?.name || '-' }}</strong></div>
          <div class="info-row"><span>Email Customer</span><strong>{{ booking.user?.email || '-' }}</strong></div>
          <div class="info-row"><span>Telepon</span><strong>{{ booking.user?.phone || '-' }}</strong></div>
          <div class="info-row"><span>Paket Event</span><strong>{{ eventPackage?.name || '-' }}</strong></div>
          <div class="info-row"><span>Kategori Event</span><strong>{{ eventPackage?.category?.name || '-' }}</strong></div>
          <div class="info-row"><span>Tanggal Event</span><strong>{{ formatDate(booking.event_date) }}</strong></div>
          <div class="info-row"><span>Lokasi Event</span><strong>{{ booking.event_location || '-' }}</strong></div>
          <div class="info-row"><span>Jumlah Peserta</span><strong>{{ booking.participants ?? '-' }}</strong></div>
          <div class="notes"><span>Catatan Customer</span><p>{{ booking.customer_notes || 'Tidak ada catatan tambahan.' }}</p></div>
        </section>

        <section class="roman-card section-card">
          <div class="roman-eyebrow">AI PRICE RECOMMENDATION</div>
          <h2>Rekomendasi Harga AI</h2>
          <p v-if="estimateError" class="error">{{ estimateError }}</p>
          <p v-if="aiError" class="error">{{ aiError }}</p>
          <div v-if="aiLoading" class="muted">Memuat rincian Random Forest dan Fuzzy Sugeno...</div>
          <AiRecommendationCard v-else-if="aiPrediction" :prediction="aiPrediction" />
          <template v-else-if="recommendedPrice !== null">
            <div class="estimate-box"><span>Harga Rekomendasi AI</span><strong>{{ formatRupiah(recommendedPrice) }}</strong></div>
            <div class="info-row"><span>Harga Dasar</span><strong>{{ formatRupiah(basePrice) }}</strong></div>
            <div v-if="calculatedAt" class="info-row"><span>Waktu Perhitungan</span><strong>{{ formatDateTime(calculatedAt) }}</strong></div>
            <p class="muted">Rincian model belum tersedia. Rekomendasi digunakan sebagai pertimbangan admin sebelum menetapkan harga final.</p>
          </template>
          <p v-else class="muted">Estimasi harga AI belum tersedia untuk booking ini.</p>
        </section>

        <section v-if="hasPriceQuote" class="roman-card section-card">
          <div class="roman-eyebrow">PRICE QUOTATION</div>
          <h2>Penawaran Harga Terkirim</h2>
          <div class="info-row"><span>Harga Estimasi</span><strong>{{ formatRupiah(priceQuote.estimated_price) }}</strong></div>
          <div class="info-row"><span>Harga Final</span><strong class="orange">{{ formatRupiah(priceQuote.final_price) }}</strong></div>
          <div class="info-row"><span>Status Penawaran</span><strong class="orange">{{ quoteStatusLabel(priceQuote.status) }}</strong></div>
          <div v-if="priceQuote.created_at" class="info-row"><span>Tanggal Penawaran</span><strong>{{ formatDateTime(priceQuote.created_at) }}</strong></div>
          <div class="notes"><span>Catatan Admin</span><p>{{ priceQuote.admin_notes || 'Tidak ada catatan admin.' }}</p></div>
          <p class="muted">Penawaran sudah tersimpan dan tidak dapat dikirim ulang untuk booking yang sama.</p>
        </section>

        <section v-else-if="isReviewed" class="roman-card section-card">
          <div class="roman-eyebrow">OFFICIAL PRICE QUOTATION</div>
          <h2>Buat Penawaran Harga</h2>
          <p class="muted">Tentukan harga final berdasarkan rekomendasi AI dan kebutuhan event.</p>
          <form @submit.prevent="submitPriceQuote">
            <div class="field">
              <label for="final_price">Harga Final (Rp)</label>
              <div class="price-input-row">
                <input id="final_price" v-model="form.final_price" class="roman-input" type="number" min="0.01" step="any" placeholder="Masukkan harga final" :disabled="sendingQuote || !canCreateQuote" required />
                <button type="button" class="use-estimate-button" :disabled="sendingQuote || recommendedPrice === null" @click="useRecommendedPrice">Gunakan Harga AI</button>
              </div>
            </div>
            <div class="field">
              <label for="admin_notes">Catatan Admin (Opsional)</label>
              <textarea id="admin_notes" v-model="form.admin_notes" class="roman-input notes-input" rows="5" maxlength="2000" placeholder="Tuliskan rincian atau ketentuan penawaran..." :disabled="sendingQuote || !canCreateQuote"></textarea>
              <span class="character-count">{{ form.admin_notes.length }}/2000</span>
            </div>
            <p v-if="!canCreateQuote" class="warning-text">Penawaran hanya dapat dikirim setelah rekomendasi harga AI tersedia.</p>
            <button type="submit" class="primary-button" :disabled="sendingQuote || !canCreateQuote">{{ sendingQuote ? 'Mengirim Penawaran...' : 'Kirim Penawaran Harga →' }}</button>
          </form>
        </section>
        <div v-if="successMessage" class="success-box" role="status">{{ successMessage }}</div>
        <div v-if="actionError" class="error-box" role="alert">{{ actionError }}</div>
      </div>

      <aside class="roman-card section-card">
        <div class="roman-eyebrow">ADMIN ACTIONS</div>
        <h2>Tindak Lanjut Booking</h2>
        <p class="muted">Periksa informasi pemesanan dan catatan customer sebelum mengambil keputusan.</p>
        <template v-if="isPending">
          <button class="primary-button" :disabled="processing || sendingQuote" @click="submitReview('reviewed')">{{ processing ? 'Memproses...' : 'Setujui Review Booking' }}</button>
          <button class="danger-button" :disabled="processing || sendingQuote" @click="submitReview('cancelled')">Batalkan Booking</button>
        </template>
        <div v-else class="status-info"><span>Status Booking</span><strong class="orange">{{ statusLabel(booking.status) }}</strong></div>
        <div v-if="hasPriceQuote" class="status-info"><span>Status Penawaran</span><strong>{{ quoteStatusLabel(priceQuote.status) }}</strong></div>
        <button class="refresh-button" :disabled="loading || processing || sendingQuote" @click="loadBooking">Perbarui Detail</button>
      </aside>
    </div>
  </AdminLayout>
</template>

<style scoped>
.back-link { display:inline-block; margin-bottom:25px; color:#fb923c; font-size:13px; text-decoration:none; }
h1 { margin:12px 0 28px; }
.detail-grid { display:grid; grid-template-columns:minmax(0,1.7fr) minmax(280px,1fr); gap:22px; align-items:start; }
.main-column { display:grid; gap:22px; min-width:0; }
.section-card { padding:28px; min-width:0; }
.section-card h2 { margin:14px 0 24px; font-size:19px; }
.info-row { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; padding:17px 0; border-bottom:1px solid #303036; }
.info-row span,.notes>span { color:#a1a1aa; font-size:13px; }
.info-row strong { color:#fafaf9; font-size:13px; text-align:right; overflow-wrap:anywhere; }
.info-row strong.orange,.orange { color:#fb923c; }
.notes { padding-top:22px; }
.notes p { margin:12px 0 0; padding:16px; border:1px solid #303036; border-radius:8px; background:#111113; color:#d4d4d8; font-size:13px; line-height:1.8; white-space:pre-wrap; overflow-wrap:anywhere; }
.estimate-box { display:flex; flex-direction:column; gap:10px; padding:22px; border:1px solid #f9731633; border-radius:8px; background:#f973160b; }
.estimate-box span { color:#a1a1aa; font-size:12px; }
.estimate-box strong { color:#fb923c; font-size:clamp(22px,3vw,30px); overflow-wrap:anywhere; }
.muted { color:#a1a1aa; font-size:13px; line-height:1.8; }
.field { margin-bottom:22px; }
.field label { display:block; margin-bottom:10px; font-size:12px; font-weight:600; }
.field .roman-input { width:100%; box-sizing:border-box; }
.price-input-row { display:flex; gap:10px; align-items:stretch; }
.price-input-row input { min-width:0; flex:1; }
.use-estimate-button { flex-shrink:0; padding:10px 14px; border:1px solid #f97316; border-radius:8px; background:transparent; color:#fb923c; font-size:12px; cursor:pointer; }
.notes-input { min-height:120px; padding:14px; resize:vertical; font-family:inherit; line-height:1.7; }
.character-count { display:block; margin-top:8px; color:#71717a; font-size:11px; text-align:right; }
.primary-button,.danger-button,.refresh-button { width:100%; padding:13px 16px; border-radius:9px; cursor:pointer; font-size:13px; transition:.2s; }
.primary-button { border:1px solid #f97316; background:#f97316; color:#fff; font-weight:600; }
.danger-button { margin-top:12px; border:1px solid #7f1d1d; background:transparent; color:#fca5a5; }
.refresh-button { margin-top:24px; border:1px solid #f97316; background:transparent; color:#fb923c; }
button:disabled { opacity:.5; cursor:not-allowed; }
.error-box,.success-box { padding:14px; border-radius:8px; font-size:12px; line-height:1.7; }
.error-box { border:1px solid #7f1d1d; color:#fca5a5; }
.success-box { border:1px solid #166534; color:#86efac; }
.state-box { padding:30px; color:#a1a1aa; }
.error { color:#fca5a5; }
.warning-text { margin-bottom:20px; color:#fdba74; font-size:12px; line-height:1.7; }
.status-info { display:flex; flex-direction:column; gap:10px; padding:18px 0; border-bottom:1px solid #303036; }
.status-info span { color:#a1a1aa; font-size:12px; }
.status-info strong { font-size:13px; }
@media(max-width:900px) { .detail-grid { grid-template-columns:1fr; } }
@media(max-width:600px) { .section-card { padding:20px; } .price-input-row { flex-direction:column; } .use-estimate-button { width:100%; } }
</style>
