<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MlPricingService
{
    private const CATEGORY_MAP = [
        'workshop' => 'Workshop',
        'seminar' => 'Seminar',
        'family-gathering' => 'Family Gathering',
        'employee-gathering' => 'Employee Gathering',
        'event-bisnis' => 'Event Bisnis',
    ];

    public function predict(Booking $booking): array
    {
        $booking->loadMissing('eventPackage.category');

        $package = $booking->eventPackage;

        if (!$package || !$package->category) {
            throw new RuntimeException(
                'Kategori paket booking tidak ditemukan.'
            );
        }

        $category = Str::slug(
            $package->category->slug
                ?: $package->category->name
        );

        if (!array_key_exists($category, self::CATEGORY_MAP)) {
            throw new RuntimeException(
                'Kategori event "' .
                    $package->category->name .
                    '" belum didukung oleh model Random Forest.'
            );
        }

        $basePrice = (float) $booking->base_price_snapshot;

        if ($basePrice <= 0) {
            throw new RuntimeException(
                'Harga dasar booking tidak valid.'
            );
        }

        $payload = [
            'jenis_acara' => self::CATEGORY_MAP[$category],
            'tanggal_pelaksanaan' =>
            $booking->event_date->format('Y-m-d'),
            'base_price' => $basePrice,
        ];

        $url = rtrim(
            (string) config('services.kareem_ml.url'),
            '/'
        );

        $apiKey = (string) config(
            'services.kareem_ml.api_key'
        );

        if ($url === '' || $apiKey === '') {
            throw new RuntimeException(
                'Konfigurasi Kareem ML belum lengkap.'
            );
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders([
                    'x-api-key' => $apiKey,
                ])
                ->connectTimeout(5)
                ->timeout(15)
                ->post($url . '/predict', $payload);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Layanan FastAPI tidak dapat dihubungi: ' .
                    $exception->getMessage(),
                0,
                $exception
            );
        }

        if (!$response->successful()) {
            throw new RuntimeException(
                'FastAPI mengembalikan HTTP ' .
                    $response->status() .
                    ': ' .
                    $response->body()
            );
        }

        $result = $response->json();

        if (!is_array($result)) {
            throw new RuntimeException(
                'Respons FastAPI bukan JSON yang valid.'
            );
        }

        $requiredFields = [
            'recommended_price',
            'rf_predicted_price',
            'fuzzy_adjustment_percent',
            'model_version',
            'calculation_method',
        ];

        foreach ($requiredFields as $field) {
            if (
                !array_key_exists($field, $result) ||
                $result[$field] === null
            ) {
                throw new RuntimeException(
                    "Field {$field} tidak ditemukan dalam respons ML."
                );
            }
        }

        foreach (
            [
                'recommended_price',
                'rf_predicted_price',
                'fuzzy_adjustment_percent',
            ] as $field
        ) {
            if (
                !is_numeric($result[$field]) ||
                !is_finite((float) $result[$field])
            ) {
                throw new RuntimeException(
                    "Nilai {$field} tidak valid."
                );
            }
        }

        if (
            (float) $result['recommended_price'] <= 0 ||
            (float) $result['rf_predicted_price'] <= 0
        ) {
            throw new RuntimeException(
                'Harga hasil prediksi harus lebih besar dari nol.'
            );
        }

        if (
            !is_string($result['model_version']) ||
            trim($result['model_version']) === '' ||
            !is_string($result['calculation_method']) ||
            trim($result['calculation_method']) === ''
        ) {
            throw new RuntimeException(
                'Metadata model ML tidak valid.'
            );
        }

        if (
            isset($result['input_features']) &&
            !is_array($result['input_features'])
        ) {
            throw new RuntimeException(
                'Input features ML tidak valid.'
            );
        }

        if (
            isset($result['calculation_details']) &&
            !is_array($result['calculation_details'])
        ) {
            throw new RuntimeException(
                'Calculation details ML tidak valid.'
            );
        }

        if (
            isset($result['predicted_demand']) &&
            (
                !is_numeric($result['predicted_demand']) ||
                !is_finite((float) $result['predicted_demand']) ||
                (float) $result['predicted_demand'] < 0 ||
                (float) $result['predicted_demand'] > 1
            )
        ) {
            throw new RuntimeException(
                'Predicted demand ML tidak valid.'
            );
        }

        return [
            'base_price' => $basePrice,
            'predicted_demand' =>
            $result['predicted_demand'] ?? null,
            'fuzzy_adjustment_percent' =>
            (float) $result['fuzzy_adjustment_percent'],
            'recommended_price' =>
            (float) $result['recommended_price'],
            'model_version' => $result['model_version'],
            'calculation_method' =>
            $result['calculation_method'],
            'input_features' =>
            $result['input_features'] ?? $payload,
            'calculation_details' => array_merge(
                $result['calculation_details'] ?? [],
                [
                    'rf_predicted_price' =>
                    (float) $result['rf_predicted_price'],
                ]
            ),
        ];
    }
}
