<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PriceQuote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\BookingStatusHistory;

class CustomerPriceQuoteController extends Controller
{
    public function show(
        Request $request,
        Booking $booking
    ): JsonResponse {
        if ((int) $booking->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan.',
            ], 404);
        }

        $quote = $booking->priceQuote;

        if (!$quote) {
            return response()->json([
                'success' => false,
                'message' => 'Penawaran belum tersedia.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $quote,
        ]);
    }

    public function respond(
        Request $request,
        Booking $booking
    ): JsonResponse {
        $data = $request->validate([
            'decision' => [
                'required',
                'in:accepted,rejected',
            ],
            'customer_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        if ((int) $booking->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan.',
            ], 404);
        }

        $quote = DB::transaction(function () use ($request, $booking, $data) {
            $lockedBooking = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            $quote = PriceQuote::query()
                ->where('booking_id', $lockedBooking->id)
                ->lockForUpdate()
                ->first();

            if (!$quote) {
                throw ValidationException::withMessages([
                    'booking' => [
                        'Penawaran belum tersedia.',
                    ],
                ]);
            }

            if ($quote->status !== 'sent') {
                throw ValidationException::withMessages([
                    'decision' => [
                        'Penawaran sudah ditanggapi.',
                    ],
                ]);
            }

            if ($lockedBooking->status !== 'reviewed') {
                throw ValidationException::withMessages([
                    'booking' => [
                        'Status booking tidak memungkinkan respons.',
                    ],
                ]);
            }

            $quote->update([
                'status' => $data['decision'],
                'customer_notes' =>
                $data['customer_notes'] ?? null,
                'responded_at' => now(),
            ]);

            $lockedBooking->update([
                'status' => $data['decision'] === 'accepted'
                    ? 'awaiting_payment'
                    : 'cancelled',
            ]);


            BookingStatusHistory::create([
                'booking_id' => $lockedBooking->id,
                'changed_by' => $request->user()->id,
                'old_status' => 'reviewed',
                'new_status' => $lockedBooking->status,
                'action' => 'customer_quote_response',
                'notes' => $data['decision'] === 'accepted'
                    ? 'Customer menerima penawaran harga.'
                    : 'Customer menolak penawaran harga.',
            ]);



            return $quote->refresh();
        });

        return response()->json([
            'success' => true,
            'message' => $quote->status === 'accepted'
                ? 'Penawaran harga berhasil diterima.'
                : 'Penawaran harga berhasil ditolak.',
            'data' => $quote,
        ]);
    }
}
