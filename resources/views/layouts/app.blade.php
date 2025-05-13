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

    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="overflow-y-scroll font-sans antialiased text-text bg-gradient-to-br from-secondary via-primary">
<div class="flex flex-col min-h-screen">

    @include('partials.header')

    {{-- CART OVERLAY & SIDEBAR --}}
    <div
        x-data="{}"
        x-show="$store.cart.open"
        x-on:keydown.window.escape="$store.cart.close()"
        class="fixed inset-0 z-40"
        style="display: none;"
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black bg-opacity-50"
            @click="$store.cart.close()"
        ></div>

        {{-- Sidebar Panel --}}
        <aside
            x-data="cartSidebar()"
            x-ref="cartSidebar"
            x-init="load()"
            class="absolute inset-y-0 right-0 w-96 max-w-full bg-white shadow-xl flex flex-col z-50"
        >
            {{-- Header --}}
            <header class="flex items-center justify-between px-6 py-4 border-b">
                <h2 class="text-lg font-semibold">Your Cart</h2>
                <button @click="$store.cart.close()" class="text-gray-600 hover:text-gray-800">
                    ✕
                </button>
            </header>

            {{-- Body --}}
            <div class="flex-1 overflow-auto px-6 py-4">
                <template x-if="loading">
                    <p class="text-center text-gray-500">Loading…</p>
                </template>

                <template x-if="!loading && items.length === 0">
                    <p class="text-center text-gray-600">Your cart is empty.</p>
                </template>

                <template x-if="!loading && items.length">
                    <ul class="space-y-4">
                        <template x-for="item in items" :key="item.id">
                            <li class="flex items-center">
                                <img
                                    :src="item.product.image_url"
                                    class="w-16 h-16 object-cover rounded"
                                    alt=""
                                />
                                <div class="ml-4 flex-1">
                                    <p class="font-medium" x-text="item.product.name"></p>
                                    <p class="text-sm text-gray-500">
                                        $<span x-text="item.price.toFixed(2)"></span>
                                        ×
                                        <span x-text="item.quantity"></span>
                                    </p>
                                </div>
                                <button
                                    @click="remove(item.id)"
                                    class="text-red-500 hover:text-red-700 ml-4"
                                >
                                    Remove
                                </button>
                            </li>
                        </template>
                    </ul>
                </template>
            </div>

            {{-- Footer --}}
            <footer class="border-t px-6 py-4">
                <div class="flex justify-between mb-2">
                    <span class="font-medium">Subtotal:</span>
                    <span class="font-medium">$<span x-text="subtotal.toFixed(2)"></span></span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="font-medium">Discount:</span>
                    <span class="font-medium">− $<span x-text="discount.toFixed(2)"></span></span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="font-medium">Tax:</span>
                    <span class="font-medium">$<span x-text="tax.toFixed(2)"></span></span>
                </div>
                <div class="flex justify-between mb-4">
                    <span class="font-semibold">Total:</span>
                    <span class="font-semibold">$<span x-text="total.toFixed(2)"></span></span>
                </div>
                <a
                    href="{{ route('checkout') }}"
                    class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded"
                >
                    Checkout
                </a>
            </footer>
        </aside>
    </div>
    {{-- END CART OVERLAY & SIDEBAR --}}

    <main class="flex-grow pt-16">
        @yield('content')
    </main>

    @include('partials.footer')
</div>
</body>
</html>
