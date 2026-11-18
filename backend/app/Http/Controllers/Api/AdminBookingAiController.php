<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;

class AdminBookingAiController extends Controller
{
    public function show(Booking $booking): JsonResponse
    {
        $booking->load([
            'eventPackage.category',
        ]);

        $recommendation = $booking
            ->priceRecommendations()
            ->where('status', 'generated')
            ->latest('id')
            ->first();

        if (!$recommendation) {
            return response()->json([
                'success' => false,
                'message' => 'Rekomendasi AI belum tersedia.',
                'data' => null,
            ], 404);
        }

        $details = $recommendation->calculation_details ?? [];

        return response()->json([
            'success' => true,
            'message' => 'Detail rekomendasi AI berhasil diambil.',
            'data' => [
                'booking' => [
                    'id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'event_date' => $booking->event_date
                        ->format('Y-m-d'),
                    'event_category' =>
                    $booking->eventPackage?->category?->name,
                    'event_package' =>
                    $booking->eventPackage?->name,
                    'participants' => $booking->participants,
                    'status' => $booking->status,
                ],
                'prediction' => [
                    'base_price' =>
                    (float) $recommendation->base_price,

                    'rf_predicted_price' =>
                    isset($details['rf_predicted_price'])
                        ? (float) $details['rf_predicted_price']
                        : null,

                    'fuzzy_adjustment_percent' =>
                    $recommendation->fuzzy_adjustment_percent !== null
                        ? (float) $recommendation
                            ->fuzzy_adjustment_percent
                        : null,

                    'recommended_price' =>
                    (float) $recommendation->recommended_price,

                    'predicted_demand' =>
                    $recommendation->predicted_demand !== null
                        ? (float) $recommendation->predicted_demand
                        : null,

                    'model_version' =>
                    $recommendation->model_version,

                    'calculation_method' =>
                    $recommendation->calculation_method,

                    'input_features' =>
                    $recommendation->input_features,

                    'calculation_details' => $details,

                    'calculated_at' =>
                    $recommendation->created_at,
                ],
            ],
        ]);
    }
}
