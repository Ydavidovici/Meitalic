@extends('layouts.app')

@section('title', 'My Account')

@section('content')
<div class="max-w-4xl mx-auto mt-16">
    <h2 class="text-3xl font-bold mb-6">My Account</h2>

    <p class="mb-4">Welcome, {{ auth()->user()->name ?? 'Guest' }}!</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="{{ route('account.orders') }}" class="block p-6 bg-white border rounded shadow hover:bg-gray-50">
            <h3 class="text-lg font-semibold mb-2">Order History</h3>
            <p class="text-sm text-gray-600">View your past purchases and order statuses.</p>
        </a>

        <a href="{{ route('profile.edit') }}" class="block p-6 bg-white border rounded shadow hover:bg-gray-50">
            <h3 class="text-lg font-semibold mb-2">Edit Profile</h3>
            <p class="text-sm text-gray-600">Update your account information and preferences.</p>
        </a>
    </div>
</div>
@endsection
