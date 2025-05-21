@extends('layouts.app')

@section('title', 'Your Cart')

@section('content')
    <div class="cart-page">
        <div class="cart-card">  {{-- new wrapper --}}
            <h1 class="cart-title">Your Cart</h1>

            @if($items->isEmpty())
                <p class="cart-empty">
                    Your cart is empty.
                    <a href="{{ route('home') }}" class="cart-empty__link">Continue Shopping</a>
                </p>
            @else
                <div class="cart-table-wrapper">
                    <table class="cart-table">
                        <thead>
                        <tr class="cart-table__header-row">
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
                                <td class="text-right">{{ $item->quantity }}</td>
                                <td class="text-right">${{ number_format($item->price,2) }}</td>
                                <td class="text-right">${{ number_format($item->total,2) }}</td>
                                <td class="text-center">
                                    <x-form method="DELETE" action="{{ route('cart.remove',$item->id) }}">
                                        <button type="submit" class="cart-remove-btn">Remove</button>
                                    </x-form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="{{ route('cart.applyPromo') }}" class="cart-promo">
                    @csrf
                    <input name="code" placeholder="Promo code" class="form-input" />
                    <button type="submit" class="btn-secondary">Apply</button>
                </form>

                <div class="cart-footer">
                    <div class="cart-summary">
                        <div class="cart-summary-row">
                            <span class="cart-summary-label">Subtotal:</span>
                            <span class="cart-summary-value">${{ number_format($subtotal,2) }}</span>
                        </div>

                        @if($discount > 0)
                            <div class="cart-summary-row">
                                <span class="cart-summary-label">Discount:</span>
                                <span class="cart-summary-value">−${{ number_format($discount,2) }}</span>
                            </div>
                        @endif

                        <div class="cart-summary-row">
                            <span class="cart-summary-label">Tax:</span>
                            <span class="cart-summary-value">${{ number_format($tax,2) }}</span>
                        </div>

                        <div class="cart-total-row">
                            <span class="cart-total-label">Total:</span>
                            <span class="cart-total-value">${{ number_format($total,2) }}</span>
                        </div>
                    </div>

                    <a href="{{ route('checkout') }}" class="btn-primary cart-checkout-btn">
                        Proceed to Checkout
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
