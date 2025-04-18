@extends('layouts.app')

@section('title', 'Welcome to Meitalic')

@section('content')
    <!-- Hero -->
    <section class="flex flex-col-reverse md:flex-row items-center justify-between max-w-7xl mx-auto py-16 px-6">
        <div class="md:w-1/2 text-center md:text-left">
            <h2 class="text-5xl font-bold mb-4">Meitalic</h2>
            <p class="text-lg mb-6">Where elegance meets skincare.</p>
            <a href="#" class="bg-black text-white px-6 py-3 rounded-full">Shop Now</a>
        </div>
        <div class="md:w-1/2 mb-10 md:mb-0">
            <div class="w-full h-96 bg-gray-200 rounded-lg"></div>
        </div>
    </section>

    <!-- Categories -->
    <section class="py-16 px-6 bg-gray-50">
        <h2 class="text-3xl font-semibold text-center mb-10">Shop by Category</h2>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-6 max-w-5xl mx-auto">
            <div class="bg-white p-6 rounded shadow text-center">Makeup</div>
            <div class="bg-white p-6 rounded shadow text-center">Lipstick</div>
            <div class="bg-white p-6 rounded shadow text-center">Skincare</div>
            <div class="bg-white p-6 rounded shadow text-center">Fragrance</div>
            <div class="bg-white p-6 rounded shadow text-center">Accessories</div>
        </div>
    </section>

    <!-- About -->
    <section class="py-16 px-6">
        <h2 class="text-3xl font-semibold text-center mb-6">About the Brand</h2>
        <blockquote class="italic text-center max-w-2xl mx-auto">“A short and powerful quote to inspire customers.”</blockquote>
    </section>

    <!-- Benefits -->
    <section class="py-16 px-6 bg-gray-50">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-5xl mx-auto text-center">
            <div class="p-4">Natural Ingredients</div>
            <div class="p-4">Hydrating Formula</div>
            <div class="p-4">Cruelty-Free</div>
            <div class="p-4">Dermatologist Tested</div>
        </div>
    </section>

    <!-- Reviews -->
    <section class="py-16 px-6">
        <h2 class="text-3xl font-semibold text-center mb-6">Customer Reviews</h2>
        <p class="text-center mb-4">“A customer testimonial... no one ever raving any” orates.”</p>
        <div class="text-center text-yellow-400 text-xl">★★★★★</div>
    </section>

    <!-- Newsletter -->
    <section class="py-16 px-6 bg-gray-50">
        <h2 class="text-3xl font-semibold text-center mb-4">Join Our Newsletter</h2>
        <p class="text-center mb-6">Get 10% off your first order</p>
        <form class="flex flex-col md:flex-row justify-center items-center gap-4 max-w-xl mx-auto">
            <input type="email" placeholder="Email address" class="w-full md:w-auto px-4 py-2 border rounded">
            <button type="submit" class="bg-black text-white px-6 py-2 rounded">Subscribe</button>
        </form>
    </section>
@endsection
