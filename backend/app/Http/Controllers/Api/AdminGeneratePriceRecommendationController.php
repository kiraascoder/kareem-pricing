<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\KareemPricePredictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminGeneratePriceRecommendationController extends Controller
{
    public function __invoke(Request $request, Booking $booking, KareemPricePredictionService $service): JsonResponse
    {
        abort_unless(in_array($request->user()->role, ['admin', 'super_admin'], true), 403);
        $recommendation = $service->generate($booking);
        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi harga berhasil dibuat.',
            'data' => $recommendation,
        ], 201);
    }
}
