<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Checkoutcontroller;


Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook']);


Route::get('/_log-test-success', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/_log-test-exception', function () {
    throw new \Exception('Test exception');
});

