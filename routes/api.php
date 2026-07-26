<?php

use App\Http\Controllers\Api\GuestOrderController;
use App\Http\Controllers\Api\HostGuestController;
use App\Http\Controllers\Api\HostOrderController;
use App\Http\Controllers\Api\HostPropertyController;
use App\Http\Controllers\Api\PmsWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are stateless and use Sanctum token authentication.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Host API
    Route::middleware('host')->prefix('host')->name('api.host.')->group(function () {
        Route::apiResource('properties', HostPropertyController::class);
        Route::apiResource('guests', HostGuestController::class)->only(['index', 'show']);
        Route::apiResource('orders', HostOrderController::class)->only(['index', 'show', 'update']);
    });

    // Guest API
    Route::prefix('guest')->name('api.guest.')->group(function () {
        Route::get('/portal', [GuestOrderController::class, 'portal'])->name('portal');
        Route::post('/orders', [GuestOrderController::class, 'store'])->name('orders.store');
    });
});

// PMS webhooks are signed by shared secret
Route::post('/pms/{provider}/webhook', [PmsWebhookController::class, 'handle'])
    ->name('api.pms.webhook')
    ->whereIn('provider', ['beds24', 'cloudbeds', 'hostaway']);
