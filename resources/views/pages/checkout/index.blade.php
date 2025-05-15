@extends('layouts.app')
@section('title','Checkout')

@section('content')

    <x-form
        method="POST"
        action="{{ route('checkout.create') }}"
        id="checkout-form"
        class="checkout-container space-y-6"
        x-data="checkoutPage()"
        x-init="init()"
    >
        {{-- Shipping & Contact --}}
        <div
            x-show="items.length"
            class="checkout-card"
        >
            <h3 class="section-heading">Shipping & Contact</h3>

            {{-- Address --}}
            <div class="form-group">
                <label for="shipping_address" class="block font-medium mb-1">Address</label>
                <textarea
                    id="shipping_address"
                    name="shipping_address"
                    x-model="form.shipping_address"
                    required
                    class="form-textarea"
                    placeholder="Address"
                ></textarea>
                <x-input-error :messages="$errors->get('shipping_address')" class="mt-1" />
            </div>

            {{-- Calculate Shipping --}}
            <div class="form-group">
                <button
                    type="button"
                    @click="calculateShipping()"
                    :disabled="loading"
                    class="btn-secondary"
                >
                    Calculate Shipping
                </button>
            </div>

            {{-- Display Shipping Fee --}}
            <div
                x-show="shipping !== null"
                class="form-group"
            >
                <p class="font-medium">Shipping: $<span x-text="shipping.toFixed(2)"></span></p>
            </div>

            {{-- Contact --}}
            <div class="form-group">
                <label for="email" class="block font-medium mb-1">Email</label>
                <input
                    id="email"
                    name="email"
                    x-model="form.email"
                    type="email"
                    required
                    class="form-input"
                    placeholder="Email"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <div class="form-group">
                <label for="phone" class="block font-medium mb-1">Phone (optional)</label>
                <input
                    id="phone"
                    name="phone"
                    x-model="form.phone"
                    type="text"
                    class="form-input"
                    placeholder="Phone (optional)"
                />
                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
            </div>
        </div>

        {{-- Payment --}}
        <div
            x-show="items.length"
            class="checkout-card"
        >
            <h3 class="section-heading">Payment</h3>

            <div class="form-group">
                <label for="card-element" class="block font-medium mb-1">Card Details</label>
                <div id="card-element" class="form-input"></div>
                <p id="card-errors" class="error-text mt-1"></p>
            </div>

            <button
                type="button"
                @click="pay()"
                :disabled="loading"
                class="btn-primary btn-pay"
            >
                <span x-text="loading
                    ? 'Processing…'
                    : `Pay $${(total + (shipping||0)).toFixed(2)}`"></span>
            </button>
        </div>
    </x-form>

    <script src="https://js.stripe.com/v3/"></script>
@endsection
