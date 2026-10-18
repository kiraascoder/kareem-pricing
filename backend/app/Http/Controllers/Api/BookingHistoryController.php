<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingHistoryController extends Controller
{
    public function index(
        Request $request,
        Booking $booking
    ): JsonResponse {
        $user = $request->user();

        $isOwner = (int) $booking->user_id === (int) $user->id;

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

        $histories = $booking->statusHistories()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $histories,
        ]);
    }
}
