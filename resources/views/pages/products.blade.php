@extends('layouts.app')

@section('title', 'Shop Products')

@section('content')
    <div class="container px-4 sm:px-6 lg:px-8 mt-16">
        <h2 class="text-3xl font-bold mb-8 text-center">Our Products</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
            @foreach ($products as $product)
                <a
                    href="{{ route('products.show', $product->slug) }}"
                    class="block border rounded shadow hover:shadow-lg transition"
                >
                    <img
                        src="{{ asset('storage/' . $product->image) }}"
                        alt="{{ $product->name }}"
                        class="w-full h-64 object-cover rounded-t"
                    >
                    <div class="p-4">
                        <h3 class="text-lg font-semibold">{{ $product->name }}</h3>
                        <p class="text-sm text-gray-600 mt-2">
                            ${{ number_format($product->price, 2) }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endsection
