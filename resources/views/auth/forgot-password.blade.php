@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
    <div class="auth-page">

        <p class="auth-instructions">
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </p>

        <x-auth-session-status class="auth-session-status" :status="session('status')" />

        <x-form
            method="POST"
            action="{{ route('password.email') }}"
            class="auth-form"
        >
            <div class="auth-form-group">
                <x-input-label for="email" :value="__('Email')" class="auth-label" />
                <x-text-input
                    id="email"
                    name="email"
                    type="email"
                    :value="old('email')"
                    required
                    autofocus
                    class="auth-input"
                />
                <x-input-error :messages="$errors->get('email')" class="auth-error" />
            </div>

            <div class="auth-submit-container">
                <x-primary-button class="auth-submit-btn">
                    {{ __('Email Password Reset Link') }}
                </x-primary-button>
            </div>
        </x-form>

    </div>
@endsection
