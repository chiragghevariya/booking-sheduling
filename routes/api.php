<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\Provider\AvailabilityController;
use App\Http\Controllers\Api\Provider\AvailabilityExceptionController;
use App\Http\Controllers\Api\Provider\BookingController as ProviderBookingController;
use App\Http\Controllers\Api\Provider\ServiceController as ProviderServiceController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SlotsController;
use Illuminate\Support\Facades\Route;

// SPA (cookie) auth — used by the Vue web app.
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
    });
});

// Token (Bearer) auth — used by the React Native (Expo) mobile app.
// Public; success returns { data: user, token: "..." }.
Route::post('register', [AuthController::class, 'tokenRegister'])->name('token.register');
Route::post('login', [AuthController::class, 'tokenLogin'])->name('token.login');

// Token-protected: requires Authorization: Bearer <token>.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [AuthController::class, 'tokenUser'])->name('token.user');
    Route::post('logout', [AuthController::class, 'tokenLogout'])->name('token.logout');

    // Mobile push: customer app sends its Expo push token here after every login.
    Route::post('device-token', [DeviceTokenController::class, 'store'])->name('device-token.store');
});

Route::middleware('auth:sanctum')->group(function () {
    // Provider availability management (policy enforces role).
    Route::apiResource('availability', AvailabilityController::class);
    Route::apiResource('availability-exceptions', AvailabilityExceptionController::class)
        ->parameters(['availability-exceptions' => 'exception']);

    // Service catalog (read-only here — provider CRUD comes later).
    Route::get('services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('services/{service}', [ServiceController::class, 'show'])->name('services.show');

    // Slot lookup for the booking calendar.
    Route::get('slots', [SlotsController::class, 'index'])->name('slots.index');

    // Customer-facing booking flow.
    Route::apiResource('bookings', CustomerBookingController::class);

    // Provider/admin management.
    Route::prefix('provider')->name('provider.')->group(function () {
        Route::get('bookings', [ProviderBookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/{booking}', [ProviderBookingController::class, 'show'])->name('bookings.show');
        Route::post('bookings/{booking}/approve', [ProviderBookingController::class, 'approve'])->name('bookings.approve');
        Route::post('bookings/{booking}/decline', [ProviderBookingController::class, 'decline'])->name('bookings.decline');
        Route::delete('bookings/{booking}', [ProviderBookingController::class, 'destroy'])->name('bookings.destroy');

        Route::apiResource('services', ProviderServiceController::class);
    });
});
