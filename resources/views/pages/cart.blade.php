@extends('layouts.app')

@section('title', 'Your Cart')

@section('content')
    <div class="max-w-4xl mx-auto py-12 px-4">
        <h1 class="text-3xl font-semibold mb-6">Your Cart</h1>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if(count($items) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full text-left mb-6 border-collapse">
                    <thead>
                    <tr class="border-b">
                        <th class="py-2">Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $item)
                        <tr class="border-b">
                            <td class="py-2">{{ $item['product']->name }}</td>
                            <td>{{ $item['quantity'] }}</td>
                            <td>${{ number_format($item['product']->price, 2) }}</td>
                            <td>${{ number_format($item['product']->price * $item['quantity'], 2) }}</td>
                            <td>
                                <form method="POST" action="{{ route('cart.remove', $item['product']->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col items-end space-y-4">
                <div class="text-xl font-semibold">
                    Total: ${{ number_format($total, 2) }}
                </div>

                <form method="POST" action="{{ route('checkout.create') }}">
                    @csrf
                    <button type="submit" class="bg-black text-white px-6 py-3 rounded hover:bg-gray-900 transition">
                        Checkout with Stripe
                    </button>
                </form>
            </div>
        @else
            <div class="text-gray-600 text-center mt-10">
                <p>Your cart is currently empty.</p>
                <a href="{{ route('home') }}" class="text-blue-600 hover:underline mt-2 inline-block">Continue Shopping</a>
            </div>
        @endif
    </div>
@endsection
