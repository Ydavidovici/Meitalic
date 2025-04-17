@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div class="max-w-5xl mx-auto mt-16 grid grid-cols-1 md:grid-cols-2 gap-10">
    <div>
        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full rounded shadow">
    </div>
    <div>
        <h2 class="text-4xl font-bold mb-4">{{ $product->name }}</h2>
        <p class="text-xl text-pink-600 mb-6">${{ number_format($product->price, 2) }}</p>

        <p class="text-gray-700 mb-6">{{ $product->description }}</p>

        <form method="POST" action="{{ route('cart.add') }}">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <div class="flex items-center mb-4">
                <label for="quantity" class="mr-2 text-sm font-medium">Quantity:</label>
                <input type="number" name="quantity" id="quantity" value="1" min="1" class="w-20 p-2 border rounded">
            </div>
            <button type="submit" class="bg-black text-white px-6 py-2 rounded hover:bg-gray-800">
                Add to Cart
            </button>
        </form>
    </div>
</div>
@endsection
