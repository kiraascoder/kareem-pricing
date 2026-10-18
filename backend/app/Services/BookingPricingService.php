<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PriceRecommendation;

class BookingPricingService
{
    public function __construct(
        private MlPricingService $mlPricingService
    ) {}

    public function generate(
        Booking $booking
    ): PriceRecommendation {
        $result = $this->mlPricingService->predict($booking);

        return PriceRecommendation::create([
            'booking_id' => $booking->id,
            'base_price' => $booking->base_price_snapshot,
            'predicted_demand' => $result['predicted_demand'],
            'fuzzy_adjustment_percent' =>
            $result['fuzzy_adjustment_percent'],
            'recommended_price' => $result['recommended_price'],
            'model_version' => $result['model_version'],
            'calculation_method' => $result['calculation_method'],
            'input_features' => $result['input_features'] ?? null,
            'calculation_details' =>
            $result['calculation_details'] ?? null,
            'status' => 'generated',
        ]);
    }
}
