<header class="site-header">
    <div class="site-header__inner flex items-center">
        <!-- 1) Logo -->
        <a href="{{ route('home') }}" class="site-header__logo-link">
            <img
                src="{{ asset('images/logo-meitalic.png') }}"
                alt="Meitalic Logo"
                class="site-header__logo"
            />
        </a>

        <!-- 2+3) Desktop nav + cart grouped and pushed right -->
        <div class="hidden md:flex items-center space-x-6 ml-auto">
            <!-- Desktop Nav -->
            <ul class="site-nav">
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

                @guest
                    <li class="site-nav__item">
                        <a href="{{ route('login') }}" class="site-nav__cta">Login</a>
                    </li>
                    <li class="site-nav__item">
                        <a href="{{ route('register') }}" class="site-nav__cta">Register</a>
                    </li>
                @endguest

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
            </ul>

            <!-- Desktop Cart -->
            <button @click="$store.cart.toggle()"
                    class="site-nav__link text-white"
                    aria-label="Cart">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                    <path d="M1.75 1.002a.75.75 0 1 0 0 1.5h1.835l1.24 5.113A3.752 3.752 0 0 0 2 11.25c0 .414.336.75.75.75h10.5a.75.75 0 0 0 0-1.5H3.628A2.25 2.25 0 0 1 5.75 9h6.5a.75.75 0 0 0 .73-.578l.846-3.595a.75.75 0 0 0-.578-.906 44.118 44.118 0 0 0-7.996-.91l-.348-1.436a.75.75 0 0 0-.73-.573H1.75ZM5 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM13 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                </svg>
            </button>
        </div>

        <!-- 4) Mobile menu + cart (only below md) -->
        <div class="flex items-center space-x-4 md:hidden ml-auto">
            <!-- Menu toggle -->
            <button class="site-nav-toggle"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    aria-label="Toggle menu">
                ☰
            </button>

            <!-- Mobile Cart -->
            <button @click="$store.cart.toggle()"
                    class="site-nav__link text-white"
                    aria-label="Cart">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                    <path d="M1.75 1.002a.75.75 0 1 0 0 1.5h1.835l1.24 5.113A3.752 3.752 0 0 0 2 11.25c0 .414.336.75.75.75h10.5a.75.75 0 0 0 0-1.5H3.628A2.25 2.25 0 0 1 5.75 9h6.5a.75.75 0 0 0 .73-.578l.846-3.595a.75.75 0 0 0-.578-.906 44.118 44.118 0 0 0-7.996-.91l-.348-1.436a.75.75 0 0 0-.73-.573H1.75ZM5 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM13 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                </svg>
            </button>
        </div>
    </div>
</header>

<nav
    x-show="mobileMenuOpen"
    @click.self="mobileMenuOpen = false"
    @click.away="mobileMenuOpen = false"
    x-cloak
    class="mobile-nav"
>
    <ul class="mobile-nav__list">
        <li>
            <x-nav-link href="{{ route('home') }}"
                        :active="request()->routeIs('home')"
                        class="site-nav__link">
                Home
            </x-nav-link>
        </li>
        <li>
            <x-nav-link href="{{ route('products.index') }}"
                        :active="request()->routeIs('products.*')"
                        class="site-nav__link">
                Shop
            </x-nav-link>
        </li>
        <li>
            <x-nav-link href="{{ route('home') . '#about' }}"
                        class="site-nav__link">
                About
            </x-nav-link>
        </li>
        <li>
            <x-nav-link href="{{ route('contact') }}"
                        :active="request()->routeIs('contact')"
                        class="site-nav__link">
                Contact
            </x-nav-link>
        </li>

        @guest
            <li><a href="{{ route('login') }}" class="site-nav__cta">Login</a></li>
            <li><a href="{{ route('register') }}" class="site-nav__cta">Register</a></li>
        @endguest

        @auth
            <li>
                <x-nav-link href="{{ $accountUrl }}"
                            :active="$accountActive"
                            class="site-nav__link">
                    My Account
                </x-nav-link>
            </li>
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
