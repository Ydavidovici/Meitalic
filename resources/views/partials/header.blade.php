<header class="bg-white border-b border-primary">
    <div class="container flex items-center justify-between py-6 px-4 sm:px-6 lg:px-8">
        <!-- Logo -->
        <a href="{{ route('home') }}" class="no-underline">
            <img src="{{ asset('images/logo-meitalic.png') }}"
                 alt="Meitalic Logo"
                 class="h-8">
        </a>

        <!-- Navigation -->
        <ul class="flex items-center space-x-6" x-data>
            <!-- Static Links -->
            <li>
                <x-nav-link :href="route('home')" :active="request()->routeIs('home')">
                    Home
                </x-nav-link>
            </li>
            <li>
                <x-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')">
                    Shop
                </x-nav-link>
            </li>
            <li>
                <x-nav-link :href="route('home') . '#about'">
                    About
                </x-nav-link>
            </li>
            <li>
                <x-nav-link :href="route('contact')" :active="request()->routeIs('contact')">
                    Contact
                </x-nav-link>
            </li>

            <!-- Guest links -->
            @guest
                <template x-if="! $store.auth.isAuthenticated">
                    <li>
                        <a href="{{ route('login') }}" class="btn-secondary">
                            Login
                        </a>
                    </li>
                </template>
                <template x-if="! $store.auth.isAuthenticated">
                    <li>
                        <a href="{{ route('register') }}" class="btn-secondary">
                            Register
                        </a>
                    </li>
                </template>
            @endguest

            <!-- Authenticated links -->
            @auth
                <template x-if="$store.auth.isAuthenticated">
                    <li>
                        @php
                            $user = auth()->user();
                            $accountUrl = $user->is_admin
                                ? route('admin.dashboard')
                                : route('account.index');
                            $accountActive = $user->is_admin
                                ? request()->routeIs('admin.dashboard')
                                : request()->routeIs('account.*');
                        @endphp

                        <x-nav-link
                            :href="$accountUrl"
                            :active="$accountActive"
                        >
                            My Account
                        </x-nav-link>
                    </li>
                </template>
                <template x-if="$store.auth.isAuthenticated">
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-secondary">
                                Logout
                            </button>
                        </form>
                    </li>
                </template>
            @endauth

            <!-- Cart toggle button (always shown) -->
            <li>
                <button
                    @click="Alpine.store('cart').toggle()"
                    class="relative p-2 rounded-full hover:bg-gray-100 transition"
                    aria-label="View cart"
                >
                    🛒
                    <span
                        x-text="$store.cart.count || ''"
                        x-show="$store.cart.count > 0"
                        class="absolute top-0 right-0 bg-red-500 text-white rounded-full text-xs w-5 h-5 flex items-center justify-center"
                    ></span>
                </button>
            </li>
        </ul>
    </div>
</header>
