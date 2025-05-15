@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <div class="auth-page">

        <div class="auth-card">
            <h2 class="auth-title">Register</h2>

            <x-form method="POST" action="{{ route('register') }}" class="auth-form">

                <!-- Name -->
                <div class="auth-form-group">
                    <x-input-label for="name" :value="__('Name')" class="auth-label" />
                    <x-text-input
                        id="name"
                        name="name"
                        type="text"
                        :value="old('name')"
                        required
                        autofocus
                        autocomplete="name"
                        class="auth-input"
                    />
                    <x-input-error :messages="$errors->get('name')" class="auth-error" />
                </div>

                <!-- Email Address -->
                <div class="auth-form-group">
                    <x-input-label for="email" :value="__('Email')" class="auth-label" />
                    <x-text-input
                        id="email"
                        name="email"
                        type="email"
                        :value="old('email')"
                        required
                        autocomplete="username"
                        class="auth-input"
                    />
                    <x-input-error :messages="$errors->get('email')" class="auth-error" />
                </div>

                <!-- Password -->
                <div class="auth-form-group">
                    <x-input-label for="password" :value="__('Password')" class="auth-label" />
                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="auth-input"
                    />
                    <x-input-error :messages="$errors->get('password')" class="auth-error" />
                </div>

                <!-- Confirm Password -->
                <div class="auth-form-group">
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="auth-label" />
                    <x-text-input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="auth-input"
                    />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="auth-error" />
                </div>

                <div class="auth-footer-links">
                    <a href="{{ route('login') }}" class="auth-link">
                        {{ __('Already registered?') }}
                    </a>
                    <x-primary-button class="auth-submit-btn">
                        {{ __('Register') }}
                    </x-primary-button>
                </div>

            </x-form>
        </div>

    </div>
@endsection
