<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('logo-meitalic.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','Meitalic')</title>

    {{-- Inject auth state before any scripts load --}}
    <script>
        window.isAuthenticated = @json(auth()->check());
    </script>

    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="overflow-y-scroll font-sans antialiased text-text bg-gradient-to-br from-secondary via-primary">
<div class="flex flex-col min-h-screen">
    @include('partials.header')

    <main class="flex-grow pt-16">
        @yield('content')
    </main>

    @include('partials.footer')
</div>
</body>
</html>
