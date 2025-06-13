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


    <!-- Why Shop at Meitalic -->
    <section class="why-shop bg-white mt-12 mb-12 py-16">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-3xl font-bold mb-8">Why Shop at Meitalic</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Satisfaction Guarantee -->
                <div class="flex flex-col items-center">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-8 w-8 text-pink-500 mb-3"
                         fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-semibold text-gray-800">
                        Satisfaction Guarantee
                    </p>
                </div>

                <!-- Free Shipping -->
                <div class="flex flex-col items-center">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-8 w-8 text-pink-500 mb-3"
                         fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 7h13v10H3V7zm13-4H3a2 2 0 00-2 2v12a2 2 0 002 2h1m16-6h2M16 3l5 5m0 0v8m0-8H16" />
                    </svg>
                    <p class="font-semibold text-gray-800">
                        Free Shipping on Orders Over $55
                    </p>
                </div>

                <!-- Made in USA -->
                <div class="flex flex-col items-center">
                    <img
                        src="{{ asset('images/128px-Flag_of_the_United_States.svg.png') }}"
                        alt="U.S. flag"
                        class="h-8 w-auto mb-3"
                    />
                    <p class="font-semibold text-gray-800">
                       Proudly Made in the USA
                    </p>
                </div>
            </div>
        </div>
    </section>


    <!-- Promo Video -->
    <section class="promo-video py-16">
        <div class="container mx-auto px-6">
            <div class="w-full max-w-4xl mx-auto aspect-square">
                <video
                    src="{{ asset('images/meitalic-promo-shortened(35).mp4') }}"
                    autoplay
                    loop
                    muted
                    playsinline
                    class="w-full h-full object-cover rounded-lg shadow-md"
                ></video>
            </div>
        </div>
    </section>



    <!-- Featured Products -->
    <section class="featured py-16">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl mb-12 text-center">Bestsellers</h2>

            {{-- Product 1: Glycolic Moisturizer (image right) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center mb-16">
                <!-- text in col 1 -->
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
                <!-- image in col 2 -->
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

            {{-- Product 2: Dr Pimple Serum (image left) --}}
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

            {{-- Product 3: Makeup Peel (text left) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center mb-16">
                <!-- text in col 1 -->
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
                <!-- image in col 2 -->
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

            {{-- Product 4: Exfoliating Scrub (image left) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
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
                        glow and renewed clarity.
                    </p>
                </div>
            </div>
        </div>
    </section>


    <!-- Shop by Brand -->
    <section class="shop-by-brand py-16 text-center">
        <h2 class="section-title text-2xl font-bold mb-6">
            Shop by Brand
        </h2>

        <div class="container mx-auto px-6 max-w-screen-lg">
            <div
                class="flex flex-col md:flex-row
             justify-center items-start md:items-center
             gap-8 md:gap-12"
            >
                <!-- Column 1: brand buttons -->
                <div class="flex flex-col items-center md:items-start space-y-4 w-full max-w-xs">
                    @foreach(array_keys(config('brands')) as $brand)
                        <a
                            href="{{ route('products.index', array_merge(request()->only(['search','brand','category']), ['brand' => $brand])) }}"
                            class="brand-card border p-4 text-center hover:shadow-lg transition w-full"
                        >
                            <span class="block text-xl font-medium">{{ $brand }}</span>
                        </a>
                    @endforeach
                </div>

                <!-- Column 2: preview video -->
                <div class="w-full max-w-xs aspect-square overflow-hidden rounded-lg shadow-md">
                    <video
                        src="{{ asset('images/glycolic-moisturizer.mp4') }}"
                        poster="{{ asset('images/glycolic-moisturizer-poster.jpg') }}"
                        class="w-full h-full object-cover"
                        autoplay muted loop playsinline preload="metadata"
                        aria-label="Glycolic Moisturizer preview"
                    ></video>
                </div>
            </div>
        </div>
    </section>


    <!-- Shop by Category -->
    <section class="shop-by-category py-16 text-center">
        <h2 class="section-title text-2xl font-bold mb-6">
            Shop by Category
        </h2>

        <div class="container mx-auto px-6 max-w-screen-lg">
            <div     class="flex flex-col md:flex-row
           justify-center items-start md:items-center
           gap-8 md:gap-12"
            >
                <!-- Column 1: preview video -->
                <div class="w-full max-w-xs aspect-square overflow-hidden rounded-lg shadow-md order-2 md:order-1">
                    <video
                        src="{{ asset('images/silk-skincare.mp4') }}"
                        poster="{{ asset('images/silk-skincare-poster.jpg') }}"
                        class="w-full h-full object-cover"
                        autoplay muted loop playsinline preload="metadata"
                        aria-label="Silk Skincare preview"
                    ></video>
                </div>

                <!-- Column 2: category buttons -->
                <div class="flex flex-col items-center md:items-start space-y-4 w-full max-w-xs order-1 md:order-2">
                    @foreach([
                      'Skincare'     => 'Skincare',
                      'Makeup'       => 'Makeup',
                      'Starter Kits' => 'Starter Kits',
                      'Accessories'  => 'Accessories',
                    ] as $label => $cat)
                        <a
                            href="{{ route('products.index', array_merge(request()->only(['search','brand','category']), ['category' => $cat])) }}"
                            class="category-card border p-4 text-center hover:shadow-lg transition w-full"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>



    <section class="instagram py-16 bg-secondary">
        <div class="container mx-auto px-6 text-center flex flex-col items-center">
            <h2 class="text-3xl font-bold mb-4">Follow Us on Instagram</h2>
            <a
                href="https://www.instagram.com/Meitaliccosmeceuticalsnewyork"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center space-x-2 justify-center text-accent hover:underline mt-2"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2zm0 1.5A4.25 4.25 0 0 0 3.5 7.75v8.5A4.25 4.25 0 0 0 7.75 20.5h8.5a4.25 4.25 0 0 0 4.25-4.25v-8.5A4.25 4.25 0 0 0 16.25 3.5h-8.5zM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 1.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7zm4.75-.75a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5z"/>
                </svg>
                <span class="text-xl break-all">@Meitaliccosmeceuticalsnewyork</span>
            </a>
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
    <section class="reviews py-16">
        <div class="container mx-auto px-6 reviews__inner">
            <h2 class="section-title text-3xl font-bold text-center mb-12">
                Customer Reviews
            </h2>

            <div class="reviews__grid grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Review 1 -->
                <div class="reviews__card bg-white p-6 rounded-lg shadow-md flex flex-col justify-between h-full">
                    <p class="reviews__text text-gray-700 mb-4 break-words">
                        “Highly Recommend! So natural and full!”
                    </p>
                    <p class="reviews__author text-gray-900 font-semibold mb-2">
                        — Emma R.
                    </p>
                    <div class="reviews__stars text-yellow-400 text-xl">
                        ★★★★★
                    </div>
                </div>

                <!-- Review 2 -->
                <div class="reviews__card bg-white p-6 rounded-lg shadow-md flex flex-col justify-between h-full">
                    <p class="reviews__text text-gray-700 mb-4 break-words">
                        “I just wanted to say a huge thank you for the perfect purchase.
                        Such a light, airy makeup—in 10 seconds I feel like a million dollars
                        😍🥰😍🥰. The facial cleanser is absolutely perfect, wow, and the face
                        cream I put on in the morning—what a combo! I seriously feel like I
                        have brand-new skin!!!!!”
                    </p>
                    <p class="reviews__author text-gray-900 font-semibold mb-2">
                        — Mia K.
                    </p>
                    <div class="reviews__stars text-yellow-400 text-xl">
                        ★★★★★
                    </div>
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
