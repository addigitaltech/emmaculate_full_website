<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhooks/payments/{gateway}', [PaymentWebhookController::class, 'handle'])
    ->where('gateway', 'paystack|flutterwave|moniepoint')
    ->middleware('throttle:payments-webhooks')
    ->name('payments.webhook');
