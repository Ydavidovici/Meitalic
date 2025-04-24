@extends('layouts.app')

@section('title', 'Order Successful')

@section('content')
    <div class="max-w-2xl mx-auto py-16 text-center">
        <h1 class="text-3xl font-bold mb-4">Thank you for your purchase!</h1>
        <p class="text-gray-600">Your order has been successfully placed. A confirmation email will be sent shortly.</p>
        <a href="{{ route('home') }}" class="mt-6 inline-block bg-black text-white px-6 py-2 rounded">Back to Home</a>
    </div>
@endsection
