<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController;

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

// Product Routes
Route::get('/products', [ProductController::class, 'index'])->name('products.index'); // Lists all products (e.g., resources/views/pages/product.blade.php or brands/products.blade.php)
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show'); // Shows a single product (could use resources/views/pages/product.blade.php)

// Brand Routes
Route::get('/brands', [BrandController::class, 'index'])->name('brands.index'); // Renders resources/views/pages/brands/index.blade.php
Route::get('/brands/{brand}', [BrandController::class, 'show'])->name('brands.show'); // Renders resources/views/pages/brands/show.blade.php
// Optionally, you can add a route to list products by brand if needed:
// Route::get('/brands/{brand}/products', [BrandController::class, 'products'])->name('brands.products');

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

require __DIR__.'/auth.php';
