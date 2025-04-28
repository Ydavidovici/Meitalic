<header class="bg-secondary border-b border-primary">
    <nav class="section-wrapper flex items-center justify-between py-6">
        <!-- Logo -->
        <a href="{{ route('home') }}"
           class="text-2xl font-bold text-text no-underline">
            Meitalic
        </a>

        <!-- Desktop Nav -->
        <ul class="hidden md:flex items-center space-x-6">
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
                <x-nav-link :href="route('faq')" :active="request()->routeIs('faq')">
                    About
                </x-nav-link>
            </li>
            <li>
                <x-nav-link :href="route('contact')" :active="request()->routeIs('contact')">
                    Contact
                </x-nav-link>
            </li>

            @guest
                <li>
                    <a href="{{ route('login') }}"
                       class="btn-secondary no-underline">
                        Login
                    </a>
                </li>
            @else
                <li>
                    <x-nav-link :href="route('account.index')" :active="request()->routeIs('account.*')">
                        My Account
                    </x-nav-link>
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="text-gray-500 hover:text-accent transition no-underline">
                            Logout
                        </button>
                    </form>
                </li>
            @endguest
        </ul>

        <!-- Mobile Menu Button -->
        <button
            class="md:hidden p-2 text-text hover:text-accent no-underline"
            @click="$dispatch('open-modal','mobile-nav')"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </nav>

    <!-- Mobile Nav (only on small screens) -->
    <div class="md:hidden">
        <x-responsive-nav-link name="mobile-nav">
            <ul class="space-y-4 bg-white p-6">
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
                    <x-nav-link :href="route('faq')" :active="request()->routeIs('faq')">
                        About
                    </x-nav-link>
                </li>
                <li>
                    <x-nav-link :href="route('contact')" :active="request()->routeIs('contact')">
                        Contact
                    </x-nav-link>
                </li>
                @guest
                    <li>
                        <a href="{{ route('login') }}"
                           class="btn-secondary block text-center no-underline">
                            Login
                        </a>
                    </li>
                @else
                    <li>
                        <x-nav-link :href="route('account.index')" :active="request()->routeIs('account.*')">
                            My Account
                        </x-nav-link>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left text-gray-500 hover:text-accent transition no-underline">
                                Logout
                            </button>
                        </form>
                    </li>
                @endguest
            </ul>
        </x-responsive-nav-link>
    </div>
</header>
