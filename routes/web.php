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

// ───────────── Public Pages ────────────────
Route::get('/',                   [HomeController::class, 'index'])->name('home');
Route::get('/faq',                [FAQController::class,   'index'])->name('faq');
Route::get('/contact',            [ContactController::class,'index'])->name('contact');
Route::post('/contact',           [ContactController::class,'submit'])->name('contact.submit');

// ───────────── Product & Brand ─────────────
Route::get('/products',           [ProductController::class,'index'])->name('products.index');
Route::get('/products/{slug}',    [ProductController::class,'show'])->name('products.show');
Route::get('/brands',             [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand}',     [BrandController::class, 'show'])->name('brands.show');

// ───────────── Stripe Webhook ──────────────
Route::post('/stripe/webhook',    [PaymentController::class,'handleWebhook'])->name('stripe.webhook');

// ───────────── Checkout & Orders ───────────
Route::get('/checkout',           [CheckoutController::class,'checkout']);
Route::post('/checkout',          [CheckoutController::class,'create'])->name('checkout.create');
Route::get('/checkout/success',   [CheckoutController::class,'success'])->name('checkout.success');
Route::post('/order/place',       [OrderController::class,   'placeOrder'])->name('order.place');

// ───────────── Cart ────────────────────────
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/',               [CartController::class, 'index'])->name('index');
    Route::post('/add',           [CartController::class, 'add'])->name('add');
    Route::delete('/remove/{id}', [CartController::class, 'remove'])->name('remove');
    Route::patch('/update/{id}',  [CartController::class, 'update'])->name('update');
    Route::post('/promo',         [CartController::class, 'applyPromo'])->name('applyPromo');
});

// ───────────── Search ───────────────────────
Route::get('/search',             [ProductController::class,'index'])->name('search');

// ───────────── Auth Routes ──────────────────
require __DIR__.'/auth.php';

// ───────────── Authenticated User Area ─────
Route::middleware('auth')->group(function () {

    // Account & Orders
    Route::get('/account',         [AccountController::class,'index'])->name('account.index');
    Route::get('/account/orders',  [AccountController::class,'orders'])->name('account.orders');
    Route::get('/order/{id}',      [AccountController::class,'show'])->name('order.show');

    Route::get('/dashboard', [AccountController::class,'index'])
        ->name('dashboard');

    // Fetch one order as JSON for the modal
    Route::get('/order/{order}', [AccountController::class,'show'])
        ->name('order.show');

    // User actions
    Route::patch('/order/{order}/cancel', [AccountController::class,'cancel'])
        ->name('order.cancel');
    Route::patch('/order/{order}/return', [AccountController::class,'return'])
        ->name('order.return');

    // Profile
    Route::get('/profile',         [ProfileController::class,'edit'])->name('profile.edit');
    Route::patch('/profile',       [ProfileController::class,'update'])->name('profile.update');
    Route::delete('/profile',      [ProfileController::class,'destroy'])->name('profile.destroy');

    // Dashboard alias
    Route::get('/dashboard',       [AccountController::class,'index'])->name('dashboard');
    Route::get('/dashboard/orders', [AccountController::class,'orders'])->name('dashboard.orders');
    Route::get('/dashboard/cart',   [AccountController::class,'cart'])->name('dashboard.cart');

    // Consultations
    Route::prefix('consultations')->name('consultations.')->group(function () {
        Route::get('/',    [ConsultationController::class,'index'])->name('index');
        Route::get('/create',[ConsultationController::class,'create'])->name('create');
        Route::post('/',    [ConsultationController::class,'store'])->name('store');
    });

    // ───────────── Admin Dashboard & Management ─────────────
    Route::prefix('admin')
        ->name('admin.')
        ->group(function () {

            // Dashboard
            Route::get('/',                         [AdminController::class,'index'])->name('dashboard');
            Route::get('/orders',                   [AdminController::class,'orders'])->name('orders');
            Route::get('/orders/{order}',           [AdminController::class,'show'])->name('orders.show');            // ← show()
            Route::patch('/orders/{order}/status',  [AdminController::class,'updateOrderStatus'])->name('orders.updateStatus');
            Route::patch('/orders/{order}',         [AdminController::class,'update'])->name('orders.update');            // full-edit
            Route::post('/orders/bulk-update',      [AdminController::class,'bulkUpdateOrderStatus'])->name('orders.bulkUpdate');
            Route::patch('/account/orders/{order}/cancel',[AccountController::class,'cancel'])->name('account.orders.cancel');
            Route::patch('/account/orders/{order}/return',[AccountController::class,'return'])->name('account.orders.return');

            // Inventory adjustment
            Route::patch('/products/{product}/adjust', [AdminController::class,'adjustInventory'])->name('products.adjust');

            // Product CRUD
            Route::resource('products', ProductController::class)->names([
                'index'   => 'products.index',
                'create'  => 'products.create',
                'store'   => 'products.store',
                'edit'    => 'products.edit',
                'update'  => 'products.update',
                'destroy' => 'products.destroy',
            ]);

            // Promo CRUD
            Route::resource('promo', PromoCodeController::class)->names([
                'index'   => 'promo.index',
                'create'  => 'promo.create',
                'store'   => 'promo.store',
                'edit'    => 'promo.edit',
                'update'  => 'promo.update',
                'destroy' => 'promo.destroy',
            ]);
        });
});
