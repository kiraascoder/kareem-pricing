<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MlPricingService
{
    public function predict(Booking $booking): array
    {
        $booking->loadMissing('eventPackage.category');

        $category = Str::slug(
            $booking->eventPackage->category->slug
        );

        $supportedCategories = [
            'wedding',
            'corporate',
            'concert',
            'gathering',
        ];

        if (!in_array($category, $supportedCategories, true)) {
            throw new RuntimeException(
                'Kategori event belum didukung model ML.'
            );
        }

        $payload = [
            'participants' => $booking->participants,
            'booking_date' => $booking->created_at
                ->toDateString(),
            'event_date' => $booking->event_date
                ->toDateString(),
            'event_category' => $category,
            'base_price' => (float) $booking->base_price_snapshot,
        ];

        try {
            $response = Http::acceptJson()
                ->timeout(config('services.ml_service.timeout', 10))
                ->post(
                    rtrim(config('services.ml_service.url'), '/')
                        . '/predict-price',
                    $payload
                );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Layanan prediksi harga tidak dapat dihubungi.',
                0,
                $exception
            );
        }

        if (!$response->successful()) {
            throw new RuntimeException(
                'Layanan prediksi harga mengembalikan kesalahan.'
            );
        }

        $result = $response->json();

        if (
            !is_array($result) ||
            ($result['success'] ?? false) !== true ||
            !is_numeric($result['recommended_price'] ?? null) ||
            !is_numeric($result['predicted_demand'] ?? null) ||
            !is_numeric($result['fuzzy_adjustment_percent'] ?? null) ||
            !is_string($result['model_version'] ?? null) ||
            !is_string($result['calculation_method'] ?? null) ||
            (float) $result['recommended_price'] <= 0 ||
            (float) $result['predicted_demand'] < 0 ||
            (float) $result['predicted_demand'] > 1
        ) {
            throw new RuntimeException(
                'Respons prediksi harga tidak valid.'
            );
        }

        return $result;
    }
}
