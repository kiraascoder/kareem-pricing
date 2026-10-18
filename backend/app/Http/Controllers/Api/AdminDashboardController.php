<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PriceQuote;
use App\Models\PriceRecommendation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $totalBookings = Booking::count();

        $bookingStatuses = Booking::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $paymentStatuses = Payment::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $quoteStatuses = PriceQuote::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $verifiedRevenue = Payment::query()
            ->where('status', 'verified')
            ->sum('amount');

        $averageRecommendedPrice = PriceRecommendation::query()
            ->avg('recommended_price');

        $averageDemand = PriceRecommendation::query()
            ->avg('predicted_demand');

        $recentBookings = Booking::query()
            ->with([
                'eventPackage.category',
                'user:id,name,email',
                'priceQuote',
            ])
            ->latest()
            ->limit(10)
            ->get();

        $pendingPayments = Payment::query()
            ->with([
                'booking.user:id,name,email',
            ])
            ->where('status', 'pending_verification')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard admin berhasil dimuat.',
            'data' => [
                'summary' => [
                    'total_bookings' => $totalBookings,
                    'pending_bookings' =>
                    (int) ($bookingStatuses['pending'] ?? 0),
                    'reviewed_bookings' =>
                    (int) ($bookingStatuses['reviewed'] ?? 0),
                    'awaiting_payment_bookings' =>
                    (int) ($bookingStatuses['awaiting_payment'] ?? 0),
                    'paid_bookings' =>
                    (int) ($bookingStatuses['paid'] ?? 0),
                    'cancelled_bookings' =>
                    (int) ($bookingStatuses['cancelled'] ?? 0),
                    'pending_payments' =>
                    (int) ($paymentStatuses['pending_verification'] ?? 0),
                    'verified_payments' =>
                    (int) ($paymentStatuses['verified'] ?? 0),
                    'total_verified_payments' =>
                    (float) $verifiedRevenue,
                    'total_price_recommendations' =>
                    PriceRecommendation::count(),
                    'average_recommended_price' =>
                    $averageRecommendedPrice !== null
                        ? round((float) $averageRecommendedPrice, 2)
                        : null,
                    'average_predicted_demand' =>
                    $averageDemand !== null
                        ? round((float) $averageDemand, 4)
                        : null,
                ],
                'booking_statuses' => $bookingStatuses,
                'payment_statuses' => $paymentStatuses,
                'quote_statuses' => $quoteStatuses,
                'recent_bookings' => $recentBookings,
                'pending_payment_reviews' => $pendingPayments,
            ],
        ]);
    }

    public function bookingTrend(Request $request): JsonResponse
    {
        $data = $request->validate([
            'days' => 'sometimes|integer|min:7|max:365',
        ]);

        $days = (int) ($data['days'] ?? 30);

        $startDate = now()
            ->startOfDay()
            ->subDays($days - 1);

        $records = Booking::query()
            ->selectRaw('DATE(created_at) as booking_date')
            ->selectRaw('COUNT(*) as total')
            ->where('created_at', '>=', $startDate)
            ->groupByRaw('DATE(created_at)')
            ->orderBy('booking_date')
            ->get()
            ->keyBy('booking_date');

        $trend = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()
                ->addDays($i)
                ->toDateString();

            $trend[] = [
                'date' => $date,
                'total' => (int) (
                    $records->get($date)?->total ?? 0
                ),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'days' => $days,
                'trend' => $trend,
            ],
        ]);
    }
}
