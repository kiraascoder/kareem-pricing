<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EventCatalogController;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::prefix('v1')->group(function () {
    Route::get('/categories', [EventCatalogController::class, 'categories']);
    Route::get('/packages', [EventCatalogController::class, 'packages']);
    Route::get('/packages/{id}', [EventCatalogController::class, 'showPackage'])
        ->whereNumber('id');
});