<script setup>
import { computed, onMounted, ref, reactive } from "vue";
import AdminLayout from "../layouts/AdminLayout.vue";
import { historicalEventPriceApi as service } from "../services/historicalEventPriceApi";

const rows = ref([]);
const categories = ref([]);
const loading = ref(false);
const busy = ref(false);
const error = ref("");
const notice = ref("");
const modalError = ref("");
const editing = ref(null);
const modal = ref(false);
const removeTarget = ref(null);
const page = reactive({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0 });
const filter = reactive({ search: "", jenis_acara: "", date_from: "", date_to: "", per_page: 10 });
const form = reactive({ jenis_acara: "", tanggal_pelaksanaan: "", harga_estimasi: "" });
const today = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
const canEdit = (r) => String(r?.id_pemesanan || "").startsWith("MAN-");
const rupiah = (v) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 2 }).format(Number(v || 0));
const pages = computed(() => Array.from({ length: Math.min(5, page.last_page) }, (_, i) => Math.max(1, Math.min(page.current_page - 2, page.last_page - 4)) + i).filter((p) => p <= page.last_page));
function message(e) { return e.response?.data?.message || e.message || "Permintaan gagal."; }
let sequence = 0;
async function load(target = 1) {
  const seq = ++sequence;
  loading.value = true; error.value = "";
  try {
    const { data } = await service.list({ ...filter, page: target });
    if (seq !== sequence) return;
    if (!Array.isArray(data?.data?.data)) throw new Error("Format data API tidak sesuai.");
    rows.value = data.data.data;
    Object.assign(page, { current_page: data.data.current_page, last_page: data.data.last_page, total: data.data.total, from: data.data.from || 0, to: data.data.to || 0 });
  } catch (e) { if (seq === sequence) { error.value = message(e); rows.value = []; } }
  finally { if (seq === sequence) loading.value = false; }
}
async function loadCategories() {
  try { const { data } = await service.categories(); categories.value = data.data || []; }
  catch (e) { error.value = message(e); }
}
function openForm(record = null) {
  editing.value = record?.id || null;
  Object.assign(form, { jenis_acara: record?.jenis_acara || "", tanggal_pelaksanaan: String(record?.tanggal_pelaksanaan || "").slice(0, 10), harga_estimasi: record?.harga_estimasi || "" });
  modalError.value = ""; modal.value = true;
}
async function save() {
  if (busy.value) return;
  busy.value = true; modalError.value = "";
  try {
    const payload = { jenis_acara: form.jenis_acara.trim(), tanggal_pelaksanaan: form.tanggal_pelaksanaan, harga_estimasi: Number(form.harga_estimasi) };
    if (editing.value) await service.update(editing.value, payload);
    else await service.create(payload);
    modal.value = false; notice.value = "Data historis berhasil disimpan.";
    await Promise.all([load(editing.value ? page.current_page : 1), loadCategories()]);
  } catch (e) { modalError.value = message(e); }
  finally { busy.value = false; }
}
async function remove() {
  if (!removeTarget.value || busy.value) return;
  busy.value = true; modalError.value = "";
  try {
    await service.remove(removeTarget.value.id);
    removeTarget.value = null; notice.value = "Data historis berhasil dihapus.";
    await Promise.all([load(rows.value.length === 1 && page.current_page > 1 ? page.current_page - 1 : page.current_page), loadCategories()]);
  } catch (e) { modalError.value = message(e); }
  finally { busy.value = false; }
}
onMounted(() => Promise.all([load(), loadCategories()]));
</script>

