<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook']);
