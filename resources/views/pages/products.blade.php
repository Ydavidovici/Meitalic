@extends('layouts.app')

@section('title', 'Shop Products')

@section('content')
    @php
        use Illuminate\Support\Str;

        $brand    = request('brand');
        $category = request('category');

        // 1) Grab exactly the lines for the chosen brand (or [] if none/mismatch)
        $brandLines = config("brands.{$brand}.lines", []);

        // 2) Decide what goes into the <select name="line">
        if (! empty($brandLines)) {
            // a brand with lines => only those lines
            $lineOptions = $brandLines;
        } elseif (! $brand && $category === 'Skincare') {
            // no brand + category=Skincare => Meitalic's four skincare lines
            $lineOptions = array_filter(
                config('brands.Meitalic.lines', []),
                fn($l) => Str::contains(Str::lower($l), 'line') && ! Str::contains(Str::lower($l), 'full')
            );
        } else {
            // fallback: every distinct line in your DB
            $lineOptions = $allLines->toArray();
        }
    @endphp

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

            {{-- Line dropdown --}}
            <select name="line" class="form-select">
                <option value="">All Lines</option>
                @foreach($lineOptions as $l)
                    <option value="{{ $l }}" @selected(request('line') === $l)>
                        {{ $l }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn-primary h-[2.75rem]">
                Filter
            </button>
        </x-form>

        {{-- 2) Product Grid --}}
        <div class="product-grid">
            @foreach($products as $product)
                <a href="{{ route('products.show', $product->slug) }}" class="product-card group">
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

                        <x-form
                            action="{{ route('cart.add') }}"
                            method="POST"
                            class="product-card__atc-form"
                            @click.stop
                        >
                            <input type="hidden" name="product_id" value="{{ $product->id }}" />
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

            <div class="product-grid__pagination">
                {{ $products->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