<template>
  <AdminLayout>
    <div class="historical-page">
      <header class="heading">
        <div><div class="eyebrow">DATA MANAGEMENT</div><h1 class="roman-title">Historical Event Price</h1><p>Data historis harga estimasi untuk penelitian Kareem Pricing.</p></div>
        <button class="primary" @click="openForm()">+ Tambah Histori</button>
      </header>
      <div v-if="error" class="alert" role="alert">{{ error }}</div>
      <div v-if="notice" class="notice" role="status">{{ notice }} <button @click="notice = ''">×</button></div>
      <div class="summary">
        <div class="tile"><small>TOTAL DATA HISTORIS</small><strong>{{ page.total }}</strong><span>Sesuai filter aktif</span></div>
        <div class="tile"><small>DATASET PENELITIAN</small><strong class="small-value">rf-60-v1</strong><span>Data asli terkunci dari perubahan</span></div>
        <div class="tile"><small>SUMBER DATA</small><strong class="small-value">Dataset / Manual</strong><span>Data baru menggunakan kode MAN-</span></div>
      </div>
      <section class="panel"><h2>Filter Data</h2>
        <form class="filters" @submit.prevent="load(1)">
          <label>Cari ID / Kategori<input v-model="filter.search" placeholder="Cari ID pemesanan atau jenis acara" /></label>
          <label>Jenis Acara<select v-model="filter.jenis_acara"><option value="">Semua Jenis</option><option v-for="c in categories" :key="c" :value="c">{{ c }}</option></select></label>
          <label>Tanggal Mulai<input v-model="filter.date_from" type="date" /></label>
          <label>Tanggal Akhir<input v-model="filter.date_to" type="date" /></label>
          <label>Baris per Halaman<select v-model.number="filter.per_page"><option :value="10">10</option><option :value="20">20</option><option :value="50">50</option><option :value="100">100</option></select></label>
          <div class="actions"><button class="primary" type="submit">Terapkan</button><button class="secondary" type="button" @click="Object.assign(filter, { search: '', jenis_acara: '', date_from: '', date_to: '', per_page: 10 }); load(1)">Reset</button></div>
        </form>
      </section>
      <section class="panel"><div class="panel-top"><h2>Daftar Harga Historis</h2><button class="secondary" :disabled="loading" @click="load(page.current_page)">Refresh</button></div>
        <div class="table-scroll"><table><thead><tr><th>No.</th><th>ID Pemesanan</th><th>Jenis Acara</th><th>Tanggal</th><th>Hari</th><th>Kategori Hari</th><th>Harga Estimasi</th><th>Sumber</th><th>Aksi</th></tr></thead>
        <tbody><tr v-if="loading"><td colspan="9" class="empty">Memuat data...</td></tr><tr v-else-if="!rows.length"><td colspan="9" class="empty">Tidak ada data atau akses API belum berhasil.</td></tr><tr v-for="(r, i) in loading ? [] : rows" :key="r.id"><td>{{ (page.current_page - 1) * filter.per_page + i + 1 }}</td><td>{{ r.id_pemesanan }}</td><td>{{ r.jenis_acara }}</td><td>{{ String(r.tanggal_pelaksanaan).slice(0, 10) }}</td><td>{{ r.hari_dalam_minggu }}</td><td>{{ r.kategori_hari }}</td><td class="price">{{ rupiah(r.harga_estimasi) }}</td><td><span class="pill" :class="canEdit(r) ? 'manual' : ''">{{ canEdit(r) ? 'Manual' : 'Dataset' }}</span></td><td><div v-if="canEdit(r)" class="row-actions"><button class="secondary" @click="openForm(r)">Edit</button><button class="danger-outline" @click="modalError = ''; removeTarget = r">Hapus</button></div><span v-else class="muted">Terkunci</span></td></tr></tbody></table></div>
        <footer class="pagination"><span>Menampilkan {{ page.from }}–{{ page.to }} dari {{ page.total }} data</span><div><button class="secondary" :disabled="loading || page.current_page <= 1" @click="load(page.current_page - 1)">Sebelumnya</button><button v-for="n in pages" :key="n" class="secondary" :class="{ selected: n === page.current_page }" :disabled="loading" @click="load(n)">{{ n }}</button><button class="secondary" :disabled="loading || page.current_page >= page.last_page" @click="load(page.current_page + 1)">Berikutnya</button></div></footer>
      </section>
      <div v-if="modal" class="overlay" @click.self="!busy && (modal = false)"><div class="dialog" role="dialog" aria-modal="true"><header><h2>{{ editing ? 'Edit' : 'Tambah' }} Harga Historis</h2><button class="secondary" :disabled="busy" @click="modal = false">×</button></header><form @submit.prevent="save"><div v-if="modalError" class="alert">{{ modalError }}</div><label>Jenis Acara *<input v-model="form.jenis_acara" maxlength="100" required placeholder="Contoh: Workshop" /></label><label>Tanggal Pelaksanaan *<input v-model="form.tanggal_pelaksanaan" type="date" :max="today" required /></label><label>Harga Estimasi (Rp) *<input v-model="form.harga_estimasi" type="number" min="0.01" step="0.01" required /></label><p class="muted">Tahun, bulan, hari, dan kategori hari dihitung otomatis oleh Laravel.</p><footer><button type="button" class="secondary" :disabled="busy" @click="modal = false">Batal</button><button class="primary" :disabled="busy">{{ busy ? 'Menyimpan...' : 'Simpan' }}</button></footer></form></div></div>
      <div v-if="removeTarget" class="overlay" @click.self="!busy && (removeTarget = null)"><div class="dialog" role="alertdialog" aria-modal="true"><header><h2>Hapus Data Manual?</h2></header><p>Data {{ removeTarget.id_pemesanan }} akan dihapus permanen.</p><div v-if="modalError" class="alert">{{ modalError }}</div><footer><button class="secondary" :disabled="busy" @click="removeTarget = null">Batal</button><button class="danger" :disabled="busy" @click="remove">{{ busy ? 'Menghapus...' : 'Ya, Hapus' }}</button></footer></div></div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.historical-page{color:#f4f4f5;min-width:0}.heading{display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap;margin-bottom:25px}.heading h1{font-size:clamp(22px,3vw,30px);margin:7px 0}.heading p,.muted{color:#a1a1aa;font-size:12px}.eyebrow{font-size:10px;letter-spacing:.16em;color:#fb923c;font-weight:700}.primary,.secondary,.danger,.danger-outline{border-radius:8px;padding:10px 14px;font-size:12px;cursor:pointer}.primary{background:#f97316;border:1px solid #f97316;color:white}.secondary{background:transparent;border:1px solid #404047;color:#e4e4e7}.danger{background:#dc2626;border:1px solid #dc2626;color:white}.danger-outline{background:transparent;border:1px solid #dc2626;color:#fca5a5}button:disabled{opacity:.5;cursor:not-allowed}.summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:24px}.tile,.panel{background:#18181b;border:1px solid #303036;border-radius:12px}.tile{padding:23px;display:flex;flex-direction:column;gap:10px}.tile small{color:#a1a1aa;font-size:10px;letter-spacing:.1em}.tile strong{font-size:29px;color:#fb923c}.tile .small-value{font-size:19px}.tile span{color:#a1a1aa;font-size:11px}.panel{margin-bottom:24px;overflow:hidden}.panel>h2,.panel-top{padding:19px 22px;border-bottom:1px solid #303036}.panel h2{margin:0;font-size:15px}.panel-top{display:flex;justify-content:space-between;align-items:center}.filters{padding:22px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.filters label,.dialog label{display:flex;flex-direction:column;gap:8px;font-size:12px;color:#d4d4d8}.filters input,.filters select,.dialog input{width:100%;min-width:0;background:#111113;border:1px solid #404047;border-radius:8px;color:#f4f4f5;padding:11px 12px;color-scheme:dark}.filters input:focus,.filters select:focus,.dialog input:focus{outline:1px solid #f97316}.actions{display:flex;gap:10px;align-items:end}.table-scroll{overflow-x:auto}table{width:100%;min-width:1100px;border-collapse:collapse;text-align:left}th{background:#202024;color:#a1a1aa;font-size:11px;padding:15px}td{border-top:1px solid #303036;font-size:12px;padding:15px;color:#d4d4d8}tbody tr:hover{background:#202024}.price{color:#fb923c;font-weight:700;white-space:nowrap}.pill{background:#164e63;color:#67e8f9;border-radius:6px;padding:6px 9px;font-size:10px}.pill.manual{background:#3f3f46;color:#e4e4e7}.row-actions{display:flex;gap:7px}.empty{text-align:center;padding:45px}.pagination{padding:16px 22px;border-top:1px solid #303036;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;color:#a1a1aa;font-size:11px}.pagination>div{display:flex;flex-wrap:wrap;gap:5px}.selected{border-color:#f97316;background:#f9731622}.alert,.notice{padding:14px;border-radius:8px;margin-bottom:18px;font-size:12px}.alert{background:#7f1d1d40;border:1px solid #991b1b;color:#fca5a5}.notice{background:#14532d40;border:1px solid #166534;color:#86efac;display:flex;justify-content:space-between}.notice button{background:none;border:0;color:inherit;cursor:pointer}.overlay{position:fixed;inset:0;background:#000b;z-index:2000;display:grid;place-items:center;padding:16px}.dialog{background:#18181b;border:1px solid #404047;border-radius:12px;width:100%;max-width:540px;max-height:92vh;overflow:auto;padding:24px;display:grid;gap:16px}.dialog header,.dialog footer{display:flex;align-items:center;justify-content:space-between;gap:12px}.dialog h2{margin:0;font-size:18px}.dialog form{display:grid;gap:17px}.dialog footer{justify-content:flex-end}.dialog p{font-size:12px;line-height:1.7}@media(max-width:1100px){.summary,.filters{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:650px){.summary,.filters{grid-template-columns:1fr}.heading{align-items:flex-start}.pagination{align-items:flex-start;flex-direction:column}.filters{padding:16px}}
</style>
