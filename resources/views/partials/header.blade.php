<header class="site-header">
    <div class="site-header__inner">
        <!-- Logo -->
        <a href="{{ route('home') }}" class="site-header__logo-link">
            <img
                src="{{ asset('images/logo-meitalic.png') }}"
                alt="Meitalic Logo"
                class="site-header__logo"
            >
        </a>

        <!-- Navigation -->
        <ul class="site-nav" x-data>
            <!-- Static Links -->
            <li class="site-nav__item">
                <x-nav-link
                    :href="route('home')"
                    :active="request()->routeIs('home')"
                    class="site-nav__link"
                >
                    Home
                </x-nav-link>
            </li>
            <li class="site-nav__item">
                <x-nav-link
                    :href="route('products.index')"
                    :active="request()->routeIs('products.*')"
                    class="site-nav__link"
                >
                    Shop
                </x-nav-link>
            </li>
            <li class="site-nav__item">
                <x-nav-link
                    :href="route('home') . '#about'"
                    class="site-nav__link"
                >
                    About
                </x-nav-link>
            </li>
            <li class="site-nav__item">
                <x-nav-link
                    :href="route('contact')"
                    :active="request()->routeIs('contact')"
                    class="site-nav__link"
                >
                    Contact
                </x-nav-link>
            </li>

            <!-- Guest links -->
            @guest
                <template x-if="! $store.auth.isAuthenticated">
                    <li class="site-nav__item">
                        <a href="{{ route('login') }}" class="site-nav__cta">Login</a>
                    </li>
                </template>
                <template x-if="! $store.auth.isAuthenticated">
                    <li class="site-nav__item">
                        <a href="{{ route('register') }}" class="site-nav__cta">Register</a>
                    </li>
                </template>
            @endguest

            <!-- Authenticated links -->
            @auth
                <template x-if="$store.auth.isAuthenticated">
                    <li class="site-nav__item">
                        @php
                            $user = auth()->user();
                            $accountUrl = $user->is_admin
                                ? route('admin.dashboard')
                                : route('dashboard');
                            $accountActive = $user->is_admin
                                ? request()->routeIs('admin.dashboard')
                                : request()->routeIs('account.*');
                        @endphp

                        <x-nav-link
                            :href="$accountUrl"
                            :active="$accountActive"
                            class="site-nav__link"
                        >
                            My Account
                        </x-nav-link>
                    </li>
                </template>
                <template x-if="$store.auth.isAuthenticated">
                    <li class="site-nav__item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="site-nav__cta">Logout</button>
                        </form>
                    </li>
                </template>
            @endauth

            <!-- Cart toggle button (always shown) -->
            <li class="site-nav__item">
                <button
                    @click="Alpine.store('cart').toggle()"
                    aria-label="View cart"
                    class="cart-toggle"
                >
                    🛒
                    <span
                        x-text="$store.cart.count || ''"
                        x-show="$store.cart.count > 0"
                        class="cart-toggle__badge"
                    ></span>
                </button>
            </li>
        </ul>
    </div>
</header>
