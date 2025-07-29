{{-- resources/views/pages/checkout/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Checkout')

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
                    <input
                        id="city" name="city"
                        x-model="form.city"
                        class="form-input"
                        placeholder="City"
                        required
                    >
                </div>

                <div
                    x-show="form.country === 'US'"
                    class="form-group"
                    x-cloak
                >
                    <label for="state" class="block font-medium mb-1">State</label>
                    <select
                        id="state" name="state"
                        x-model="form.state"
                        class="form-input"
                        :required="form.country === 'US'"
                    >
                    >
                        <option value="" disabled>Select a state</option>
                        @foreach(config('shipping.states') as $code => $label)
                            <option value="{{ $code }}">{{ $label }} ({{ $code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="postal_code" class="block font-medium mb-1">ZIP</label>
                    <input
                        id="postal_code" name="postal_code"
                        x-model="form.postal_code"
                        class="form-input"
                        placeholder="ZIP"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="country" class="block font-medium mb-1">Country</label>
                    <select
                        id="country" name="country"
                        x-model="form.country"
                        class="form-input"
                        required
                    >
                        <option value="" disabled>Select a country</option>
                        @foreach(config('shipping.countries') as $code => $name)
                            <option value="{{ $code }}">{{ $name }} ({{ $code }})</option>
                        @endforeach
                    </select>
                </div>
            </div> {{-- end checkout-grid --}}

            <div class="flex justify-end mt-6">
                <button
                    type="button"
                    @click="goToStep(2)"
                    class="btn-primary"
                >
                    Next
                </button>
            </div>
        </div> {{-- end step 1 card --}}

        {{-- STEP 2: Review, Promo & Shipping Options --}}
        <div x-show="step===2" class="checkout-card space-y-6">
            <h3 class="section-heading">2. Review Your Order & Shipping</h3>

            {{-- Promo Code --}}
            <div class="form-group mb-4">
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
                >
                    Apply
                </button>
                <p x-text="promoError" class="text-red-600 mt-1"></p>
            </div>

            {{-- Shipping Options --}}
            <div class="space-y-2">
                <h4 class="font-medium mb-2">Shipping Options</h4>

                <template x-if="!shippingLoading && rates.length">
                    <div class="space-y-2">
                        <template x-for="r in rates" :key="r.serviceCode">
                            <label class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <input
                                        type="radio"
                                        name="serviceCode"
                                        :value="r.serviceCode"
                                        :checked="selectedRate?.serviceCode === r.serviceCode"
                                        @change="selectRate(r)"
                                        class="form-radio mr-2"
                                    >
                                    <span x-text="r.serviceName"></span>
                                </div>

                                <div class="flex items-baseline">
                                    <!-- price -->
                                    <span class="font-medium mr-2">
            $<span x-text="(r.shipmentCost + r.otherCost).toFixed(2)"></span>
          </span>

                                    <!-- days -->
                                    <template x-if="r.deliveryDays != null">
            <span class="text-sm text-gray-500">
              (<span x-text="formatDays(r)"></span>)
            </span>
                                    </template>
                                    <template x-if="r.deliveryDays == null">
                                        <span class="text-sm text-gray-500">(est. shipping time varies)</span>
                                    </template>
                                </div>
                            </label>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Totals --}}
            <div class="border-t pt-4 space-y-1">
                <div class="flex justify-between">
                    <span>Subtotal</span>
                    <span>$<span x-text="subtotal.toFixed(2)"></span></span>
                </div>
                <div class="flex justify-between">
                    <span>Discount</span>
                    <span>− $<span x-text="discount.toFixed(2)"></span></span>
                </div>
                <div class="flex justify-between">
                    <span>Tax</span>
                    <span>$<span x-text="tax.toFixed(2)"></span></span>
                </div>
                <div class="flex justify-between">
                    <span>Shipping</span>
                    <span>$<span x-text="shippingFee.toFixed(2)"></span></span>
                </div>
                <div class="flex justify-between font-semibold">
                    <span>Total</span>
                    <span>$<span x-text="total.toFixed(2)"></span></span>
                </div>
            </div>

            {{-- Hidden fields for final submit --}}
            <input type="hidden" name="serviceCode" :value="selectedRate?.serviceCode">
            <input type="hidden" name="shipping_fee"  :value="shippingFee">

            {{-- Navigation Buttons --}}
            <div class="flex justify-between mt-6">
                <button
                    type="button"
                    @click="goToStep(1)"
                    class="btn-secondary"
                >
                    ← Back
                </button>
                <button
                    type="button"
                    @click="goToStep(3)"
                    class="btn-primary"
                >
                    Continue to Payment →
                </button>
            </div>
        </div> {{-- end step 2 card --}}

        {{-- STEP 3: Payment --}}
        <div x-show="step===3" class="checkout-card">
            <h3 class="section-heading">3. Payment</h3>

            <!-- Single container for Stripe's Card Element -->
            <div class="form-group">
                <label for="card-element" class="block mb-1">Card Details</label>
                <div id="card-element" class="form-input"><!-- Stripe injects number/expiry/CVC here --></div>
            </div>

            <!-- Show any Stripe error -->
            <p x-text="orderError" class="text-red-600 mt-2"></p>

            <div class="mt-6">
                <button
                    type="button"
                    @click="pay()"
                    :disabled="loading"
                    class="btn-primary btn-pay w-full"
                >
      <span x-text="loading
        ? 'Processing…'
        : `Pay $${total.toFixed(2)}`">
      </span>
                </button>
            </div>

            <div class="mt-4 text-center">
                <button
                    type="button"
                    @click="goToStep(2)"
                    class="btn-secondary"
                >
                    ← Back
                </button>
            </div>
        </div>
    </x-form>

    <script src="https://js.stripe.com/v3/"></script>
@endsection