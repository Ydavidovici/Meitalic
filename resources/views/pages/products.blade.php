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
                    <option value="{{ $b }}" @selected(request('brand') === $b)>
                        {{ $b }}
                    </option>
                @endforeach
            </select>

            <select name="category" class="form-select">
                <option value="">All Categories</option>
                @foreach($allCategories as $c)
                    <option value="{{ $c }}" @selected(request('category') === $c)>
                        {{ $c }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn-primary h-[2.75rem]">
                Filter
            </button>
        </x-form>

        {{-- Skincare Lines (only when category=Skincare) --}}
        @if(request('category') === 'Skincare')
            <section class="skincare-lines mb-12">
                <h3 class="section-subtitle text-xl font-semibold mb-4">
                    Shop Skincare Lines
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach([
                      'Brightening Line' => 'Brightening Line',
                      'Acne Line'        => 'Acne Line',
                      'Rosacea Line'     => 'Rosacea Line',
                      'Makeup Line'      => 'Makeup Line',
                    ] as $label => $line)
                        <a
                            href="{{ route('products.index', array_merge(request()->only(['search','brand','category']), ['line' => $line])) }}"
                            class="line-card border p-3 text-center hover:shadow-md transition"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Brand Lines (when brand=Meitalic, Repechage or Melaleuca) --}}
        @php
            $brandLines = [];
            if(request('brand') === 'Meitalic') {
                $brandLines = [
                  'Brightening Line' => 'Brightening Line',
                  'Acne Line'        => 'Acne Line',
                  'Rosacea Line'     => 'Rosacea Line',
                  'Makeup Line'      => 'Makeup Line',
                ];
            } elseif(request('brand') === 'Repechage') {
                $brandLines = [
                  'Hydra Medic' => 'Hydra Medic',
                  'Biolight'    => 'Biolight',
                  'Vita Cura'   => 'Vita Cura',
                  'Hydra 4'     => 'Hydra 4',
                ];
            } elseif(request('brand') === 'Melaleuca') {
                $brandLines = [
                  'Renew' => 'Renew',
                ];
            }
        @endphp

        @if(count($brandLines))
            <section class="brand-lines mb-12">
                <h3 class="section-subtitle text-xl font-semibold mb-4">
                    Shop {{ request('brand') }} Lines
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach($brandLines as $label => $line)
                        <a
                            href="{{ route('products.index', array_merge(request()->only(['search','brand','category']), ['line' => $line])) }}"
                            class="line-card border p-3 text-center hover:shadow-md transition"
                        >
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
                            ${{ number_format($product->price, 2) }}
                        </p>

                        {{-- Add-to-cart form --}}
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
                                class="product-card__qty-input"
                            />

                            <button type="submit" class="product-card__atc-btn btn-primary">
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
