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
                alt="Beauty is being comfortable and confident in your own skin"
                class="hero__banner-img"
            />
        </div>
    </section>


    <!-- Featured Products -->
    <section class="featured py-16">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl  mb-12 text-center">Bestsellers</h2>

            {{-- Product 1 (image left) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center mb-16">
                <!-- image in col 1 -->
                <div class="md:col-start-1">
                    <a href="{{ route('products.index', ['search' => 'Exfoliating Scrub']) }}">
                        <img
                            src="{{ asset('images/exfoliating-scrub.jpeg') }}"
                            alt="Exfoliating Scrub"
                            class="w-full rounded-lg shadow-md"
                        />
                    </a>
                </div>
                <!-- text in col 2 -->
                <div class="md:col-start-2">
                    <h3 class="text-2xl font-semibold mb-2">Exfoliating Scrub</h3>
                    <h4 class="text-xl text-gray-600 mb-4">Our patented honey-almond polish</h4>
                    <p class="text-base leading-relaxed">
                        Reveal your softest skin yet with our gentle, ultra-fine scrub. A blend of pure
                        honey, crushed almond meal, and nourishing botanicals buffs away dull surface cells
                        and unclogs pores without irritation. As you massage, the natural humectant
                        properties of honey lock in moisture, leaving your complexion silky-smooth, radiant,
                        and perfectly prepped for serums or makeup. Use once or twice weekly for a polished
                        glow and renewed clarity.                    </p>
                </div>
            </div>

            {{-- Product 2 (image right) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center mb-16">
                <!-- text goes in col 1 -->
                <div class="md:col-start-1">
                    <h3 class="text-2xl font-semibold mb-2">Glycolic Moisturizer</h3>
                    <h4 class="text-xl text-gray-600 mb-4">Exfoliating Hydration Booster</h4>
                    <p class="text-base leading-relaxed">
                        Give skin a second lease on life with our silky lotion that marries 5%
                        glycolic acid and hyaluronic acid for a double-duty glow. It smooths fine lines,
                        refines texture, and locks in moisture without heaviness, leaving you with a plump,
                        even-tone finish. Ideal as your overnight hero—wake up to fresh, dewy skin primed
                        for makeup or bare-faced confidence.
                    </p>
                </div>
                <!-- image forced into col 2 -->
                <div class="md:col-start-2">
                    <a href="{{ route('products.index', ['search' => 'Glycolic Moisturizer']) }}">
                        <img
                            src="{{ asset('images/Glycolic-Moisturizer.jpeg') }}"
                            alt="Glycolic Moisturizer"
                            class="w-full rounded-lg shadow-md"
                        />
                    </a>
                </div>
            </div>

            {{-- Product 3 (image left) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center mb-16">
                <!-- image in col 1 -->
                <div class="md:col-start-1">
                    <a href="{{ route('products.index', ['search' => 'Dr Pimple Serum']) }}">
                        <img
                            src="{{ asset('images/dr-pimple.jpeg') }}"
                            alt="Dr Pimple Serum"
                            class="w-full rounded-lg shadow-md"
                        />
                    </a>
                </div>
                <!-- text in col 2 -->
                <div class="md:col-start-2">
                    <h3 class="text-2xl font-semibold mb-2">Dr Pimple Serum</h3>
                    <h4 class="text-xl text-gray-600 mb-4">Gentle Clarifying & Soothing Treatment</h4>
                    <p class="text-base leading-relaxed">
                        This ultra-light serum blends calming calamine clay with pore-refining salicylic
                        acid to absorb excess oil, gently exfoliate, and reduce redness. Your complexion
                        will feel balanced and matte—no tightness, just a clear, comfortable glow. Use
                        morning and night to maintain skin that’s smooth, calm, and ready for whatever the
                        day brings.
                    </p>
                </div>
            </div>

            {{-- Product 4 (image right) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                <!-- text in first column -->
                <div class="md:col-start-1">
                    <h3 class="text-2xl font-semibold mb-2">Makeup Peel</h3>
                    <h4 class="text-xl text-gray-600 mb-4">Sweet Exfoliation for Instant Radiance</h4>
                    <p class="text-base leading-relaxed">
                        Transform your routine with a dreamy at-home peel that sweeps away dead skin,
                        pollution, and stubborn makeup in one go. Infused with nourishing honey and almond
                        proteins, it buffs and conditions, revealing a silky-soft surface and a healthy,
                        lit-from-within luminosity. Perfect for weekly spa-level pampering—no harsh grains,
                        just gentle polish.
                    </p>
                </div>
                <!-- image in second column -->
                <div class="md:col-start-2">
                    <a href="{{ route('products.index', ['search' => 'Makeup Peel']) }}">
                        <img
                            src="{{ asset('images/makeup-peel.jpeg') }}"
                            alt="Makeup Peel"
                            class="w-full rounded-lg shadow-md"
                        />
                    </a>
                </div>
            </div>
    </section>

    <!-- Promo Video -->
    <section class="promo-video py-16">
        <div class="container mx-auto px-6">
            <div class="w-full max-w-4xl mx-auto aspect-square">
                <video
                    src="{{ asset('images/meitalic-promo.mp4') }}"
                    autoplay
                    loop
                    muted
                    playsinline
                    class="w-full h-full object-cover rounded-lg shadow-md"
                ></video>
            </div>
        </div>
    </section>


    <!-- Shop by Brand -->
    <section class="shop-by-brand py-16">
        <div class="container mx-auto px-6">
            <h2 class="section-title text-2xl font-bold mb-6 text-center">Shop by Brand</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                {{-- Column 1: brand buttons --}}
                <div class="flex flex-col items-center md:items-start space-y-4">
                    @foreach(array_keys(config('brands')) as $brand)
                        <a
                            href="{{ route('products.index', array_merge(
                request()->only(['search','brand','category']),
                ['brand' => $brand]
            )) }}"
                            class="brand-card border p-4 text-center hover:shadow-lg transition w-full max-w-xs"
                        >
                            <span class="block text-xl font-medium">{{ $brand }}</span>
                        </a>
                    @endforeach
                </div>

                {{-- Column 2: preview video --}}
                <div class="flex justify-center">
                    <div class="w-full max-w-xs aspect-square overflow-hidden rounded-lg shadow-md">
                        <video
                            src="{{ asset('images/glycolic-moisturizer.mp4') }}"
                            poster="{{ asset('images/glycolic-moisturizer-poster.jpg') }}"
                            class="w-full h-full object-cover"
                            autoplay
                            muted
                            loop
                            playsinline
                            preload="metadata"
                            aria-label="Glycolic Moisturizer preview"
                        ></video>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Shop by Category -->
    <section class="shop-by-category py-16">
        <div class="container mx-auto px-6">
            <h2 class="section-title text-2xl font-bold mb-6 text-center">Shop by Category</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                {{-- Column 1: preview video --}}
                <div class="flex justify-center md:justify-start">
                    <div class="w-full max-w-xs aspect-square overflow-hidden rounded-lg shadow-md">
                        <video
                            src="{{ asset('images/silk-skincare.mp4') }}"
                            poster="{{ asset('images/silk-skincare-poster.jpg') }}"
                            class="w-full h-full object-cover"
                            autoplay
                            muted
                            loop
                            playsinline
                            preload="metadata"
                            aria-label="Silk Skincare preview"
                        ></video>
                    </div>
                </div>

                {{-- Column 2: category buttons --}}
                <div class="flex flex-col items-center md:items-start space-y-4">
                    @foreach([
                      'Skincare'     => 'Skincare',
                      'Makeup'       => 'Makeup',
                      'Starter Kits' => 'Starter Kits',
                      'Accessories'  => 'Accessories',
                    ] as $label => $cat)
                        <a
                            href="{{ route('products.index', array_merge(
                request()->only(['search','brand','category']),
                ['category' => $cat]
            )) }}"
                            class="category-card border p-4 text-center hover:shadow-lg transition w-full max-w-xs"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
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
        x-data="{ open: @json(session()->has('newsletter_success')) }"
        x-init="if (!open) setTimeout(() => open = true, 500)"
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

                    {{-- Show success message if present --}}
                    @if(session('newsletter_success'))
                        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
                            {{ session('newsletter_success') }}
                        </div>
                    @endif

                    {{-- Hide the form once subscribed --}}
                    @unless(session('newsletter_success'))
                        <x-form
                            method="POST"
                            action="{{ route('newsletter.subscribe') }}"
                            class="space-y-4"
                        >
                            @csrf
                            <input
                                type="email"
                                name="email"
                                placeholder="Your email address"
                                required
                                class="w-full px-6 py-3 border rounded focus:outline-none focus:ring-2 focus:ring-accent"
                            />
                            <button type="submit" class="btn-primary w-full">Subscribe</button>
                        </x-form>
                    @endunless

                </div>
            </div>
        </template>
    </section>


@endsection
