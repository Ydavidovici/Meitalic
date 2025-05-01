<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    HomeController,
    FAQController,
    ContactController,
    ProductController,
    BrandController,
    OrderController,
    PaymentController,
    ProfileController,
    CartController,
    CheckoutController,
    ConsultationController,
    AccountController,
    AdminController,
    PromoCodeController,
};

// ───────────── Public Pages ────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/faq', [FAQController::class, 'index'])->name('faq');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

// ───────────── Product & Brand ─────────────────
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand}', [BrandController::class, 'show'])->name('brands.show');

// ───────────── Stripe Webhook ──────────────────
Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook'])->name('stripe.webhook');

// ───────────── Checkout & Order Placement ────
Route::get('/checkout', [CheckoutController::class, 'checkout']);
Route::post('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/order/place', [OrderController::class, 'placeOrder'])->name('order.place');

// ───────────── Cart ───────────────────────────
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/',               [CartController::class, 'index'])->name('index');
    Route::post('/add',           [CartController::class, 'add'])->name('add');
    Route::delete('/remove/{id}', [CartController::class, 'remove'])->name('remove');
    Route::patch('/update/{id}',  [CartController::class, 'update'])->name('update');
    Route::post('/promo',         [CartController::class, 'applyPromo'])->name('applyPromo');
});

// ───────────── Search ─────────────────────────
Route::get('/search', [ProductController::class, 'index'])->name('search');

// ───────────── Authentication Routes ─────────
require __DIR__.'/auth.php';

// ───────────── Authenticated User Area ───────
Route::middleware('auth')->group(function () {
    // Account & Orders
    Route::get('/account',        [AccountController::class, 'index'])->name('account.index');
    Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders');
    Route::get('/order/{id}',     [AccountController::class, 'show'])->name('order.show');

    // Profile Management
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User Dashboard
    Route::get('/dashboard',        [AccountController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/orders', [AccountController::class, 'orders'])->name('dashboard.orders');
    Route::get('/dashboard/cart',   [AccountController::class, 'cart'])->name('dashboard.cart');

    // Consultations
    Route::prefix('consultations')->name('consultations.')->group(function () {
        Route::get('/',       [ConsultationController::class, 'index'])->name('index');
        Route::get('/create', [ConsultationController:: class, 'create'])->name('create');
        Route::post('/',      [ConsultationController::class, 'store'])->name('store');
    });

    // Admin Dashboard & Management
    Route::prefix('admin')
        ->name('admin.')
        ->group(function () {

            // Dashboard & Orders
            Route::get('/',        [AdminController::class, 'index'])->name('dashboard');
            Route::get('/orders',  [AdminController::class, 'orders'])->name('orders');

            Route::prefix('products')->name('products.')->group(function () {
                Route::get('/',               [ProductController::class, 'index'])->name('index');
                Route::get('/create',         [ProductController::class, 'create'])->name('create');
                Route::post('/',              [ProductController::class, 'store'])->name('store');
                Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
                Route::put('/{product}',      [ProductController::class, 'update'])->name('update');
                Route::delete('/{product}',   [ProductController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('promo')->name('promo.')->group(function () {
                Route::get('/',               [PromoCodeController::class, 'index'])->name('index');
                Route::get('/create',         [PromoCodeController::class, 'create'])->name('create');
                Route::post('/',              [PromoCodeController::class, 'store'])->name('store');
                Route::get('/{promo}/edit',   [PromoCodeController::class, 'edit'])->name('edit');
                Route::put('/{promo}',        [PromoCodeController::class, 'update'])->name('update');
                Route::delete('/{promo}',     [PromoCodeController::class, 'destroy'])->name('destroy');
            });
        });
    });
