@extends('layouts.app')

@section('title', 'Order Successful')

@section('content')

    <div class="success-container">
        <h1 class="success-heading">Thank you for your purchase!</h1>
        <p class="success-text">
            Your order has been successfully placed. A confirmation email will be sent shortly.
        </p>
        <a
            href="{{ route('home') }}"
            class="btn-primary btn-back-home"
        >
            Back to Home
        </a>
    </div>
    @vite('resources/js/checkout.js')
@endsection
