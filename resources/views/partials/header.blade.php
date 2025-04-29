<header class="bg-white border-b border-primary">
    <div class="container flex items-center justify-between py-6 px-4 sm:px-6 lg:px-8">
        <!-- Logo -->
        <a href="{{ route('home') }}" class="no-underline">
            <img src="{{ asset('/images/logo-meitalic.png') }}"
                 alt="Meitalic Logo"
                 class="h-8">
        </a>

        <!-- Navigation -->
        <ul class="flex items-center space-x-6">
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
                    <a href="{{ route('login') }}" class="btn-secondary">
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
                        <button type="submit" class="text-gray-500 hover:text-accent no-underline">
                            Logout
                        </button>
                    </form>
                </li>
            @endguest
        </ul>
    </div>
</header>
