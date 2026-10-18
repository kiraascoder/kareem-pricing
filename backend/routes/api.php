<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EventCatalogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\AdminBookingController;
use App\Http\Controllers\Api\PriceRecommendationController;

use App\Http\Controllers\Api\AdminPriceQuoteController;
use App\Http\Controllers\Api\CustomerPriceQuoteController;
use App\Http\Controllers\Api\BookingHistoryController;
use App\Http\Controllers\Api\CustomerPaymentController;
use App\Http\Controllers\Api\AdminPaymentController;
use App\Http\Controllers\Api\AdminDashboardController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {

    // Public: Event Catalog
    Route::get('/categories', [
        EventCatalogController::class,
        'categories',
    ]);

    Route::get('/packages', [
        EventCatalogController::class,
        'packages',
    ]);

    Route::get('/packages/{id}', [
        EventCatalogController::class,
        'showPackage',
    ])->whereNumber('id');

    // Public: Authentication
    Route::post('/auth/register', [
        AuthController::class,
        'register',
    ]);

    Route::post('/auth/login', [
        AuthController::class,
        'login',
    ]);

    // Protected: Authenticated Users
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/auth/me', [
            AuthController::class,
            'me',
        ]);


        Route::get(
            '/bookings/{booking}/history',
            [BookingHistoryController::class, 'index']
        )->whereNumber('booking');


        Route::post('/auth/logout', [
            AuthController::class,
            'logout',
        ]);

        // Customer dan admin yang berwenang
        Route::get('/bookings/{booking}/price-estimate', [
            PriceRecommendationController::class,
            'show',
        ])->whereNumber('booking');

        // Customer Routes
        Route::middleware('role:customer')->group(function () {

            Route::get(
                '/bookings/{booking}/price-quote',
                [CustomerPriceQuoteController::class, 'show']
            )->whereNumber('booking');


            Route::get(
                '/bookings/{booking}/payments',
                [CustomerPaymentController::class, 'index']
            )->whereNumber('booking');

            Route::post(
                '/bookings/{booking}/payments',
                [CustomerPaymentController::class, 'store']
            )->whereNumber('booking');


            Route::patch(
                '/bookings/{booking}/price-quote/respond',
                [CustomerPriceQuoteController::class, 'respond']
            )->whereNumber('booking');


            Route::get('/bookings', [
                BookingController::class,
                'index',
            ]);

            Route::post('/bookings', [
                BookingController::class,
                'store',
            ]);

            Route::get('/bookings/{booking}', [
                BookingController::class,
                'show',
            ])->whereNumber('booking');
        });

        // Admin Routes
        Route::middleware('role:admin,super_admin')->group(function () {


            Route::post(
                '/admin/bookings/{booking}/price-quote',
                [AdminPriceQuoteController::class, 'store']
            )->whereNumber('booking');

            Route::get('/admin/check', function () {
                return response()->json([
                    'success' => true,
                    'message' => 'Akses admin berhasil.',
                ]);
            });


            Route::get(
                '/admin/dashboard',
                [AdminDashboardController::class, 'index']
            );

            Route::get(
                '/admin/dashboard/booking-trend',
                [AdminDashboardController::class, 'bookingTrend']
            );


            Route::get(
                '/admin/payments',
                [AdminPaymentController::class, 'index']
            );

            Route::get(
                '/admin/payments/{payment}/proof',
                [AdminPaymentController::class, 'proof']
            )->whereNumber('payment');

            Route::patch(
                '/admin/payments/{payment}/verify',
                [AdminPaymentController::class, 'verify']
            )->whereNumber('payment');

            Route::get('/admin/bookings', [
                AdminBookingController::class,
                'index',
            ]);

            Route::get('/admin/bookings/{booking}', [
                AdminBookingController::class,
                'show',
            ])->whereNumber('booking');

            Route::patch('/admin/bookings/{booking}/review', [
                AdminBookingController::class,
                'review',
            ])->whereNumber('booking');
        });
    });
});
