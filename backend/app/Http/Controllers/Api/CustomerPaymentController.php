<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerPaymentController extends Controller
{
    public function index(
        Request $request,
        Booking $booking
    ): JsonResponse {
        abort_unless(
            (int) $booking->user_id === (int) $request->user()->id,
            404
        );

        return response()->json([
            'success' => true,
            'data' => $booking->payments()
                ->latest()
                ->get(),
        ]);
    }

    public function store(
        Request $request,
        Booking $booking
    ): JsonResponse {
        abort_unless(
            (int) $booking->user_id === (int) $request->user()->id,
            404
        );

        $data = $request->validate([
            'sender_name' => 'required|string|max:150',
            'bank_name' => 'required|string|max:100',
            'transferred_at' => 'required|date|before_or_equal:now',
            'proof' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
            'customer_notes' => 'nullable|string|max:2000',
        ]);

        $path = null;

        try {
            $payment = DB::transaction(function () use (
                $request,
                $booking,
                $data,
                &$path
            ) {
                $lockedBooking = Booking::query()
                    ->whereKey($booking->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedBooking->status !== 'awaiting_payment') {
                    throw ValidationException::withMessages([
                        'booking' => [
                            'Booking tidak sedang menunggu pembayaran.',
                        ],
                    ]);
                }

                $quote = $lockedBooking->priceQuote;

                if (!$quote || $quote->status !== 'accepted') {
                    throw ValidationException::withMessages([
                        'booking' => [
                            'Penawaran harga belum diterima.',
                        ],
                    ]);
                }

                $hasActivePayment = Payment::query()
                    ->where('booking_id', $lockedBooking->id)
                    ->whereIn('status', [
                        'pending_verification',
                        'verified',
                    ])
                    ->exists();

                if ($hasActivePayment) {
                    throw ValidationException::withMessages([
                        'booking' => [
                            'Pembayaran aktif sudah tersedia.',
                        ],
                    ]);
                }

                $path = $request->file('proof')->store(
                    'payment-proofs',
                    'local'
                );

                if (!$path) {
                    throw new \RuntimeException(
                        'Gagal menyimpan bukti pembayaran.'
                    );
                }

                return Payment::create([
                    'booking_id' => $lockedBooking->id,
                    'price_quote_id' => $quote->id,
                    'amount' => $quote->final_price,
                    'payment_method' => 'bank_transfer',
                    'sender_name' => $data['sender_name'],
                    'bank_name' => $data['bank_name'],
                    'transferred_at' => $data['transferred_at'],
                    'proof_path' => $path,
                    'status' => 'pending_verification',
                    'customer_notes' =>
                    $data['customer_notes'] ?? null,
                ]);
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'Bukti pembayaran berhasil dikirim.',
            'data' => $payment,
        ], 201);
    }
}
