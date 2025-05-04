@extends('layouts.app')
@section('title','Checkout')

@section('content')
    <div x-data="checkoutPage()" x-init="init()" class="container mx-auto py-12 px-4 sm:px-6 lg:px-8 space-y-8">

        {{-- Cart Summary --}}
        <div class="bg-white rounded shadow p-6">
            <h2 class="text-2xl font-bold mb-4">Your Cart</h2>

            <template x-if="!items.length">
                <div class="bg-red-100 text-red-800 p-4 rounded mb-4">Your cart is empty.</div>
            </template>

            <template x-for="item in items" :key="item.id">
                <div class="flex justify-between py-2 border-b">
                    <div>
                        <div class="font-medium" x-text="item.product.name"></div>
                        <div class="text-sm text-gray-600">
                            Qty: <span x-text="item.quantity"></span>
                        </div>
                    </div>
                    <div>
                        $<span x-text="(item.price * item.quantity).toFixed(2)"></span>
                    </div>
                    <button @click="remove(item.id)" class="text-red-600">Remove</button>
                </div>
            </template>

            <div x-show="items.length" class="mt-4 text-right space-y-1">
                <div>Subtotal: $<span x-text="subtotal.toFixed(2)"></span></div>
                <div x-show="discount>0">Discount: −$<span x-text="discount.toFixed(2)"></span></div>
                <div>Tax: $<span x-text="tax.toFixed(2)"></span></div>
                <div class="font-bold">Total: $<span x-text="total.toFixed(2)"></span></div>
            </div>
        </div>

        {{-- Promo Code --}}
        <div x-show="items.length" class="bg-white rounded shadow p-6">
            <h3 class="font-semibold mb-2">Have a promo code?</h3>
            <div class="flex space-x-2">
                <input x-model="promoCode"
                       type="text"
                       placeholder="Enter code"
                       class="border rounded px-3 py-2 flex-1">
                <button @click="applyPromo()"
                        :disabled="loading"
                        class="btn-primary">
                    Apply
                </button>
            </div>
            <div class="mt-2 text-red-500" x-text="promoError"></div>
        </div>

        {{-- Shipping & Payment Form --}}
        <form x-show="items.length" @submit.prevent="placeOrder()" class="bg-white rounded shadow p-6 space-y-4">
            <h3 class="text-xl font-bold">Shipping & Contact</h3>

            <div>
                <label class="block font-medium">Address</label>
                <textarea x-model="form.shipping_address" required
                          class="border rounded p-2 w-full"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium">Email</label>
                    <input x-model="form.email" type="email" required
                           class="border rounded px-3 py-2 w-full">
                </div>
                <div>
                    <label class="block font-medium">Phone</label>
                    <input x-model="form.phone" type="text"
                           class="border rounded px-3 py-2 w-full">
                </div>
            </div>

            <div class="text-right">
                <button type="submit"
                        :disabled="loading"
                        class="btn-primary">
                    <span x-text="loading ? 'Processing…' : 'Proceed to Payment'"></span>
                </button>
            </div>
            <div class="text-red-500" x-text="orderError"></div>
        </form>
    </div>
@endsection
