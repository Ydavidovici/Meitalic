@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')
@push('styles')
    @vite('resources/js/user-dashboard.js')
@endpush
@section('title','Your Dashboard')

@section('content')
    <script>
        window.profileData = @json($profileData);
    </script>

    <div x-data="userDashboard()" class="dashboard">

        {{-- 1) At‑a‑Glance Metrics --}}
        <div class="dashboard__metrics">
            <div class="dashboard__metric-card">
                <h4 class="dashboard__metric-title">Total Orders</h4>
                <p class="dashboard__metric-value">{{ $totalOrders }}</p>
            </div>
            <div class="dashboard__metric-card">
                <h4 class="dashboard__metric-title">Spend This Year</h4>
                <p class="dashboard__metric-value">${{ number_format($yearlySpend,2) }}</p>
            </div>
            <div class="dashboard__metric-card">
                <h4 class="dashboard__metric-title">Store Credit</h4>
                <p class="dashboard__metric-value">{{ $storeCredit }} pts</p>
            </div>
        </div>

        {{-- 2) Recent & Upcoming Orders --}}
        <div class="dashboard__recent">
            <h3 class="dashboard__recent-title">Recent &amp; Upcoming Orders</h3>
            <ul class="dashboard__recent-list">
                @foreach($recentOrders as $o)
                    <li class="dashboard__recent-item">
                        <span>Order #{{ $o->id }}</span>
                        <span class="dashboard__status-badge">{{ ucfirst($o->status) }}</span>

                        @if($o->status === 'delivered')
                            @php $item = $o->orderItems->first(fn($i) => ! $i->review); @endphp
                            <div>
                                <button
                                    @click="openReviewModal({ orderId: {{ $o->id }}, itemId: {{ $item?->id ?? 'null' }}, productId: {{ $item?->product_id ?? 'null' }}, rating: 1, body: '' })"
                                    class="dashboard__review-btn {{ $item ? 'dashboard__review-available' : 'dashboard__review-none' }}"
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
        <div class="dashboard__history">
            <h3 class="dashboard__history-title">Order History</h3>
            {{-- (Your table markup goes here, unchanged) --}}
        </div>

        {{-- 4) Account & Profile --}}
        <div class="dashboard__profile">
            <h3 class="dashboard__profile-title">Your Profile</h3>
            <div class="dashboard__profile-fields">
                <div><strong>Name:</strong> {{ $user->name }}</div>
                <div><strong>Email:</strong> {{ $user->email }}</div>
                {{-- Add other fields as needed --}}
            </div>
            <button @click="openProfileModal()" class="dashboard__profile-edit-btn">
                Edit Profile
            </button>
        </div>

        <x-modal name="profile-edit" maxWidth="md">
            <x-slot name="title">Edit Profile</x-slot>
            <form id="profile-form" action="{{ route('profile.update') }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-4">
                    <label for="name" class="block font-medium">Name</label>
                    <input id="name" name="name" x-model="profileForm.name" />
                </div>
                <div class="mb-4">
                    <label for="email" class="block font-medium">Email</label>
                    <input id="email" name="email" x-model="profileForm.email" />
                </div>
            </form>
            <x-slot name="footer">
                <button @click="$dispatch('close-modal','profile-edit')" class="btn-secondary">
                    Cancel
                </button>
                <button type="submit" form="profile-form" class="btn-primary">
                    Save
                </button>
            </x-slot>
        </x-modal>

        {{-- Review Modal --}}
        <div x-show="isReviewModalOpen" x-cloak class="modal-wrapper">
            <div class="modal-panel">
                <button @click="closeReviewModal()" class="absolute top-2 right-2 text-gray-600 text-xl">
                    &times;
                </button>
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

        {{-- 6) Recommendations & Promotions --}}
        <div class="dashboard__recommendations">
            <h3 class="dashboard__recommendations-title">You Might Like</h3>
            <div class="dashboard__recommendations-grid">
                @foreach($recommendations as $prod)
                    <div class="recommendation-card">
                        <img
                            src="{{ Str::startsWith($prod->thumbnail_url, ['http://','https://'])
                                ? $prod->thumbnail_url
                                : asset('storage/'.$prod->thumbnail_url)
                            }}"
                            alt="{{ $prod->name }}"
                            class="recommendation-card__img"
                        >
                        <div class="recommendation-card__actions">
                            <form action="{{ route('cart.add') }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $prod->id }}">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="recommendation-card__btn">
                                    Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 7) Support --}}
        <div class="dashboard__support">
            <a href="{{ route('contact') }}" class="dashboard__support-btn">
                Contact Support
            </a>
        </div>

        {{-- 8) Inline Order Details Modal --}}
        <div x-show="isOrderModalOpen" x-cloak class="modal-wrapper">
            <div class="modal-panel">
                <button @click="closeModal()" class="absolute top-3 right-3 text-gray-600 hover:text-gray-900 text-2xl">
                    &times;
                </button>
                <template x-if="selectedOrder">
                    <div>
                        <h2 class="text-2xl font-bold mb-4">
                            Order #<span x-text="selectedOrder.id"></span>
                        </h2>
                        <p class="mb-2">
                            <strong>Status:</strong>
                            <span x-text="selectedOrder.status.charAt(0).toUpperCase() + selectedOrder.status.slice(1)"></span>
                        </p>
                        <p class="mb-4">
                            <strong>Date:</strong>
                            <span x-text="new Date(selectedOrder.created_at).toLocaleString()"></span>
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
                            <button @click="closeModal()" class="btn-secondary">Close</button>

                            <template x-if="selectedOrder.status === 'pending'">
                                <button @click="cancelOrder(selectedOrder.id)" class="btn-primary">Cancel Order</button>
                            </template>

                            <template x-if="['shipped','delivered'].includes(selectedOrder.status)">
                                <button @click="returnOrder(selectedOrder.id)" class="btn-primary">Return Order</button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>
@endsection
