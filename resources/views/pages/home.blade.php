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
                “Beauty is being comfortable and confident in your own skin”
            </blockquote>

            <!-- Our Philosophy -->
            <div class="about__section about__philosophy">
                <h2 class="section-subtitle">Our Philosophy</h2>
                <div class="philosophy__card">
                    <p class="philosophy__text">
                        Meitalic theory is a clean environment, effective skincare powered by nature and science.
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
                        As a family owned business, from lab to bottle, every formulation is meticulously developed
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
                        “Highly Recommend! So natural and full!”
                    </p>
                    <div class="reviews__stars">★★★★★</div>
                </div>
                <div class="reviews__card">
                    <p class="reviews__text">
                        “I just wanted to say a huge thank you for the perfect purchase.
                        Such a light, airy makeup—in 10 seconds I feel like a million dollars
                        😍🥰😍🥰. The facial cleanser is absolutely perfect, wow, and the face
                        cream I put on in the morning—what a combo! I seriously feel like I
                        have brand-new skin!!!!!”
                    </p>
                    <div class="reviews__stars">★★★★★</div>
                </div>
            </div>
        </div>
    </section>


    <section
        class="newsletter"
        x-data="{ open: false }"
        x-init="setTimeout(() => open = true, 500)"   {{-- fire after 0.5s --}}
        @keydown.window.escape="open = false"
    >
        <div class="newsletter__inner text-center">
            <h2 class="section-title">Join Our Newsletter</h2>
            <button @click="open = true" class="btn-primary newsletter__btn">
                Sign Up
            </button>
        </div>

        <!-- Modal -->
        <template x-if="open">
            <div
                class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
            >
                <div
                    class="bg-white rounded-2xl shadow-lg p-6 max-w-md mx-4 transform"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-90"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-90"
                >
                    <button @click="open = false" class="text-gray-500 hover:text-gray-700 float-right text-2xl leading-none">
                        &times;
                    </button>

                    <h3 class="text-2xl font-semibold mb-4">Subscribe</h3>
                    <p class="text-neutral-600 mb-4">Get 10% off your first order ✨</p>

                    <x-form method="POST" action="{{ route('newsletter.subscribe') }}" class="space-y-4">
                        <input
                            type="email"
                            name="email"
                            placeholder="Your email address"
                            required
                            class="w-full px-6 py-3 border rounded focus:outline-none focus:ring-2 focus:ring-accent"
                        />
                        <button type="submit" class="btn-primary w-full">Subscribe</button>
                    </x-form>
                </div>
            </div>
        </template>
    </section>


@endsection
