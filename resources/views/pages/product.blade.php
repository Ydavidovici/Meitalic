@extends('layouts.app')

@section('title', $product->name)

@section('content')

    <div class="product-container">
        @php use Illuminate\Support\Str; @endphp

        <img
            src="{{ Str::startsWith($product->image, ['http://','https://'])
          ? $product->image
          : asset('storage/'.$product->image) }}"
            alt="{{ $product->name }}"
            class="product-image"
        >

        <div>
            <h2 class="product-title">{{ $product->name }}</h2>
            <p class="product-price">${{ number_format($product->price, 2) }}</p>

            <p class="product-description">{{ $product->description }}</p>

            <form method="POST" action="{{ route('cart.add') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                <div class="form-group">
                    <label for="quantity" class="form-label">Quantity:</label>
                    <input
                        type="number"
                        name="quantity"
                        id="quantity"
                        value="1"
                        min="1"
                        class="quantity-input"
                    >
                </div>

                <button type="submit" class="btn-primary">
                    Add to Cart
                </button>
            </form>
        </div>
    </div>
    @vite('resources/js/shop.js')
@endsection
