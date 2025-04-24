<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    HomeController,
    ContactController,
    ProductController,
    BrandController,
    OrderController,
    PaymentController,
    FAQController,
    ProfileController,
    CartController,
    CheckoutController,
    ConsultationController
};

// Public Pages
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/faq', [FAQController::class, 'index'])->name('faq');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

// Product Routes
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

// Brand Routes
Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand}', [BrandController::class, 'show'])->name('brands.show');

// Orders (Authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/account', [OrderController::class, 'index'])->name('account.index');
    Route::get('/account/orders', [OrderController::class, 'orders'])->name('account.orders');
    Route::get('/order/{id}', [OrderController::class, 'show'])->name('order.show');
});

// Stripe Webhook
Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook'])->name('stripe.webhook');

// Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Routes
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/', fn () => view('pages.admin.dashboard'))->name('admin.dashboard');
    Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders');

    // Product Admin Routes (no admin. prefix on names)
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('admin.products.index');
        Route::get('/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/', [ProductController::class, 'store'])->name('products.store');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });
});


// Checkout
Route::get('/checkout', [CheckoutController::class, 'checkout']);
Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');

// Cart
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::delete('/remove/{id}', [CartController::class, 'remove'])->name('remove');
    Route::patch('/update/{id}', [CartController::class, 'update'])->name('update');
    Route::post('/promo', [CartController::class, 'applyPromo'])->name('applyPromo');
});

// Consultations (Authenticated)
Route::middleware('auth')->prefix('consultations')->name('consultations.')->group(function () {
    Route::get('/', [ConsultationController::class, 'index'])->name('index');
    Route::get('/create', [ConsultationController::class, 'create'])->name('create');
    Route::post('/', [ConsultationController::class, 'store'])->name('store');
});

// Order Placement
Route::post('/order/place', [OrderController::class, 'placeOrder'])->name('order.place');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


// Search
Route::get('/search', [ProductController::class, 'index'])->name('search');

require __DIR__.'/auth.php';
