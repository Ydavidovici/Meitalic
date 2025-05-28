{{-- resources/views/pages/checkout/index.blade.php --}}
@extends('layouts.app')
@section('title','Checkout')

@section('content')
    <x-form
        method="POST"
        action="{{ route('checkout.create') }}"
        id="checkout-form"
        class="checkout-container"
        x-data="checkoutPage()"
        x-init="init()"
    >
        {{-- STEP 1: Customer & Shipping Info --}}
        <div x-show="step===1" class="checkout-card">
            <h3 class="section-heading">1. Your Info</h3>

            <div class="checkout-grid">
                <!-- Full-width Name -->
                <div class="form-group col-span-2">
                    <label for="name" class="block font-medium mb-1">Name</label>
                    <input
                        id="name" name="name" type="text"
                        x-model="form.name"
                        class="form-input"
                        placeholder="Full name"
                        required
                    >
                </div>

                <!-- Email & Phone -->
                <div class="form-group">
                    <label for="email" class="block font-medium mb-1">Email</label>
                    <input
                        id="email" name="email" type="email"
                        x-model="form.email"
                        class="form-input"
                        placeholder="you@example.com"
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="phone" class="block font-medium mb-1">Phone (optional)</label>
                    <input
                        id="phone" name="phone" type="text"
                        x-model="form.phone"
                        class="form-input"
                        placeholder="(123) 456-7890"
                    >
                </div>

                <!-- Address (full width) -->
                <div class="form-group col-span-2">
                    <label for="shipping_address" class="block font-medium mb-1">Address</label>
                    <textarea
                        id="shipping_address" name="shipping_address"
                        x-model="form.shipping_address"
                        class="form-textarea"
                        placeholder="Street, Apt, etc."
                        required
                    ></textarea>
                </div>

                <!-- City / State / ZIP / Country -->
                <div class="form-group">
                    <label for="city" class="block font-medium mb-1">City</label>
                    <input id="city" name="city" x-model="form.city" class="form-input" placeholder="City" required>
                </div>
                <div class="form-group">
                    <label for="state" class="block font-medium mb-1">State</label>
                    <input id="state" name="state" x-model="form.state" class="form-input" placeholder="State" required>
                </div>
                <div class="form-group">
                    <label for="postal_code" class="block font-medium mb-1">ZIP</label>
                    <input id="postal_code" name="postal_code" x-model="form.postal_code" class="form-input" placeholder="ZIP" required>
                </div>
                <div class="form-group">
                    <label for="country" class="block font-medium mb-1">Country</label>
                    <input id="country" name="country" x-model="form.country" class="form-input" placeholder="Country" required>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button
                    type="button"
                    @click="goToStep(2)"
                    class="btn-primary"
                >Next</button>
            </div>
        </div>


        {{-- STEP 2: Review & Promo --}}
        <div x-show="step===2" class="checkout-card">
            <h3 class="section-heading">2. Review Your Order</h3>

            <div class="space-y-2 mb-4">
                <div>Subtotal: $<span x-text="subtotal.toFixed(2)"></span></div>
                <div>Discount: − $<span x-text="discount.toFixed(2)"></span></div>
                <div>Tax: $<span x-text="tax.toFixed(2)"></span></div>
                <div>Shipping: $<span x-text="shippingFee.toFixed(2)"></span></div>
                <div class="text-lg font-semibold">Total: $<span x-text="total.toFixed(2)"></span></div>
            </div>

            <div class="form-group mb-6">
                <input
                    type="text"
                    placeholder="Promo code"
                    x-model="promoCode"
                    class="form-input inline-block w-auto"
                >
                <button
                    type="button"
                    @click="applyPromo()"
                    class="btn-secondary ml-2"
                >Apply</button>
                <p x-text="promoError" class="text-red-600 mt-1"></p>
            </div>

            <div class="flex justify-between">
                <button
                    type="button"
                    @click="goToStep(1)"
                    class="btn-secondary"
                >Back</button>
                <button
                    type="button"
                    @click="goToStep(3)"
                    class="btn-primary"
                >Continue to Payment</button>
            </div>
        </div>


        {{-- STEP 3: Payment --}}
        <div x-show="step===3" class="checkout-card">
            <h3 class="section-heading">3. Payment</h3>

            <div class="form-group">
                <label class="block font-medium mb-1">Card Number</label>
                <div id="card-number" class="form-input"></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="block font-medium mb-1">Expiry</label>
                    <div id="card-expiry" class="form-input"></div>
                </div>
                <div class="form-group">
                    <label class="block font-medium mb-1">CVC</label>
                    <div id="card-cvc" class="form-input"></div>
                </div>
            </div>

            <div class="mt-6">
                <button
                    type="button"
                    @click="pay()"
                    :disabled="loading"
                    class="btn-primary btn-pay w-full"
                >
        <span x-text="loading
          ? 'Processing…'
          : `Pay $${total.toFixed(2)}`"></span>
                </button>
            </div>

            <div class="mt-4 text-center">
                <button
                    type="button"
                    @click="goToStep(2)"
                    class="btn-secondary"
                >Back</button>
            </div>
        </div>
    </x-form>

    <script src="https://js.stripe.com/v3/"></script>
@endsection
