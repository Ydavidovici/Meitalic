@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')
@section('title','Your Dashboard')

@section('content')
    <script>
        window.profileData = @json($profileData);
    </script>
    <div x-data="userDashboard()" class="py-12 container px-4 sm:px-6 lg:px-8 space-y-8">

        {{-- 1) At‑a‑Glance Metrics --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="p-6 bg-white rounded shadow">
                <h4 class="font-semibold">Total Orders</h4>
                <p class="text-3xl">{{ $totalOrders }}</p>
            </div>
            <div class="p-6 bg-white rounded shadow">
                <h4 class="font-semibold">Spend This Year</h4>
                <p class="text-3xl">${{ number_format($yearlySpend,2) }}</p>
            </div>
            <div class="p-6 bg-white rounded shadow">
                <h4 class="font-semibold">Store Credit</h4>
                <p class="text-3xl">{{ $storeCredit }} pts</p>
            </div>
        </div>

        {{-- 2) Recent & Upcoming Orders --}}
        <div class="bg-white rounded shadow p-6">
            <h3 class="text-xl font-bold mb-4">Recent & Upcoming Orders</h3>
            <ul class="space-y-2">
                @foreach($recentOrders as $o)
                    <li class="flex justify-between items-center">
                        <span>Order #{{ $o->id }}</span>
                        <span class="ml-2 px-2 py-1 bg-gray-100 rounded text-sm">{{ ucfirst($o->status) }}</span>

                        @if($o->status === 'delivered')
                            @php $item = $o->orderItems->first(fn($i) => ! $i->review); @endphp
                            <div>
                                <button
                                    @click="openReviewModal({ orderId: {{ $o->id }}, itemId: {{ $item?->id ?? 'null' }}, productId: {{ $item?->product_id ?? 'null' }}, rating: 1, body: '' })"
                                    class="text-sm {{ $item? '&text-green-600' : 'text-gray-600' }} hover:underline"
                                >
                                    {{ $item ? 'Leave Review' : 'Manage Reviews' }}
                                </button>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- 3) Full Order History --}}
        <div class="bg-white rounded shadow p-6">
            <h3 class="text-xl font-bold mb-4">Order History</h3>
            {{-- table markup omitted for brevity; unchanged --}}
        </div>

        {{-- 4) Account & Profile --}}
        <div class="bg-white rounded shadow p-6">
            <h3 class="text-xl font-bold mb-4">Your Profile</h3>

            <div class="space-y-2">
                <div><strong>Name:</strong> {{ $user->name }}</div>
                <div><strong>Email:</strong> {{ $user->email }}</div>
                {{-- add any other fields you track, e.g. phone, address --}}
            </div>

            <button
                @click="openProfileModal()"
                class="mt-4 btn-primary"
            >Edit Profile</button>
        </div>

        {{-- Profile Edit Modal --}}
        <div
            x-show="isProfileModalOpen"
            x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
            <div class="bg-white rounded-lg p-6 w-full max-w-md relative">
                <button @click="closeProfileModal()" class="absolute top-2 right-2 text-gray-600 text-xl">&times;</button>
                <h2 class="text-xl font-bold mb-4">Edit Profile</h2>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label for="name" class="block font-medium">Name</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            x-model="profileForm.name"
                            class="border rounded w-full p-2"
                        />
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block font-medium">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            x-model="profileForm.email"
                            class="border rounded w-full p-2"
                        />
                    </div>
                    {{-- add more fields as needed --}}
                    <button type="submit" class="btn-primary w-full">Save</button>
                </form>
            </div>
        </div>

        {{-- Review Modal --}}
        <div x-show="isReviewModalOpen" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md relative">
                <button @click="closeReviewModal()" class="absolute top-2 right-2 text-gray-600 text-xl">&times;</button>
                <h2 class="text-xl font-bold mb-4" x-text="modalTitle"></h2>
                <form :action="modalAction" method="POST">
                    @csrf
                    <template x-if="modalData.itemId">
                        <input type="hidden" name="order_item_id" :value="modalData.itemId">
                        <input type="hidden" name="product_id" :value="modalData.productId">
                    </template>
                    <div class="mb-4">
                        <label for="rating" class="block font-medium">Rating:</label>
                        <select name="rating" id="rating" x-model="modalData.rating" class="border rounded w-full p-2">
                            <template x-for="i in [1,2,3,4,5]" :key="i">
                                <option :value="i" x-text="i"></option>
                            </template>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="body" class="block font-medium">Review:</label>
                        <textarea name="body" id="body" x-model="modalData.body" class="border rounded w-full p-2" rows="4"></textarea>
                    </div>
                    <button type="submit" class="btn-primary w-full">Submit Review</button>
                </form>
            </div>
        </div>



        {{-- 6. Recommendations & Promotions --}}
        <div class="bg-white rounded shadow p-6">
            <h3 class="font-bold mb-2">You Might Like</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($recommendations as $prod)
                    <div class="h-40 bg-gray-100 rounded overflow-hidden mb-4">
                        <img
                            src="{{ Str::startsWith($prod->thumbnail_url, ['http://','https://'])
                      ? $prod->thumbnail_url
                      : asset('storage/'.$prod->thumbnail_url)
                    }}"
                            alt="{{ $prod->name }}"
                            class="w-full h-24 object-cover rounded"
                        >
                        {{-- Add to Cart button --}}
                        <div class="p-2">
                            <form action="{{ route('cart.add') }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $prod->id }}">
                                <input type="hidden" name="quantity" value="1">
                                <button
                                    type="submit"
                                    class="btn-primary flex-1 text-center py-1"
                                >Add to Cart</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 7. Support --}}
        <div class="bg-white rounded shadow p-6 text-center">
            <a href="{{ route('contact') }}" class="btn-primary">Contact Support</a>
        </div>


        {{-- Inline Order Details Modal --}}
        <div
            x-show="isOrderModalOpen"
            x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
            <div class="bg-white rounded-lg overflow-auto max-w-2xl w-full mx-4 p-6 relative">
                <button @click="closeModal()"
                        class="absolute top-3 right-3 text-gray-600 hover:text-gray-900 text-2xl">
                    &times;
                </button>

                <template x-if="selectedOrder">
                    <div>
                        <h2 class="text-2xl font-bold mb-4">
                            Order #<span x-text="selectedOrder.id"></span>
                        </h2>
                        <p class="mb-2">
                            <strong>Status:</strong>
                            <span x-text="
              selectedOrder.status.charAt(0).toUpperCase()
              + selectedOrder.status.slice(1)
            "></span>
                        </p>
                        <p class="mb-4">
                            <strong>Date:</strong>
                            <span x-text="
              new Date(selectedOrder.created_at)
                .toLocaleString()
            "></span>
                        </p>
                        <p class="mb-4">
                            <strong>Total:</strong>
                            $<span x-text="selectedOrder.total.toFixed(2)"></span>
                        </p>

                        <h3 class="font-semibold mb-2">Items</h3>
                        <ul class="list-disc pl-6 mb-4">
                            <template x-for="item in selectedOrder.items" :key="item.id">
                                <li>
                                    <span x-text="item.name"></span>
                                    &times; <span x-text="item.quantity"></span>
                                    @ $<span x-text="item.price.toFixed(2)"></span>
                                </li>
                            </template>
                        </ul>

                        <div class="mt-6 flex space-x-2">
                            <button @click="closeModal()" class="btn-secondary">
                                Close
                            </button>

                            <template x-if="selectedOrder.status === 'pending'">
                                <button @click="cancelOrder(selectedOrder.id)" class="btn-primary">
                                    Cancel Order
                                </button>
                            </template>

                            <template x-if="['shipped','delivered'].includes(selectedOrder.status)">
                                <button @click="returnOrder(selectedOrder.id)" class="btn-primary">
                                    Return Order
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
@endsection


