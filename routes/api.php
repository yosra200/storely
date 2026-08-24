<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\Api\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verify']);

Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);



Route::prefix('auth')->group(function () {

    // Register
    Route::post('/register', [AuthController::class, 'register']);

    // Login
    Route::post('/login', [AuthController::class, 'login']);

    // Forgot Password - Send OTP
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

    // Verify OTP
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

    // Reset Password
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');
});
