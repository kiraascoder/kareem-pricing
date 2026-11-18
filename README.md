# Kareem Pricing — integrasi Random Forest + Fuzzy Sugeno

## 1. Persiapan Python (Windows / PowerShell)
```powershell
cd C:\laragon\www\kareem-e\ml-services
py -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
python train.py
$env:ML_API_KEY="ganti-dengan-token-rahasia-panjang"
uvicorn app:app --host 127.0.0.1 --port 8001
```

Periksa `http://127.0.0.1:8001/health`. File `metrics.json` dibuat oleh `train.py` dengan MAE/RMSE/R² dari 12 data uji (80:20 kronologis). Jangan mengklaim metrik sebelum menjalankan training.

## 2. Laravel
Salin isi folder `backend/` paket ke `backend/` proyek. Pastikan model `PriceRecommendation` memiliki fillable untuk semua field migration, casts `input_features` dan `calculation_details` => `array`.

Tambahkan pada `backend/config/services.php` dalam array return:
```php
'kareem_ml' => [
    'url' => env('KAREEM_ML_URL', 'http://127.0.0.1:8001'),
    'api_key' => env('KAREEM_ML_API_KEY'),
],
```

Tambahkan pada `.env`:
```
KAREEM_ML_URL=http://127.0.0.1:8001
KAREEM_ML_API_KEY=ganti-dengan-token-rahasia-panjang
```

Pada `routes/api.php`, **di dalam grup middleware `auth:sanctum` yang sudah ada**, tambahkan:
```php
Route::post('admin/bookings/{booking}/generate-price',
    \App\Http\Controllers\Api\AdminGeneratePriceRecommendationController::class);
```
Jika file routes sudah memakai `prefix('v1')`, jangan tambahkan `/api/v1` lagi.

Lalu jalankan `php artisan config:clear` dan `php artisan route:list --path=admin/bookings`.

## 3. Contoh panggilan
POST `/api/v1/admin/bookings/1/generate-price` dengan Bearer token admin.

Endpoint mengembalikan rekomendasi baru dan menyimpannya ke `price_recommendations`. Untuk versi awal, endpoint dijalankan admin setelah booking ada; ini menghindari kegagalan booking saat layanan AI mati. Setelah diuji, bisa diubah menjadi queued job otomatis.

## 4. Catatan metodologi
- Dataset 60 baris sintetis; evaluasi 12 baris tidak cukup untuk menyimpulkan performa produksi.
- Harga estimasi dataset diperlakukan sebagai target historis sintetis.
- Fuzzy Sugeno memakai aturan bisnis prototipe, bukan parameter yang dipelajari dari dataset.
- Prediksi tidak menggunakan `participants`, `event_package_id`, `event_location`, atau `base_price` sebagai fitur RF karena tidak tersedia di CSV.
- `base_price` hanya dicatat sebagai snapshot. Prediksi RF menggunakan jenis acara dan tanggal. Jangan mengklaim bahwa model menyesuaikan harga berdasarkan paket.
- Tabel `historical_event_prices` dapat digunakan sebagai sumber export untuk retraining selanjutnya; versi ini melatih langsung dari CSV.
