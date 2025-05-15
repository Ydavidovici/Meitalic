@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <div class="product-container">
        {{-- IMAGE COLUMN --}}
        <div class="product-image-wrapper">
            <img
                src="{{ $product->image_url }}"
                alt="{{ $product->name }}"
                class="product-image"
            />
        </div>

        {{-- DETAILS COLUMN --}}
        <div class="product-details">
            <h1 class="product-title">{{ $product->name }}</h1>
            <p class="product-price">${{ number_format($product->price,2) }}</p>
            <p class="product-description">{{ $product->description }}</p>

            {{-- INLINE STEPPER + ADD TO CART --}}
            <div x-data="{ qty: 1 }" class="add-to-cart">
                <div class="quantity-controls">
                    <button
                        type="button"
                        @click="qty = Math.max(1, qty-1)"
                        class="quantity-btn"
                    >–</button>
                    <span x-text="qty" class="quantity-value"></span>
                    <button
                        type="button"
                        @click="qty++"
                        class="quantity-btn"
                    >+</button>
                </div>

                <!-- refactored form -->
                <x-form
                    method="POST"
                    action="{{ route('cart.add') }}"
                    class="mt-4"
                >
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity"    :value="qty">

                    <button type="submit" class="btn-primary">
                        Add to Cart
                    </button>
                </x-form>
            </div>
        </div>
    </div>
@endsection
