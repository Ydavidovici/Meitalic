<header class="p-6 border-b border-gray-200">
    <nav class="flex justify-between items-center max-w-7xl mx-auto">
        <h1 class="text-xl font-bold">Meitalic</h1>
        <ul class="flex space-x-6 items-center">
            <li><a href="{{ route('home') }}" class="hover:text-pink-600">Home</a></li>
            <li><a href="{{ route('products.index') }}" class="hover:text-pink-600">Shop</a></li>
            <li><a href="{{ route('faq') }}" class="hover:text-pink-600">About</a></li>
            <li><a href="{{ route('contact') }}" class="hover:text-pink-600">Contact</a></li>

            @guest
                <li>
                    <a href="{{ route('login') }}" class="bg-black text-white px-4 py-2 rounded hover:bg-gray-800">
                        Login
                    </a>
                </li>
            @else
                <li>
                    <a href="{{ route('account.index') }}" class="hover:text-pink-600">
                        My Account
                    </a>
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-red-500 hover:text-red-700">Logout</button>
                    </form>
                </li>
            @endguest
        </ul>
    </nav>
</header>
