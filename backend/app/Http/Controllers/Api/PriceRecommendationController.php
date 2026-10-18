<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceRecommendationController extends Controller
{
    public function show(
        Request $request,
        Booking $booking
    ): JsonResponse {
        $user = $request->user();

        $isOwner = $booking->user_id === $user->id;

        $isAdmin = in_array(
            $user->role,
            ['admin', 'super_admin'],
            true
        );

        if (!$isOwner && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan.',
            ], 404);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak aktif.',
            ], 403);
        }

        $recommendation = $booking
            ->priceRecommendations()
            ->where('status', 'generated')
            ->latest('id')
            ->first();

        if (!$recommendation) {
            return response()->json([
                'success' => false,
                'message' => 'Estimasi harga belum tersedia.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Estimasi harga booking.',
            'data' => [
                'booking_code' => $booking->booking_code,
                'base_price' => $recommendation->base_price,
                'estimated_price' => $recommendation->recommended_price,
                'calculated_at' => $recommendation->created_at,
            ],
        ]);
    }
}