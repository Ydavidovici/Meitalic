@extends('layouts.app')

@section('title', 'Shop Products')

@section('content')
    <div class="products-container max-w-screen-lg mx-auto px-6 sm:px-8 lg:px-12 py-16">

        {{-- Page Heading --}}
        <h2 class="products-heading text-3xl font-bold mb-8 text-center">
            Our Products
        </h2>

        {{-- 1) Filter / Search Form --}}
        <x-form
            method="GET"
            action="{{ route('products.index') }}"
            class="filter-form max-w-4xl mx-auto mb-6 flex flex-wrap gap-4 items-center"
        >
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search products…"
                class="form-input flex-1 min-w-[12rem]"
            />

            <select name="brand" class="form-select">
                <option value="">All Brands</option>
                @foreach($allBrands as $b)
                    <option value="{{ $b }}" @selected(request('brand')=== $b)>
                        {{ $b }}
                    </option>
                @endforeach
            </select>


            <select name="category" class="form-select">
                <option value="">All Categories</option>
                @foreach($allCategories as $c)
                    <option value="{{ $c }}" @selected(request('category')=== $c)>
                        {{ $c }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn-primary h-[2.75rem]">
                Filter
            </button>
        </x-form>

        {{-- **NEW**: if user clicked “Skincare” as category, show lines --}}
        @if(request('category') === 'Skincare')
            <section class="skincare-lines mb-12">
                <h3 class="section-subtitle text-xl font-semibold mb-4">Shop Skincare Lines</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach([
                      'Brightening Line' => 'Brightening Line',
                      'Acne Line'        => 'Acne Line',
                      'Rosacea Line'     => 'Rosacea Line',
                      'Makeup Line'      => 'Makeup Line',
                    ] as $label => $line)
                        <a href="{{ route('products.index', ['category'=>'Skincare','line'=>$line]) }}"
                           class="line-card border p-3 text-center hover:shadow-md transition">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endif



        {{-- 2) Product Grid --}}
        <div class="product-grid">
            @foreach($products as $product)
                <a
                    href="{{ route('products.show', $product->slug) }}"
                    class="product-card group"
                >
                    <div class="product-card__image-wrapper">
                        <img
                            src="{{ $product->image_url }}"
                            alt="{{ $product->name }}"
                            class="product-card__image"
                        />
                    </div>
                    <div class="product-card__body">
                        <h3 class="product-card__title group-hover:text-accent">
                            {{ $product->name }}
                        </h3>
                        <p class="product-card__price">
                            ${{ number_format($product->price,2) }}
                        </p>

                        {{-- Add‑to‑cart form --}}
                        <x-form
                            action="{{ route('cart.add') }}"
                            method="POST"
                            class="product-card__atc-form"
                            @click.stop
                        >
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
                                class="form-input w-16"
                            />

                            <button type="submit" class="btn-primary product-card__atc-btn">
                                Add to Cart
                            </button>
                        </x-form>
                    </div>
                </a>
            @endforeach

            {{-- Pagination --}}
            <div class="product-grid__pagination">
                {{ $products->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
