<header class="site-header">
    <div class="site-header__inner">
        <!-- Logo -->
        <a href="{{ route('home') }}" class="site-header__logo-link">
            <img
                src="{{ asset('images/logo-meitalic.png') }}"
                alt="Meitalic Logo"
                class="site-header__logo"
            />
        </a>

        <!-- Desktop Nav -->
        <ul class="site-nav">
            <!-- Static Links -->
            <li class="site-nav__item">
                <x-nav-link href="{{ route('home') }}"
                            :active="request()->routeIs('home')"
                            class="site-nav__link">
                    Home
                </x-nav-link>
            </li>
            <li class="site-nav__item">
                <x-nav-link href="{{ route('products.index') }}"
                            :active="request()->routeIs('products.*')"
                            class="site-nav__link">
                    Shop
                </x-nav-link>
            </li>
            <li class="site-nav__item">
                <x-nav-link href="{{ route('home') . '#about' }}"
                            class="site-nav__link">
                    About
                </x-nav-link>
            </li>
            <li class="site-nav__item">
                <x-nav-link href="{{ route('contact') }}"
                            :active="request()->routeIs('contact')"
                            class="site-nav__link">
                    Contact
                </x-nav-link>
            </li>

            <!-- Guest Links -->
            @guest
                <li class="site-nav__item">
                    <a href="{{ route('login') }}" class="site-nav__cta">Login</a>
                </li>
                <li class="site-nav__item">
                    <a href="{{ route('register') }}" class="site-nav__cta">Register</a>
                </li>
            @endguest

            <!-- Authenticated Links -->
            @auth
                @php
                    $user = auth()->user();
                    $accountUrl = $user->is_admin
                      ? route('admin.dashboard')
                      : route('dashboard');
                    $accountActive = $user->is_admin
                      ? request()->routeIs('admin.dashboard')
                      : request()->routeIs('account.*');
                @endphp
                <li class="site-nav__item">
                    <x-nav-link href="{{ $accountUrl }}"
                                :active="$accountActive"
                                class="site-nav__link">
                        My Account
                    </x-nav-link>
                </li>
                <li class="site-nav__item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="site-nav__cta">Logout</button>
                    </form>
                </li>
            @endauth

            <!-- Cart Toggle -->
            <li class="site-nav__item">
                <button @click="$store.cart.toggle()"
                        class="site-nav__link"
                        aria-label="Cart">
                    🛒
                </button>
            </li>
        </ul>

        <!-- Mobile Toggle Button -->
        <button
            class="site-nav-toggle"
            @click="mobileMenuOpen = !mobileMenuOpen"
            aria-label="Toggle menu"
        >☰</button>


    </div>
</header>

<!-- Mobile Overlay Nav -->
<nav
    x-show="mobileMenuOpen"
    @click.self="mobileMenuOpen = false"
    @click.away="mobileMenuOpen = false"
    x-cloak
    class="mobile-nav"
>
    <ul class="mobile-nav__list">
        <!-- Repeat every link in the exact same order -->
        <li><x-nav-link href="{{ route('home') }}"
                        :active="request()->routeIs('home')"
                        class="site-nav__link">Home</x-nav-link></li>
        <li><x-nav-link href="{{ route('products.index') }}"
                        :active="request()->routeIs('products.*')"
                        class="site-nav__link">Shop</x-nav-link></li>
        <li><x-nav-link href="{{ route('home') . '#about' }}"
                        class="site-nav__link">About</x-nav-link></li>
        <li><x-nav-link href="{{ route('contact') }}"
                        :active="request()->routeIs('contact')"
                        class="site-nav__link">Contact</x-nav-link></li>

        @guest
            <li><a href="{{ route('login') }}" class="site-nav__cta">Login</a></li>
            <li><a href="{{ route('register') }}" class="site-nav__cta">Register</a></li>
        @endguest

        @auth
            <li><x-nav-link href="{{ $accountUrl }}"
                            :active="$accountActive"
                            class="site-nav__link">My Account</x-nav-link></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit" class="site-nav__cta">Logout</button>
                </form>
            </li>
        @endauth

        <li>
            <button @click="$store.cart.toggle()"
                    class="site-nav__link"
                    aria-label="Cart">🛒</button>
        </li>
    </ul>
</nav>
