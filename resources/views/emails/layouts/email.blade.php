<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('subject', config('app.name'))</title>
    <style>
        /* your email‑safe CSS here */
        body { font-family: sans-serif; color: #333; }
        .header { background: #f5f5f5; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .footer { background: #f5f5f5; padding: 10px; text-align: center; font-size: .8em; color: #777; }
    </style>
</head>
<body>
<div class="header">
    <h1>{{ config('app.name') }}</h1>
</div>

<div class="content">
    @yield('content')
</div>

<div class="footer">
    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
</div>
</body>
</html>
