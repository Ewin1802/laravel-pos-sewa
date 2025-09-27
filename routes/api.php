<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\LicenseController;
use App\Http\Controllers\Api\V1\PaymentConfirmationController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\SubscriptionRenewalController;
use App\Http\Controllers\Api\V1\TrialController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 🔓 Public routes
    Route::post('/auth/register-merchant', [AuthController::class, 'registerMerchant']); // register merchant
    Route::post('/auth/login', [AuthController::class, 'login']); // login user
    Route::get('/plans', [PlanController::class, 'index']); // list subscription plans

    // 🔐 Protected routes (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']); // logout

        // 📱 Device management
        Route::post('/devices/register', [DeviceController::class, 'register']);

        // 📦 Subscription
        Route::get('/subscription/status', [SubscriptionController::class, 'status']);

        // 🔄 Subscription Renewal
        Route::prefix('subscription/renewal')->group(function () {
            Route::get('/status', [SubscriptionRenewalController::class, 'status']);
            Route::post('/renew', [SubscriptionRenewalController::class, 'renew']);
            Route::post('/renew-with-plan', [SubscriptionRenewalController::class, 'renewWithPlan']);
            Route::get('/available-plans', [SubscriptionRenewalController::class, 'availablePlans']);
            Route::get('/history', [SubscriptionRenewalController::class, 'history']);
        });

        // 🛒 Checkout & Payments
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('/checkout', [CheckoutController::class, 'checkout']);
            Route::get('/checkout/status', [CheckoutController::class, 'status']);
            Route::post('/checkout/cancel', [CheckoutController::class, 'cancel']);
        });

        // 💳 Payment confirmations
        Route::post('/payment-confirmations', [PaymentConfirmationController::class, 'store']);
        Route::get('/payment-confirmations', [PaymentConfirmationController::class, 'index']);

        // 🔑 License
        Route::post('/license/issue', [LicenseController::class, 'issue']);
        Route::post('/license/refresh', [LicenseController::class, 'refresh']);
        Route::post('/license/validate', [LicenseController::class, 'validate']);
        Route::get('/license/get-info', [LicenseController::class, 'getLicense']);

        // 🎁 Trial
        // Route::middleware('throttle:2,60')->group(function () {
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('/trial/start', [TrialController::class, 'start']);
            Route::get('/trial/status', [TrialController::class, 'status']);
            Route::post('/trial/convert', [TrialController::class, 'convert']);
        });

        Route::get('/time', function (Request $request) {
            return response()->json([
                // Kirim waktu UTC dalam format ISO8601 agar mudah diparse di Flutter
                'utc_time' => Carbon::now('UTC')->toIso8601String(),
            ]);
        });

    });
});

