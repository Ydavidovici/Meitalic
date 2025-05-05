@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')
@section('title','Your Dashboard')

@section('content')
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
                        <button @click="openOrder({{ $o->id }})"
                                class="text-indigo-600 hover:underline">
                            Order #{{ $o->id }}
                        </button>
                        <span class="ml-2 px-2 py-1 bg-gray-100 rounded text-sm">
            {{ ucfirst($o->status) }}
          </span>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- 3) Full Order History --}}
        <div class="bg-white rounded shadow p-6">
            <h3 class="text-xl font-bold mb-4">Order History</h3>

            <div class="flex space-x-4 mb-4">
                <select x-model="dateRange" class="border rounded p-2">
                    <option value="all">All Time</option>
                    <option value="year">This Year</option>
                    <option value="month">This Month</option>
                    <option value="week">This Week</option>
                </select>
                <select x-model="status" class="border rounded p-2">
                    <option value="">Any Status</option>
                    @foreach(['pending','shipped','delivered','unfulfilled','canceled','returned'] as $st)
                        <option value="{{ $st }}">{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>

            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Total</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($allOrders as $o)
                    <tr
                        class="border-t"
                        x-show="
              (dateRange==='all' || '{{ $o->created_at->diffForHumans() }}'.includes(dateRange))
              && (!status || '{{ $o->status }}'===status)
            "
                    >
                        <td class="px-4 py-2">{{ $o->id }}</td>
                        <td class="px-4 py-2">{{ $o->created_at->format('M j, Y') }}</td>
                        <td class="px-4 py-2">${{ number_format($o->total,2) }}</td>
                        <td class="px-4 py-2">{{ ucfirst($o->status) }}</td>
                        <td class="px-4 py-2">
                            <button @click="openOrder({{ $o->id }})"
                                    class="text-sm text-indigo-600 hover:underline">
                                View Order
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="mt-4">{{ $allOrders->links() }}</div>
        </div>

        {{-- 4. Account & Profile --}}
        <div class="bg-white rounded shadow overflow-hidden">
            <h3 class="bg-gray-100 px-6 py-3 font-bold">Account & Profile</h3>
            <div class="p-6">
                <a href="{{ route('profile.edit') }}" class="text-indigo-600 hover:underline">
                    Edit Personal Info
                </a>
            </div>
        </div>

        {{-- 5. Your Cart --}}
        <div class="bg-white rounded shadow p-6" x-data>
            <h3 class="font-bold mb-2">Your Cart</h3>
            <button
                @click="
        $store.cart.open = true;
        // force reload of items if sidebar already mounted
        $nextTick(() => $root.__x.$data.$refs.cartSidebar.load());
      "
                class="text-indigo-600 hover:underline focus:outline-none"
            >
                View Current Cart
            </button>
        </div>

        {{-- 6. Recommendations & Promotions --}}
        <div class="bg-white rounded shadow p-6">
            <h3 class="font-bold mb-2">You Might Like</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($recommendations as $prod)
                    <div class="h-40 bg-gray-100 rounded overflow-hidden mb-4">
                        <img
                            src="{{
                Str::startsWith($prod->thumbnail_url,['http://','https://'])
                  ? $prod->thumbnail_url
                  : asset('storage/'.$prod->thumbnail_url)
              }}"
                            alt="{{ $prod->name }}"
                            class="w-full h-24 object-cover rounded"
                        >
                    </div>
                @endforeach
            </div>

        {{-- 7. Support & Contact --}}
        <div class="text-center">
            <a href="{{ route('contact') }}" class="btn-primary">Contact Support</a>
            <a href="{{ route('faq') }}" class="ml-4 text-indigo-600 hover:underline">View FAQ</a>
        </div>


        {{-- Inline Order Details Modal --}}
        <div
            x-show="modalOpen"
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
