{{-- resources/views/pages/products.blade.php --}}
@extends('layouts.app')

@section('title', 'Shop Products')

@section('content')
    <div class="container px-4 sm:px-6 lg:px-8 mt-16">
        <h2 class="text-3xl font-bold mb-8 text-center">Our Products</h2>

        {{-- Filter / Search Form --}}
        <form
            method="GET"
            action="{{ route('products.index') }}"
            class="mb-6 flex flex-wrap gap-4 items-center"
        >
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search products…"
                class="border rounded px-3 py-2 flex-1"
            />

            <select name="brand" class="border rounded px-3 py-2">
                <option value="">All Brands</option>
                @foreach($allBrands as $b)
                    <option value="{{ $b }}" @selected(request('brand') === $b)>
                        {{ $b }}
                    </option>
                @endforeach
            </select>

            <select name="category" class="border rounded px-3 py-2">
                <option value="">All Categories</option>
                @foreach($allCategories as $c)
                    <option value="{{ $c }}" @selected(request('category') === $c)>
                        {{ $c }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn-primary whitespace-nowrap">
                Filter
            </button>
        </form>

        {{-- Product Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
            @foreach ($products as $product)
                <div class="border rounded shadow hover:shadow-lg transition flex flex-col">
                    @if($product->image_url)
                        <img
                            src="{{ $product->image_url }}"
                            alt="{{ $product->name }}"
                            class="w-full h-64 object-cover rounded-t"
                        >
                    @else
                        <div
                            class="w-full h-64 bg-gray-100 flex items-center justify-center text-gray-400 rounded-t"
                        >
                            No Image
                        </div>
                    @endif

                    <div class="p-4 flex-1 flex flex-col">
                        <h3 class="text-lg font-semibold">{{ $product->name }}</h3>
                        <p class="text-sm text-gray-600 mt-2">
                            ${{ number_format($product->price, 2) }}
                        </p>

                        {{-- Add to Cart --}}
                        <form
                            action="{{ route('cart.add') }}"
                            method="POST"
                            class="mt-4 flex items-center gap-2"
                        >
                            @csrf
                            <input
                                type="hidden"
                                name="product_id"
                                value="{{ $product->id }}"
                            />
                            <input
                                type="number"
                                name="quantity"
                                value="1"
                                min="1"
                                class="w-16 border rounded px-2 py-1"
                            />
                            <button
                                type="submit"
                                class="btn-primary px-4 py-2 flex-1 text-center"
                            >
                                Add to Cart
                            </button>
                        </form>

                        {{-- View Details Link --}}
                        <a
                            href="{{ route('products.show', $product->slug) }}"
                            class="mt-4 text-center text-indigo-600 hover:underline"
                        >
                            View Details →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $products->links() }}
        </div>
    </div>
@endsection
