@extends('layouts.app')

@section('title', 'Your Cart')

@section('content')
    <div class="max-w-4xl mx-auto py-12 px-4">
        <h1 class="text-3xl font-semibold mb-6">Your Cart</h1>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @elseif(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                {{ session('error') }}
            </div>
        @endif

        @if(count($items) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full text-left mb-6 border-collapse">
                    <thead>
                    <tr class="border-b">
                        <th class="py-2">Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $item)
                        <tr class="border-b">
                            <td class="py-2">{{ $item->product->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>${{ number_format($item->price, 2) }}</td>
                            <td>${{ number_format($item->total, 2) }}</td>
                            <td>
                                <x-form
                                    method="DELETE"
                                    action="{{ route('cart.remove', $item->id) }}"
                                    class="inline"
                                >
                                    <button class="text-red-600 hover:underline">Remove</button>
                                </x-form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col items-end space-y-4">
                {{-- Promo Code Form --}}
                <x-form
                    method="POST"
                    action="{{ route('cart.applyPromo') }}"
                    class="flex items-center gap-2"
                >
                    <input
                        type="text"
                        name="code"
                        placeholder="Promo code"
                        value="{{ session('applied_promo') ?? '' }}"
                        class="form-input"
                    />
                    <button type="submit" class="btn-primary">
                        Apply
                    </button>
                </x-form>

                {{-- Total --}}
                <div class="text-xl font-semibold">
                    Total: ${{ number_format($total, 2) }}
                </div>

                {{-- Checkout --}}
                <x-form
                    method="POST"
                    action="{{ route('checkout.create') }}"
                >
                    <button type="submit" class="btn-primary px-6 py-3">
                        Checkout with Stripe
                    </button>
                </x-form>
            </div>
        @else
            <div class="text-gray-600 text-center mt-10">
                <p>Your cart is currently empty.</p>
                <a href="{{ route('home') }}" class="text-blue-600 hover:underline mt-2 inline-block">
                    Continue Shopping
                </a>
            </div>
        @endif
    </div>
@endsection
