@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="max-w-md mx-auto mt-16 p-6 border rounded shadow bg-white">
    <h2 class="text-2xl font-bold mb-4">Login</h2>

    @if (session('status'))
        <div class="mb-4 text-green-600 font-medium">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 text-red-600">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full mt-1 p-2 border rounded">
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium">Password</label>
            <input id="password" type="password" name="password" required class="w-full mt-1 p-2 border rounded">
        </div>

        <div class="mb-4 flex items-center">
            <input type="checkbox" name="remember" id="remember_me" class="mr-2">
            <label for="remember_me" class="text-sm text-gray-600">Remember me</label>
        </div>

        <div class="flex items-center justify-between">
            @if (Route::has('password.request'))
                <a class="text-sm text-pink-600 hover:underline" href="{{ route('password.request') }}">
                    Forgot your password?
                </a>
            @endif

            <button type="submit" class="bg-black text-white px-4 py-2 rounded hover:bg-gray-800">
                Log in
            </button>
        </div>
    </form>
</div>
@endsection
