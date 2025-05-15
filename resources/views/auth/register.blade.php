@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <div class="container px-4 sm:px-6 lg:px-8 mt-16 max-w-md mx-auto">
        <x-form
            method="POST"
            action="{{ route('register') }}"
            class="space-y-4"
        >
            <!-- Name -->
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input
                    id="name"
                    name="name"
                    type="text"
                    :value="old('name')"
                    required
                    autofocus
                    autocomplete="name"
                    class="form-input mt-1 w-full"
                />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input
                    id="email"
                    name="email"
                    type="email"
                    :value="old('email')"
                    required
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

            <div class="flex items-center justify-between">
                <a
                    href="{{ route('login') }}"
                    class="underline text-sm text-gray-600 hover:text-gray-900"
                >
                    {{ __('Already registered?') }}
                </a>

                <x-primary-button class="btn-primary">
                    {{ __('Register') }}
                </x-primary-button>
            </div>
        </x-form>
    </div>
@endsection
