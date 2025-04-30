@extends('layouts.app')

@section('title','Meitalic Cosmecueticals')

@section('content')
    <!-- Hero -->
    <section class="w-full bg-gradient-to-br from-secondary via-primary pt-20 pb-12">
        <div class="container px-4 sm:px-6 lg:px-8">
            <div class="max-w-screen-lg mx-auto text-center lg:text-left">
                <!-- Text wrapper: move this up -->
                <div class="space-y-4 transform -translate-y-8">
                    <h1 class="text-4xl sm:text-5xl font-bold text-text leading-tight">
                        Meitalic
                    </h1>
                    <p class="italic text-lg text-neutral-700 max-w-xl mx-auto lg:mx-0">
                        Where elegance meets skincare.
                    </p>
                </div>

                <!-- Button stays where it is -->
                <a href="{{ route('products.index') }}" class="btn-primary w-max">
                    Shop Now
                </a>
            </div>
        </div>
    </section>

    <!-- Categories -->
    <section class="py-20">
        <div class="container px-4 sm:px-6 lg:px-8">
            <div class="max-w-screen-lg mx-auto space-y-12 text-center">
                <h2 class="section-title">Shop by Category</h2>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
                    @foreach(['Makeup','Lipstick','Skincare','Fragrance','Accessories'] as $cat)
                        <div class="card">{{ $cat }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- About -->
    <section id="about" class="py-20">
        <div class="container px-4 sm:px-6 lg:px-8">
            <div class="max-w-screen-md mx-auto text-center space-y-6">
                <h2 class="section-title">About the Brand</h2>
                <blockquote class="italic text-neutral-600">
                    “A short and powerful quote to inspire customers.”
                </blockquote>
            </div>
        </div>
    </section>

    <!-- Benefits -->
    <section class="py-20">
        <div class="container px-4 sm:px-6 lg:px-8">
            <div class="max-w-screen-lg mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                @foreach(['Natural Ingredients','Hydrating Formula','Cruelty-Free','Dermatologist Tested'] as $b)
                    <div class="card">{{ $b }}</div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Reviews -->
    <section class="py-20">
        <div class="container px-4 sm:px-6 lg:px-8">
            <div class="max-w-screen-md mx-auto text-center space-y-6">
                <h2 class="section-title">Customer Reviews</h2>
                <p class="text-neutral-600">
                    “A customer testimonial... no one ever raving any orates.”
                </p>
                <div class="text-yellow-400 text-2xl">★★★★★</div>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="py-20">
        <div class="container px-4 sm:px-6 lg:px-8">
            <div class="max-w-screen-md mx-auto text-center space-y-6">
                <h2 class="section-title">Join Our Newsletter</h2>
                <p class="text-neutral-600">Get 10% off your first order</p>
                <form class="flex flex-col md:flex-row justify-center items-center gap-4 max-w-md mx-auto">
                    <input type="email" placeholder="Email address"
                           class="w-full md:w-auto px-6 py-3 border rounded focus:outline-none focus:ring-2 focus:ring-accent">
                    <button type="submit" class="btn-primary">Subscribe</button>
                </form>
            </div>
        </div>
    </section>
@endsection
