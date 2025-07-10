{{-- resources/views/contact.blade.php --}}
@extends('layouts.app')

@section('title', 'Contact Us')

@section('content')
    <div class="contact-container">
        <h2 class="page-heading">Contact Us</h2>

        @if (session('success'))
            <div class="alert--success">
                {{ session('success') }}
            </div>
        @endif

        <x-form
            method="POST"
            action="{{ route('contact.submit') }}"
            enctype="multipart/form-data"
            class="form--stacked space-y-6"
        >
            @csrf

            <div class="form-group">
                <x-input-label for="name" :value="__('Your Name')" />
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    required
                    class="form-input w-full"
                />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div class="form-group">
                <x-input-label for="email" :value="__('Your Email')" />
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    class="form-input w-full"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <div class="form-group">
                <x-input-label for="photo" :value="__('Upload Photo (optional)')" />
                <input
                    id="photo"
                    name="photo"
                    type="file"
                    accept="image/*"
                    class="form-input w-full"
                />
                <x-input-error :messages="$errors->get('photo')" class="mt-1" />
            </div>

            <div class="form-group">
                <x-input-label for="message" :value="__('Message')" />
                <textarea
                    id="message"
                    name="message"
                    rows="4"
                    required
                    class="form-textarea w-full"
                >{{ old('message') }}</textarea>
                <x-input-error :messages="$errors->get('message')" class="mt-1" />
            </div>

            <button type="submit" class="btn-primary bg-aqua">
                Send Message
            </button>
        </x-form>
    </div>
@endsection
