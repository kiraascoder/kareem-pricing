<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PriceQuote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminPriceQuoteController extends Controller
{
    public function store(
        Request $request,
        Booking $booking
    ): JsonResponse {
        $data = $request->validate([
            'final_price' => [
                'required',
                'numeric',
                'gt:0',
                'max:9999999999999.99',
            ],
            'admin_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $quote = DB::transaction(function () use (
            $request,
            $booking,
            $data
        ) {
            $lockedBooking = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBooking->status !== 'reviewed') {
                throw ValidationException::withMessages([
                    'booking' => [
                        'Booking harus berstatus reviewed.',
                    ],
                ]);
            }

            if (
                PriceQuote::where('booking_id', $lockedBooking->id)
                ->exists()
            ) {
                throw ValidationException::withMessages([
                    'booking' => [
                        'Penawaran untuk booking ini sudah dibuat.',
                    ],
                ]);
            }

            $recommendation = $lockedBooking
                ->priceRecommendations()
                ->latest()
                ->first();

            if (!$recommendation) {
                throw ValidationException::withMessages([
                    'booking' => [
                        'Estimasi harga belum tersedia.',
                    ],
                ]);
            }

            return PriceQuote::create([
                'booking_id' => $lockedBooking->id,
                'admin_id' => $request->user()->id,
                'estimated_price' =>
                $recommendation->recommended_price,
                'final_price' => $data['final_price'],
                'admin_notes' => $data['admin_notes'] ?? null,
                'status' => 'sent',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Penawaran harga berhasil dikirim.',
            'data' => $quote,
        ], 201);
    }
}
