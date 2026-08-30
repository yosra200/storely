<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\LiveController;
use App\Http\Controllers\Api\HomeController;

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



Route::middleware('auth:sanctum')->group(function () {
//change-password
    Route::post('/change-password', [
        AuthController::class,
        'changePassword',
    ]);
    //Admin orders
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/addordersupervisor', [OrderController::class, 'addOrderSupervisor']);
    Route::post('/orders/addordersales', [OrderController::class, 'addOrderSales']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/sales', [OrderController::class, 'sales']);

    Route::get('/deliveries/orders', [OrderController::class, 'deliveryOrders']);


    //customers
    Route::post('/customers', [CustomerController::class, 'store']);
Route::get('/customers', [CustomerController::class, 'customer']);

//deliveries
Route::get('/deliveries', [DeliveryController::class, 'deliveries']);

    // Start Facebook Live
    Route::post('/lives/start', [LiveController::class, 'start']);

    // End Facebook Live
    Route::post('/lives/{live}/end', [LiveController::class, 'end']);

    // Get Live details
    Route::get('/lives/{live}', [LiveController::class, 'show']);


    //Home 
    Route::get('/home', [HomeController::class, 'index']);
});
