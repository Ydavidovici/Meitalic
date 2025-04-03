<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider.
|
*/

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home'); // Renders resources/views/pages/home.blade.php
Route::get('/contact', [ContactController::class, 'index'])->name('contact'); // Renders resources/views/pages/contact.blade.php

// Product Routes (Public)
Route::get('/products', [ProductController::class, 'index'])->name('products.index'); // Lists all products
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show'); // Shows a single product

// Brand Routes (Public)
Route::get('/brands', [BrandController::class, 'index'])->name('brands.index'); // Renders brands index page
Route::get('/brands/{brand}', [BrandController::class, 'show'])->name('brands.show'); // Renders a brand's detail page

// Dashboard and Auth Protected Routes (Breeze default)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Profile Routes (Breeze default)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Routes (protected by auth middleware)
// Also includes admin product CRUD routes
Route::middleware(['auth'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/orders', [AdminController::class, 'orders'])->name('admin.orders');

    // Admin Product CRUD Routes
    Route::get('/admin/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/admin/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/admin/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/admin/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/admin/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
});

// Stripe Webhook Route
Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook'])->name('stripe.webhook');

// Order Routes
Route::post('/order/place', [OrderController::class, 'placeOrder'])->name('order.place');
Route::get('/order/{id}', [OrderController::class, 'show'])->name('order.show');

require __DIR__.'/auth.php';
