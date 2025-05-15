@extends('layouts.app')

@section('title', 'Your Cart')

@section('content')
    <div class="cart-page">
        <h1 class="cart-title">Your Cart</h1>

        @if(session('success'))
            <div class="notification notification--success">
                {{ session('success') }}
            </div>
        @elseif(session('error'))
            <div class="notification notification--error">
                {{ session('error') }}
            </div>
        @endif

        @if(count($items) > 0)
            <div class="cart-table-wrapper">
                <table class="cart-table">
                    <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $item->product->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>${{ number_format($item->price, 2) }}</td>
                            <td>${{ number_format($item->total, 2) }}</td>
                            <td>
                                <x-form
                                    method="DELETE"
                                    action="{{ route('cart.remove', $item->id) }}"
                                    class="inline"
                                >
                                    <button class="cart-table__remove-btn">Remove</button>
                                </x-form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="cart-footer">
                {{-- Promo Code --}}
                <x-form
                    method="POST"
                    action="{{ route('cart.applyPromo') }}"
                    class="promo-form"
                >
                    <input
                        type="text"
                        name="code"
                        placeholder="Promo code"
                        value="{{ session('applied_promo') ?? '' }}"
                        class="form-input"
                    />
                    <button type="submit" class="btn-primary">Apply</button>
                </x-form>

                {{-- Total --}}
                <div class="cart-total">
                    Total: ${{ number_format($total, 2) }}
                </div>

                {{-- Checkout --}}
                <x-form method="POST" action="{{ route('checkout.create') }}">
                    <button type="submit" class="checkout-btn">
                        Checkout with Stripe
                    </button>
                </x-form>
            </div>

        @else
            <div class="cart-empty">
                <p>Your cart is currently empty.</p>
                <a href="{{ route('home') }}" class="cart-empty__link">
                    Continue Shopping
                </a>
            </div>
        @endif
    </div>
@endsection
