<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('logo-meitalic.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="tax-rate" content="{{ config('cart.tax_rate', 0) }}">
    <meta name="reviews-store-route" content="{{ route('dashboard.reviews.store') }}">
    <title>@yield('title','Meitalic')</title>

    {{-- expose auth state before scripts --}}
    <script> window.isAuthenticated = @json(auth()->check()); </script>


    @vite([
      'resources/css/globals.css',
      'resources/css/pages/home.css',
      'resources/css/pages/products.css',
      'resources/css/pages/product.css',
      'resources/css/pages/cart/index.css',
      'resources/css/partials/product-grid.css',
      'resources/css/auth/auth.css',
    ])
    @stack('styles')
</head>
<body x-data="{}" class="layout-root">
<div class="layout-vh">

    @include('partials.header')

    {{-- CART OVERLAY & SIDEBAR --}}
    <div
        x-data="{}"
        x-show="$store.cart.open"
        x-cloak                          {{-- ←–– hide initially, Alpine will remove when true --}}
        x-on:keydown.window.escape="$store.cart.close()"
        class="cart-overlay"
    >
        <div class="cart-backdrop" @click="$store.cart.close()"></div>

        <aside
            x-data="cartSidebar()"
            x-init="load()"
            class="cart-panel"
        >
            <header class="cart-header">
                <h2 class="cart-title">Your Cart</h2>
                <button @click="$store.cart.close()" class="cart-close-btn">✕</button>
            </header>

            <div class="cart-body">

                <template x-if="!loading && items.length === 0">
                    <p class="text-center text-gray-600">Your cart is empty.</p>
                </template>

                <template x-if="!loading && items.length">
                    <ul class="cart-list">
                        <template x-for="item in items" :key="item.id">
                            <li class="cart-item">
                                <img :src="item.product.image_url" class="cart-item-img">
                                <div class="cart-item-info">
                                    <p class="cart-item-name" x-text="item.product.name"></p>
                                    <p class="cart-item-meta">
                                        $<span x-text="item.price.toFixed(2)"></span>
                                        ×
                                        <span x-text="item.quantity"></span>
                                    </p>
                                </div>
                                <button @click="remove(item.id)" class="cart-item-remove">
                                    Remove
                                </button>
                            </li>
                        </template>
                    </ul>
                </template>
            </div>

            <template x-if="!loading">     {{-- wrap footer so no “$0.00” flash on load --}}
                <footer class="cart-footer">
                    <div class="cart-summary-row">
                        <span class="cart-summary-label">Subtotal:</span>
                        <span class="cart-summary-value">
                $<span x-text="subtotal.toFixed(2)"></span>
              </span>
                    </div>
                    <div class="cart-summary-row">
                        <span class="cart-summary-label">Discount:</span>
                        <span class="cart-summary-value">
                − $<span x-text="discount.toFixed(2)"></span>
              </span>
                    </div>
                    <div class="cart-summary-row">
                        <span class="cart-summary-label">Tax:</span>
                        <span class="cart-summary-value">
                $<span x-text="tax.toFixed(2)"></span>
              </span>
                    </div>
                    <div class="cart-total-row">
                        <span class="cart-total-label">Total:</span>
                        <span class="cart-total-value">
                $<span x-text="total.toFixed(2)"></span>
              </span>
                    </div>
                    <a
                             href="{{ route('cart.index') }}"
                             class="block w-full text-center mb-4 text-gray-700 hover:text-gray-900"
                           >
                             View Cart
                           </a>
                    <a href="{{ route('checkout') }}" class="cart-checkout-btn">
                        Checkout
                    </a>
                </footer>
            </template>
        </aside>
    </div>
    {{-- END CART OVERLAY & SIDEBAR --}}

    <main class="layout-main">
        @yield('content')
    </main>

    @include('partials.footer')
</div>

{{-- only globals.js is needed now; it imports shop.js + checkout.js --}}
@vite('resources/js/globals.js')
</body>
</html>
