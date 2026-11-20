# Revisi Kareem Pricing — integrasi sesuai ZIP asli

## Perubahan
- CRUD harga historis dibuat berdasarkan skema **asli** (`id_pemesanan`, `jenis_acara`, `tanggal_pelaksanaan`, `tahun`, `bulan`, `hari_dalam_minggu`, `kategori_hari`, `harga_estimasi`). **Tidak** menggunakan skema `event_category_id`/`final_price` dari rancangan lain.
- Route API admin, controller, service Vue, halaman dengan AdminLayout, router, dan sidebar ditambahkan.
- Baris dataset asli dilindungi dari edit/hapus. Hanya data baru dengan kode `MAN-...` yang dapat diubah atau dihapus.
- Atribut tahun/bulan/hari dihitung otomatis saat input atau edit.

## Alur aplikasi
1. Pengguna login, memilih paket event, membuat booking.
2. Admin memeriksa booking, harga penawaran, dan pembayaran.
3. Modul ML FastAPI mendukung rekomendasi harga pada alur yang sudah ada.
4. Historical Event Price adalah **data penelitian**, bukan daftar booking atau transaksi aktual; menambah data tidak otomatis melatih ulang model.

## Instalasi
1. Backup proyek dan database lokal sebelum menimpa file.
2. Salin isi ZIP revisi ke proyek, tanpa menghapus `.env` lokal.
3. Backend: `cd backend && composer install && php artisan migrate && php artisan route:clear`.
4. Frontend: `cd frontend && npm install && npm run dev`.
5. Pastikan backend Laravel dan layanan ML berjalan sesuai konfigurasi `.env` lokal.
6. Login sebagai user yang pada database memiliki `role=admin` atau `super_admin` **dan** `status=active`.
7. Buka `/admin/historical-event-prices`.

## Catatan
- ZIP asli tidak berisi CRUD Historical Event Price yang dibahas di percakapan sebelumnya. Revisi ini disesuaikan dengan ZIP, bukan menggabungkan skema database berbeda.
- Error 403 dapat muncul jika role atau status user tidak sesuai. Jangan menghapus middleware role.
- Data sintetis tetap data penelitian, bukan harga transaksi aktual.
- Perubahan belum diuji dengan database/layanan Laravel berjalan; lakukan pengujian lokal sebelum produksi.
