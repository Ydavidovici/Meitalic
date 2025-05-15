@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="container px-4 sm:px-6 lg:px-8 mt-16">
        <div class="max-w-md mx-auto p-6 border rounded shadow bg-white">
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

            <x-form method="POST" action="{{ route('login') }}" class="space-y-4">
                <div class="form-group">
                    <label for="email" class="block text-sm font-medium">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="form-input w-full"
                    />
                </div>

                <div class="form-group">
                    <label for="password" class="block text-sm font-medium">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        class="form-input w-full"
                    />
                </div>

                <div class="flex items-center">
                    <input
                        id="remember_me"
                        name="remember"
                        type="checkbox"
                        class="form-input w-auto mr-2"
                    />
                    <label for="remember_me" class="text-sm text-gray-600">Remember me</label>
                </div>

                <div class="flex items-center justify-between">
                    @if (Route::has('password.request'))
                        <a
                            href="{{ route('password.request') }}"
                            class="text-sm text-pink-600 hover:underline"
                        >
                            Forgot your password?
                        </a>
                    @endif

                    <button type="submit" class="btn-primary">
                        Log in
                    </button>
                </div>
            </x-form>
        </div>
    </div>
@endsection
