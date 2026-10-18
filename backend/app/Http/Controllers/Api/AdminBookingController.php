<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminBookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => [
                'nullable',
                Rule::in(['pending', 'reviewed', 'cancelled']),
            ],
            'search' => 'nullable|string|max:100',
        ]);

        $bookings = Booking::query()
            ->with([
                'user:id,name,email,phone',
                'eventPackage.category',
            ])
            ->when(
                $filters['status'] ?? null,
                fn($query, $status) =>
                $query->where('status', $status)
            )
            ->when(
                $filters['search'] ?? null,
                function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('booking_code', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($user) use ($search) {
                                $user->where('name', 'like', "%{$search}%");
                            });
                    });
                }
            )
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    public function show(Booking $booking): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $booking->load([
                'user:id,name,email,phone',
                'eventPackage.category',
            ]),
        ]);
    }

    public function review(Request $request, Booking $booking): JsonResponse
    {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::in(['reviewed', 'cancelled']),
            ],
        ]);

        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Booking sudah diproses.',
            ], 409);
        }

        $booking->update([
            'status' => $data['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status booking berhasil diperbarui.',
            'data' => $booking->fresh(),
        ]);
    }
}
