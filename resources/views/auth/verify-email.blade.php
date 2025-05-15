@extends('layouts.app')

@section('title', 'Verify Email')

@section('content')
    <div class="container px-4 sm:px-6 lg:px-8 mt-16 max-w-md mx-auto space-y-6">
        <div class="text-sm text-gray-600">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="font-medium text-sm text-green-600">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </div>
        @endif

        <div class="flex items-center justify-between">
            <x-form
                method="POST"
                action="{{ route('verification.send') }}"
                class="inline"
            >
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </x-form>

            <x-form
                method="POST"
                action="{{ route('logout') }}"
                class="inline"
            >
                <button
                    type="submit"
                    class="underline text-sm text-gray-600 hover:text-gray-900"
                >
                    {{ __('Log Out') }}
                </button>
            </x-form>
        </div>
    </div>
@endsection
