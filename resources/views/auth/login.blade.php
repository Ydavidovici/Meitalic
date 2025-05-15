@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="auth-page">
        <div class="auth-card">

            <h2 class="auth-title">Login</h2>

            @if (session('status'))
                <div class="auth-status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="auth-errors">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-form method="POST" action="{{ route('login') }}" class="auth-form">
                <div class="auth-form-group">
                    <label for="email" class="auth-label">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="auth-input"
                    />
                </div>

                <div class="auth-form-group">
                    <label for="password" class="auth-label">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        class="auth-input"
                    />
                </div>

                <div class="flex items-center">
                    <input
                        id="remember_me"
                        name="remember"
                        type="checkbox"
                        class="auth-checkbox"
                    />
                    <label for="remember_me" class="auth-checkbox-label">
                        Remember me
                    </label>
                </div>

                <div class="auth-footer-links">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="auth-link">
                            Forgot your password?
                        </a>
                    @endif

                    <button type="submit" class="auth-submit-btn">
                        Log in
                    </button>
                </div>
            </x-form>
        </div>
    </div>
@endsection
