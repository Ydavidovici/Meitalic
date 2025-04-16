<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\FAQController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public Pages
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/faq', [FAQController::class, 'index'])->name('faq');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

// Product Routes
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

// Brand Routes
Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand}', [BrandController::class, 'show'])->name('brands.show');

// Orders
Route::middleware('auth')->group(function () {
    Route::get('/account', [OrderController::class, 'index'])->name('account.index');
    Route::get('/account/orders', [OrderController::class, 'orders'])->name('account.orders');
    Route::get('/order/{id}', [OrderController::class, 'show'])->name('order.show');
});

// Stripe Webhook
Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook'])->name('stripe.webhook');

// Profile (Breeze default)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
