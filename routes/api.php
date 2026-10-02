<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\LiveController;
use App\Http\Controllers\Api\LiveRequestController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Middleware\SetApiLocale;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verify']);

Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);

// Public legal documents
Route::get('/privacy-policy', [SettingsController::class, 'privacyPolicy']);
Route::get('/terms-and-conditions', [SettingsController::class, 'termsAndConditions']);



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



Route::middleware(['auth:sanctum', SetApiLocale::class])->group(function () {
    Route::put('/settings', [SettingsController::class, 'update']);

    //change-password
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    //Admin orders
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);

    Route::get('/orders/supervisor', [OrderController::class, 'supervisorOrders']);
    Route::get('/orders/packing', [OrderController::class, 'packingOrders']);
    Route::get('/orders/{order}', [OrderController::class, 'show'])->whereNumber('order');
    Route::post('/orders/{order}/delivery-location', [OrderController::class, 'updateDeliveryLocation'])->whereNumber('order');
    Route::get('/orders/{order}/delivery-location', [OrderController::class, 'deliveryLocation'])->whereNumber('order');
    Route::post('/orders/{order}/send-to-customer', [OrderController::class, 'sendToCustomer'])->whereNumber('order');
    Route::post('/orders/{order}/send-to-aliya', [OrderController::class, 'sendToAliya'])->whereNumber('order');
    Route::post('/sales/orders', [OrderController::class, 'salesAddOrder']);
    Route::get('/sales/orders', [OrderController::class, 'salesOrders']);
    Route::get('/sales', [OrderController::class, 'sales']);
    Route::get('/deliveries/sales', [OrderController::class, 'sales']);


    Route::get('/deliveries/orders', [OrderController::class, 'deliveryOrders']);
    Route::get('/deliveries/orders/{order}', [OrderController::class, 'deliveryOrder']);
    Route::patch('/deliveries/orders/{order}/status', [OrderController::class, 'changeDeliveryOrderStatus']);


    //customers
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::get('/customers', [CustomerController::class, 'customer']);
    Route::get('/clients', [CustomerController::class, 'clients']);

    Route::get('/customers/{user}', [CustomerController::class, 'show']);
    Route::match(['post', 'patch'], '/customers/{user}', [CustomerController::class, 'update']);
    Route::patch('/customers/{user}/status', [CustomerController::class, 'updateStatus']);
    Route::delete('/customers/{user}', [CustomerController::class, 'destroy']);

    //deliveries
    Route::get('/deliveries', [DeliveryController::class, 'deliveries']);

    // Start Facebook Live
    Route::post('/lives/start', [LiveController::class, 'start']);

    // End Facebook Live
    Route::post('/lives/{live}/end', [LiveController::class, 'end']);

    // Get Live details
    Route::get('/lives/{live}', [LiveController::class, 'show']);

    // Live requests
    Route::get('/live-requests', [LiveRequestController::class, 'index']);
    Route::post('/live-requests', [LiveRequestController::class, 'store']);
    Route::get('/live-requests/{liveRequest}', [LiveRequestController::class, 'show']);
    Route::post('/live-requests/{liveRequest}/accept', [LiveRequestController::class, 'accept']);
    Route::post('/live-requests/{liveRequest}/reject', [LiveRequestController::class, 'reject']);

    //Home
    Route::get('/home', [HomeController::class, 'index']);
});
