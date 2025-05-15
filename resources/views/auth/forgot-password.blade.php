@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
    <div class="container px-4 sm:px-6 lg:px-8 mt-16 max-w-md mx-auto">
        <div class="mb-6 text-sm text-gray-600">
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <x-form
            method="POST"
            action="{{ route('password.email') }}"
            class="space-y-4"
        >
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input
                    id="email"
                    name="email"
                    type="email"
                    :value="old('email')"
                    required
                    autofocus
                    class="form-input mt-1 w-full"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end">
                <x-primary-button class="btn-primary">
                    {{ __('Email Password Reset Link') }}
                </x-primary-button>
            </div>
        </x-form>
    </div>
@endsection
