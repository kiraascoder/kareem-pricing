<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\EventPackage;
use App\Services\BookingPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::query()
            ->with('eventPackage.category')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Daftar booking berhasil diambil.',
            'data' => $bookings,
        ]);
    }

    public function store(
        Request $request,
        BookingPricingService $pricingService
    ): JsonResponse {
        $data = $request->validate([
            'event_package_id' => [
                'required',
                'integer',
                'exists:event_packages,id',
            ],
            'event_date' => [
                'required',
                'date_format:Y-m-d',
                'after:today',
            ],
            'event_location' => [
                'required',
                'string',
                'max:255',
            ],
            'participants' => [
                'required',
                'integer',
                'min:1',
            ],
            'customer_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $package = EventPackage::query()
            ->whereKey($data['event_package_id'])
            ->where('is_active', true)
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->first();

        if (!$package) {
            throw ValidationException::withMessages([
                'event_package_id' => [
                    'Paket event tidak tersedia atau tidak aktif.',
                ],
            ]);
        }

        if (
            $package->min_participants !== null &&
            $data['participants'] < $package->min_participants
        ) {
            throw ValidationException::withMessages([
                'participants' => [
                    'Jumlah peserta minimal ' .
                        $package->min_participants . ' orang.',
                ],
            ]);
        }

        if (
            $package->max_participants !== null &&
            $data['participants'] > $package->max_participants
        ) {
            throw ValidationException::withMessages([
                'participants' => [
                    'Jumlah peserta maksimal ' .
                        $package->max_participants . ' orang.',
                ],
            ]);
        }

        $booking = Booking::create([
            'booking_code' => 'KR-' . strtoupper((string) Str::ulid()),
            'user_id' => $request->user()->id,
            'event_package_id' => $package->id,
            'event_date' => $data['event_date'],
            'event_location' => $data['event_location'],
            'participants' => $data['participants'],
            'customer_notes' => $data['customer_notes'] ?? null,
            'base_price_snapshot' => $package->base_price,
            'status' => 'pending',
        ]);

        $recommendation = null;

        try {
            $recommendation = $pricingService->generate($booking);
        } catch (Throwable $exception) {
            Log::error('Gagal menghasilkan estimasi harga booking.', [
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'exception' => $exception->getMessage(),
            ]);
        }

        $booking->load('eventPackage.category');

        return response()->json([
            'success' => true,
            'message' => $recommendation
                ? 'Booking berhasil dibuat dan estimasi harga tersedia.'
                : 'Booking berhasil dibuat. Estimasi harga belum tersedia.',
            'data' => [
                'booking' => $booking,
                'price_estimate' => $recommendation ? [
                    'base_price' => $recommendation->base_price,
                    'estimated_price' => $recommendation->recommended_price,
                    'calculated_at' => $recommendation->created_at,
                ] : null,
            ],
        ], 201);
    }

    public function show(
        Request $request,
        Booking $booking
    ): JsonResponse {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan.',
            ], 404);
        }

        $booking->load('eventPackage.category');

        return response()->json([
            'success' => true,
            'message' => 'Detail booking berhasil diambil.',
            'data' => $booking,
        ]);
    }
}
