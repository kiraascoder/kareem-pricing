<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PriceRecommendation;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class KareemPricePredictionService
{
    public function generate(Booking $booking): PriceRecommendation
    {
        $booking->loadMissing('eventPackage.category');
        $category = $booking->eventPackage?->category?->name;
        $allowed = ['Workshop', 'Seminar', 'Family Gathering', 'Employee Gathering', 'Event Bisnis'];

        if (!in_array($category, $allowed, true)) {
            throw ValidationException::withMessages([
                'category' => ['Kategori event belum dipetakan ke dataset AI: ' . ($category ?? '(kosong)')],
            ]);
        }

        $basePrice = $booking->base_price_snapshot;
        if (!$basePrice || (float) $basePrice <= 0) {
            throw ValidationException::withMessages(['base_price' => ['Harga dasar booking tidak valid.']]);
        }

        $response = Http::timeout(20)
            ->withHeaders(['X-API-Key' => config('services.kareem_ml.api_key')])
            ->post(rtrim(config('services.kareem_ml.url'), '/') . '/predict', [
                'jenis_acara' => $category,
                'tanggal_pelaksanaan' => $booking->event_date->format('Y-m-d'),
                'base_price' => (float) $basePrice,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Prediksi AI gagal: HTTP ' . $response->status());
        }

        $result = $response->json();
        foreach (['recommended_price', 'fuzzy_adjustment_percent', 'model_version', 'calculation_method'] as $field) {
            if (!array_key_exists($field, $result)) {
                throw new \RuntimeException('Respons AI tidak lengkap: ' . $field);
            }
        }
        if (!is_numeric($result['recommended_price']) || (float) $result['recommended_price'] <= 0) {
            throw new \RuntimeException('Harga prediksi AI tidak valid.');
        }

        return $booking->priceRecommendations()->create([
            'base_price' => $basePrice,
            'predicted_demand' => $result['predicted_demand'] ?? null,
            'fuzzy_adjustment_percent' => $result['fuzzy_adjustment_percent'],
            'recommended_price' => $result['recommended_price'],
            'model_version' => $result['model_version'],
            'calculation_method' => $result['calculation_method'],
            'input_features' => $result['input_features'] ?? null,
            'calculation_details' => $result['calculation_details'] ?? null,
            'status' => 'generated',
        ]);
    }
}
