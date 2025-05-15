@extends('layouts.app')

@section('title', 'Reset Password')

@section('content')
    <div class="container px-4 sm:px-6 lg:px-8 mt-16 max-w-md mx-auto">
        <x-form
            method="POST"
            action="{{ route('password.store') }}"
            class="space-y-4"
        >
            <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input
                    id="email"
                    name="email"
                    type="email"
                    :value="old('email', $request->email)"
                    required
                    autofocus
                    autocomplete="username"
                    class="form-input mt-1 w-full"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="form-input mt-1 w-full"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="form-input mt-1 w-full"
                />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end">
                <x-primary-button class="btn-primary">
                    {{ __('Reset Password') }}
                </x-primary-button>
            </div>
        </x-form>
    </div>
@endsection
