<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PriceRecommendation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BookingPricingService
{
    public function __construct(
        private MlPricingService $mlPricingService
    ) {}

    public function generate(
        Booking $booking
    ): PriceRecommendation {

        // Periksa apakah rekomendasi sudah tersedia.
        $existing = $booking->priceRecommendations()
            ->where('status', 'generated')
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        // Panggil FastAPI di luar transaksi database.
        $result = $this->mlPricingService->predict($booking);

        $requiredFields = [
            'recommended_price',
            'model_version',
            'calculation_method',
        ];

        foreach ($requiredFields as $field) {
            if (
                !array_key_exists($field, $result) ||
                $result[$field] === null
            ) {
                throw new RuntimeException(
                    "Respons ML tidak memiliki field: {$field}"
                );
            }
        }

        if (
            !is_numeric($result['recommended_price']) ||
            (float) $result['recommended_price'] <= 0
        ) {
            throw new RuntimeException(
                'Harga rekomendasi dari ML tidak valid.'
            );
        }

        return DB::transaction(function () use ($booking, $result) {

            // Kunci booking agar tidak terjadi duplikasi
            // saat dua request diproses bersamaan.
            $lockedBooking = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Periksa kembali setelah mendapatkan lock.
            $existing = $lockedBooking->priceRecommendations()
                ->where('status', 'generated')
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing;
            }

            return PriceRecommendation::create([
                'booking_id' => $lockedBooking->id,

                'base_price' =>
                $lockedBooking->base_price_snapshot,

                'predicted_demand' =>
                $result['predicted_demand'] ?? null,

                'fuzzy_adjustment_percent' =>
                $result['fuzzy_adjustment_percent'] ?? null,

                'recommended_price' =>
                $result['recommended_price'],

                'model_version' =>
                $result['model_version'],

                'calculation_method' =>
                $result['calculation_method'],

                'input_features' =>
                $result['input_features'] ?? null,

                'calculation_details' =>
                $result['calculation_details'] ?? null,

                'status' => 'generated',
            ]);
        });
    }
}
