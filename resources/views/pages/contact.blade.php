@extends('layouts.app')

@section('title', 'Contact Us')

@section('content')
    <div class="max-w-2xl mx-auto mt-16">
        <h2 class="text-3xl font-bold mb-6">Contact Us</h2>

        @if (session('success'))
            <div class="mb-6 p-4 bg-green-100 text-green-800 rounded">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('contact.submit') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium">Your Name</label>
                <input type="text" id="name" name="name" required class="w-full mt-1 p-2 border rounded">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium">Your Email</label>
                <input type="email" id="email" name="email" required class="w-full mt-1 p-2 border rounded">
            </div>

            <div>
                <label for="photo" class="block text-sm font-medium">Upload Photo (optional)</label>
                <input type="file" id="photo" name="photo" class="w-full mt-1 p-2 border rounded">
            </div>

            <div>
                <label for="message" class="block text-sm font-medium">Message</label>
                <textarea id="message" name="message" rows="4" required class="w-full mt-1 p-2 border rounded"></textarea>
            </div>

            <button type="submit" class="bg-black text-white px-6 py-2 rounded hover:bg-gray-800">Send Message</button>
        </form>
    </div>
@endsection
