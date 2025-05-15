@extends('layouts.app')

@section('title', 'Verify Email')

@section('content')
    <div class="auth-verify-page">

        <p class="auth-text">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <p class="auth-status">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </p>
        @endif

        <div class="auth-actions">
            <x-form
                method="POST"
                action="{{ route('verification.send') }}"
                class="auth-form-inline"
            >
                <x-primary-button class="auth-btn-primary">
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </x-form>

            <x-form
                method="POST"
                action="{{ route('logout') }}"
                class="auth-form-inline"
            >
                <button type="submit" class="auth-btn-link">
                    {{ __('Log Out') }}
                </button>
            </x-form>
        </div>

    </div>
@endsection
