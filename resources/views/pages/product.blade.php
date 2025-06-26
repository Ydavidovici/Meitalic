@extends('layouts.app')
@section('title', $product->name)

@section('content')
    <div class="product-container flex flex-col md:flex-row gap-8">

        {{-- IMAGE CAROUSEL --}}
        <div
            x-data='{
        current: 0,
        images: {!! json_encode($images) !!},
        prev() { this.current = (this.current - 1 + this.images.length) % this.images.length },
        next() { this.current = (this.current + 1) % this.images.length }
      }'
            class="relative w-full md:w-1/2"
        >
            {{-- left arrow --}}
            <button
                x-show="images.length > 1"
                @click="prev()"
                class="absolute top-1/2 left-2 transform -translate-y-1/2 bg-white bg-opacity-75 rounded-full p-2 shadow hover:bg-opacity-100"
            >‹</button>

            {{-- the image --}}
            <img
                :src="
          images[current].startsWith('http')
            ? images[current]
            : '{{ asset('storage') }}/' + images[current]
        "
                alt="{{ $product->name }}"
                class="w-full h-auto object-cover rounded"
            >

            {{-- right arrow --}}
            <button
                x-show="images.length > 1"
                @click="next()"
                class="absolute top-1/2 right-2 transform -translate-y-1/2 bg-white bg-opacity-75 rounded-full p-2 shadow hover:bg-opacity-100"
            >›</button>
        </div>

        {{-- DETAILS COLUMN --}}
        <div class="product-details w-full md:w-1/2">
            <h1 class="product-title text-3xl font-bold mb-2">{{ $product->name }}</h1>
            <p class="product-price text-2xl text-gray-700 mb-4">
                ${{ number_format($product->price,2) }}
            </p>
            <p class="product-description mb-6">{{ $product->description }}</p>

            {{-- ADD TO CART --}}
            <div x-data="{ qty: 1 }" class="add-to-cart space-y-4">
                <div class="flex items-center space-x-2">
                    <button @click="qty = Math.max(1, qty - 1)" class="px-3 py-1 bg-gray-200 rounded">–</button>
                    <span x-text="qty" class="w-8 text-center"></span>
                    <button @click="qty++" class="px-3 py-1 bg-gray-200 rounded">+</button>
                </div>
                <x-form method="POST" action="{{ route('cart.add') }}">
                    <input type="hidden" name="product_id" :value="{{ $product->id }}">
                    <input type="hidden" name="quantity"    :value="qty">
                    <button type="submit" class="btn-primary">Add to Cart</button>
                </x-form>
            </div>
        </div>

    </div>
@endsection
