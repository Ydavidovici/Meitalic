@extends('layouts.app')
@section('title','Checkout')

@section('content')

    <div
        x-data="checkoutPage()"
        x-init="init()"
        class="checkout-container"
    >

        {{-- Shipping & Contact --}}
        <div
            x-show="items.length"
            class="checkout-card"
        >
            <h3 class="section-heading">Shipping & Contact</h3>

            <textarea
                x-model="form.shipping_address"
                required
                class="form-textarea"
                placeholder="Address"
            ></textarea>

            <input
                x-model="form.email"
                type="email"
                required
                class="form-input"
                placeholder="Email"
            >

            <input
                x-model="form.phone"
                type="text"
                class="form-input"
                placeholder="Phone (optional)"
            >
        </div>

        {{-- Payment --}}
        <div
            x-show="items.length"
            class="checkout-card"
        >
            <h3 class="section-heading">Payment</h3>

            <div
                id="card-element"
                class="form-input"
            ></div>

            <p
                id="card-errors"
                class="error-text"
            ></p>

            <button
                @click="pay()"
                :disabled="loading"
                class="btn-primary btn-pay"
            >
        <span
            x-text="loading ? 'Processing…' : 'Pay $' + total.toFixed(2)"
        ></span>
            </button>
        </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
        @vite('resources/js/checkout.js')
@endsection
