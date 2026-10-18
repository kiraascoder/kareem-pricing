<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminPaymentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Payment::query()
                ->with('booking')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function proof(Payment $payment)
    {
        if (!Storage::disk('local')->exists($payment->proof_path)) {
            abort(404, 'Bukti pembayaran tidak ditemukan.');
        }

        return Storage::disk('local')->response(
            $payment->proof_path
        );
    }

    public function verify(
        Request $request,
        Payment $payment
    ): JsonResponse {
        $data = $request->validate([
            'decision' => 'required|in:verified,rejected',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $result = DB::transaction(function () use (
            $request,
            $payment,
            $data
        ) {
            $booking = Booking::query()
                ->whereKey($payment->booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPayment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status !== 'pending_verification') {
                throw ValidationException::withMessages([
                    'payment' => [
                        'Pembayaran sudah diproses.',
                    ],
                ]);
            }

            if ($booking->status !== 'awaiting_payment') {
                throw ValidationException::withMessages([
                    'booking' => [
                        'Status booking tidak valid untuk verifikasi.',
                    ],
                ]);
            }

            $lockedPayment->update([
                'status' => $data['decision'],
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'admin_notes' => $data['admin_notes'] ?? null,
            ]);

            if ($data['decision'] === 'verified') {
                $booking->update([
                    'status' => 'paid',
                ]);

                BookingStatusHistory::create([
                    'booking_id' => $booking->id,
                    'changed_by' => $request->user()->id,
                    'old_status' => 'awaiting_payment',
                    'new_status' => 'paid',
                    'action' => 'payment_verified',
                    'notes' => 'Pembayaran diverifikasi oleh admin.',
                ]);
            }

            return $lockedPayment->refresh();
        });

        return response()->json([
            'success' => true,
            'message' => $result->status === 'verified'
                ? 'Pembayaran berhasil diverifikasi.'
                : 'Pembayaran berhasil ditolak.',
            'data' => $result,
        ]);
    }
}
