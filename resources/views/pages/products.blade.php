{{-- resources/views/pages/products.blade.php --}}
@extends('layouts.app')

@section('title', 'Shop Products')

@section('content')
    <div class="products-container">
        <h2 class="products-heading">Our Products</h2>

        {{-- Filter / Search Form --}}
        <form
            method="GET"
            action="{{ route('products.index') }}"
            class="filter-form"
        >
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search products…"
                class="filter-input"
            />

            <select name="brand" class="filter-select">
                <option value="">All Brands</option>
                @foreach($allBrands as $b)
                    <option value="{{ $b }}" @selected(request('brand') === $b)>
                        {{ $b }}
                    </option>
                @endforeach
            </select>

            <select name="category" class="filter-select">
                <option value="">All Categories</option>
                @foreach($allCategories as $c)
                    <option value="{{ $c }}" @selected(request('category') === $c)>
                        {{ $c }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn-primary">
                Filter
            </button>
        </form>

        {{-- Product Grid --}}
        <div class="product-grid">
            @foreach ($products as $product)
                <div class="product-card">
                    @if($product->image_url)
                        <img
                            src="{{ $product->image_url }}"
                            alt="{{ $product->name }}"
                            class="product-image"
                        >
                    @else
                        <div class="product-image">
                            No Image
                        </div>
                    @endif

                    <div class="product-details">
                        <h3 class="product-title">{{ $product->name }}</h3>
                        <p class="product-price">
                            ${{ number_format($product->price, 2) }}
                        </p>

                        {{-- Add to Cart --}}
                        <form
                            action="{{ route('cart.add') }}"
                            method="POST"
                            class="add-to-cart-form"
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
                                class="add-to-cart-input"
                            />
                            <button
                                type="submit"
                                class="btn-primary flex-1 text-center"
                            >
                                Add to Cart
                            </button>
                        </form>

                        {{-- View Details Link --}}
                        <a
                            href="{{ route('products.show', $product->slug) }}"
                            class="product-link"
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
