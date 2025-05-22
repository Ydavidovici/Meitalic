@extends('layouts.app')

@section('title','Meitalic Cosmeceuticals')

@push('styles')
    @vite('resources/css/pages/home.css')
@endpush

@section('content')
    <!-- Hero -->
    {{--
<section class="hero">
    <div class="hero__inner">
        <div class="hero__content">
            <h1 class="hero__title">Meitalic</h1>
            <p class="hero__subtitle">Where elegance meets skincare.</p>
            <a href="{{ route('products.index') }}" class="hero__cta">Shop Now</a>
        </div>

        <figure class="hero__figure">
            <img
                src="{{ asset('images/hero-photo.png') }}"
                alt="Elegant woman"
                class="hero__image"
            />
            <figcaption class="sr-only">
                Woman with glowing skin in a soft-lit portrait.
            </figcaption>
        </figure>
    </div>
</section>
<div class="hero__inner">
            <div class="hero__content">
                <h1 class="hero__title">Meitalic</h1>
                <p class="hero__subtitle">Where elegance meets skincare.</p>
                <a href="{{ route('products.index') }}" class="hero__cta">Shop Now</a>
            </div>

            <figure class="hero__figure">
                <img
                    src="{{ asset('images/hero-photo.png') }}"
                    alt="Elegant woman"
                    class="hero__image"
                />
                <figcaption class="sr-only">
                    Woman with glowing skin in a soft-lit portrait.
                </figcaption>
            </figure>
        </div>
--}}

    <section class="hero">
        <div class="hero__banner">
            <img
                src="{{ asset('images/banner1.png') }}"
                alt="Client Preferred Banner"
                class="hero__banner-img"
            />
        </div>
    </section>


    <!-- Featured Products -->
    @if($featuredProducts->count())
        <section class="featured">
            <div class="featured__inner">
                <h2 class="featured__title">Featured Products</h2>
                <div class="featured__grid">
                    @foreach($featuredProducts as $product)
                        <div class="featured__card">
                            <a href="{{ route('products.show', $product->slug) }}">
                                @if($product->image)
                                    <img
                                        src="{{ $product->image_url }}"
                                        alt="{{ $product->name }}"
                                        class="featured__img"
                                    />
                                @endif
                                <div class="featured__body">
                                    <h3 class="featured__name">{{ $product->name }}</h3>
                                    <p class="featured__price">${{ number_format($product->price,2) }}</p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Shop by Brand -->
    <section class="shop-by-brand py-16">
        <div class="container mx-auto px-6">
            <h2 class="section-title text-2xl font-bold mb-6 text-center">Shop by Brand</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                @foreach($allBrands as $brand)
                    <a href="{{ route('products.index', ['brand' => $brand]) }}"
                       class="brand-card border p-4 text-center hover:shadow-lg transition">
                        {{ $brand }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Shop by Category -->
    <section class="shop-by-category py-16">
        <div class="container mx-auto px-6">
            <h2 class="section-title text-2xl font-bold mb-6 text-center">Shop by Category</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                @foreach([
                  'Skincare'     => 'Skincare',
                  'Makeup'       => 'Makeup',
                  'Starter Kits' => 'Starter Kits',
                  'Accessories'  => 'Accessories',
                ] as $label => $cat)
                    <a href="{{ route('products.index', ['category' => $cat]) }}"
                       class="category-card border p-4 text-center hover:shadow-lg transition">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- About -->
    <section id="about" class="about">
        <div class="about__inner">
            <!-- Main title & quote -->
            <h2 class="section-title">About the Brand</h2>
            <blockquote class="about__quote mb-12">
                “A short and powerful quote to inspire customers.”
            </blockquote>

            <!-- Our Philosophy -->
            <div class="about__section about__philosophy">
                <h2 class="section-subtitle">Our Philosophy</h2>
                <div class="philosophy__card">
                    <p class="philosophy__text">
                        We believe in clean, effective skincare powered by nature and science.
                        Our products feature natural botanicals, hydrating formulas,
                        cruelty‑free practices & dermatologist‑tested safety—
                        all rigorously blended to bring out your best skin.
                    </p>
                </div>
            </div>

            <!-- Our Mission -->
            <div class="about__section about__mission">
                <h2 class="section-subtitle">Our Mission</h2>
                <div class="mission__card">
                    <p class="mission__text">
                        From lab to bottle, every formulation is meticulously developed
                        and tested for maximum efficacy and safety—so you can trust
                        what you put on your skin.
                    </p>
                </div>
            </div>

    </section>




    <!-- Reviews -->
    <section class="reviews">
        <div class="reviews__inner">
            <h2 class="section-title">Customer Reviews</h2>

            <div class="reviews__grid">
                {{-- Ideally loop through your actual reviews; here are two examples: --}}
                <div class="reviews__card">
                    <p class="reviews__text">
                        “A customer testimonial... no one ever raving any orates.”
                    </p>
                    <div class="reviews__stars">★★★★★</div>
                </div>
                <div class="reviews__card">
                    <p class="reviews__text">
                        “Another glowing review praising the quality and results!”
                    </p>
                    <div class="reviews__stars">★★★★★</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="newsletter">
        <div class="newsletter__inner">
            <h2 class="section-title">Join Our Newsletter</h2>

            <div class="newsletter__card">
                <p class="newsletter__text mb-4">Get 10% off your first order</p>
                <x-form
                    method="POST"
                    action="{{ route('newsletter.subscribe') }}"
                    class="newsletter__form space-y-4"
                >
                    <input
                        type="email"
                        name="email"
                        placeholder="Email address"
                        required
                        class="form-input newsletter__input"
                    />
                    <button type="submit" class="btn-primary newsletter__btn">
                        Subscribe
                    </button>
                </x-form>
            </div>
        </div>
    </section>

@endsection
