<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Meitalic')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-text">
<div class="min-h-screen bg-gradient-to-br from-secondary via-primary to-accent">
    @include('partials.header')

    <main class="pt-16">
        @yield('content')
    </main>

    @include('partials.footer')
</div>
</body>
</html>
