@extends('layouts.app')

@section('title', 'Reset Password')

@section('content')
    <div class="auth-page">

        <div class="auth-card">
            <h2 class="auth-title">Reset Password</h2>

            <x-form
                method="POST"
                action="{{ route('password.store') }}"
                class="auth-form"
            >
                <!-- Token -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <!-- Email Address -->
                <div class="auth-form-group">
                    <x-input-label for="email" :value="__('Email')" class="auth-label" />
                    <x-text-input
                        id="email"
                        name="email"
                        type="email"
                        :value="old('email', $request->email)"
                        required
                        autofocus
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

                <div class="auth-submit-container">
                    <x-primary-button class="auth-submit-btn">
                        {{ __('Reset Password') }}
                    </x-primary-button>
                </div>
            </x-form>
        </div>

    </div>
@endsection
