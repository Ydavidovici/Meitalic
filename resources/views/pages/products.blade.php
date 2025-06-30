@extends('layouts.app')

@section('title', 'Shop Products')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
    @php
        $brand    = request('brand');
        $category = request('category');

        // 1) Grab exactly the lines for the chosen brand (or [] if none/mismatch)
        $brandLines = config("brands.{$brand}.lines", []);

        // 2) Decide what goes into the <select name="line">
        if (! empty($brandLines)) {
            $lineOptions = $brandLines;
        } elseif (! $brand && $category === 'Skincare') {
            $lineOptions = array_filter(
                config('brands.Meitalic.lines', []),
                fn($l) =>
                    Str::contains(Str::lower($l), 'line') &&
                    ! Str::contains(Str::lower($l), 'full')
            );
        } else {
            $lineOptions = $allLines->toArray();
        }
    @endphp

    <div class="products-container max-w-screen-lg mx-auto px-6 sm:px-8 lg:px-12 py-16">
        {{-- Page Heading --}}
        <h2 class="text-3xl font-bold mb-8 text-center">Our Products</h2>

        <x-form
            method="GET"
            action="{{ route('products.index') }}"
            class="filter-form max-w-xl mx-auto mb-6 flex flex-col space-y-4"
        >
            {{-- SEARCH ROW --}}
            <div class="w-full">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search products…"
                    class="filter-input w-full"
                />
            </div>

            {{-- FILTERS ROW --}}
            <div class="filter-controls grid grid-cols-1 sm:grid-cols-4 gap-4">
                <select name="brand" class="filter-select">
                    <option value="">All Brands</option>
                    @foreach($allBrands as $b)
                        <option value="{{ $b }}" @selected(request('brand') === $b)>{{ $b }}</option>
                    @endforeach
                </select>

                <select name="category" class="filter-select">
                    <option value="">All Categories</option>
                    @foreach($allCategories as $c)
                        <option value="{{ $c }}" @selected(request('category') === $c)>{{ $c }}</option>
                    @endforeach
                </select>

                <select name="line" class="filter-select">
                    <option value="">All Lines</option>
                    @foreach($lineOptions as $l)
                        <option value="{{ $l }}" @selected(request('line') === $l)>{{ $l }}</option>
                    @endforeach
                </select>

                <button type="submit" class="btn-primary h-11 px-6">Filter</button>
            </div>
        </x-form>

        {{-- Product Grid --}}
        <div class="product-grid">
            @foreach($products as $product)
                @php
                    // build thumbnail URL
                    $first = $product->images->first();
                    if ($first && $first->path) {
                        $p = $first->path;
                        if (Str::startsWith($p, ['http://','https://'])) {
                            $thumb = $p;
                        } elseif (Str::startsWith($p, 'public/')) {
                            $thumb = asset(Str::after($p, 'public/'));
                        } else {
                            $thumb = asset('storage/'.$p);
                        }
                    } else {
                        $thumb = '';
                    }
                @endphp

                <div class="product-card group flex flex-col shadow hover:shadow-lg">
                    {{-- clickable header --}}
                    <a href="{{ route('products.show', $product->slug) }}" class="block">
                        <div class="product-card__image-wrapper">
                            <img
                                src="{{ $thumb }}"
                                alt="{{ $product->name }}"
                                class="product-card__image rounded-t-lg"
                            />
                        </div>
                        <div class="px-4 py-3">
                            <h3 class="product-card__title group-hover:text-accent">
                                {{ $product->name }}
                            </h3>
                            <p class="product-card__price">
                                ${{ number_format($product->price, 2) }}
                            </p>
                        </div>
                    </a>

                    <div class="product-card__atc-form">
                        <a href="{{ route('products.show', $product->slug) }}" class="grab-btn">
                            Grab It Now
                        </a>
                    </div>

                </div>
            @endforeach

            <div class="product-grid__pagination">
                {{ $products->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
