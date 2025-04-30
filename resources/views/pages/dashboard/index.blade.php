{{-- resources/views/pages/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Your Dashboard')

@section('content')
    <div class="py-12 container px-4 sm:px-6 lg:px-8 space-y-8">

        {{-- At-a-Glance Metrics --}}
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

        {{-- Recent & Upcoming Orders --}}
        <div class="bg-white rounded shadow p-6">
            <h3 class="text-xl font-bold mb-4">Recent & Upcoming Orders</h3>
            <ul class="space-y-2">
                @foreach($recentOrders as $order)
                    <li class="flex justify-between items-center">
                        <div>
                            <a href="{{ route('order.show', $order) }}" class="text-indigo-600 hover:underline">
                                Order #{{ $order->id }}
                            </a>
                            <span class="ml-2 px-2 py-1 bg-gray-100 rounded text-sm">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-4">
                            @if($order->status === 'shipped')
                                <span class="text-sm text-gray-500">
                                    Est. Delivery: {{ $order->estimated_delivery->format('M j') }}
                                </span>
                                <a href="{{ route('order.track', $order) }}" class="text-sm text-blue-600 hover:underline">
                                    Track shipment
                                </a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Full Order History --}}
        <div class="bg-white rounded shadow p-6" x-data="{ dateRange: 'all', status: '' }">
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
                    <option value="pending">Pending</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
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
                @foreach($allOrders as $order)
                    <tr class="border-t"
                        x-show="(dateRange==='all' || '{{ $order->filterRange }}'===dateRange)
                                   && (!status || '{{ $order->status }}'===status)">
                        <td class="px-4 py-2">{{ $order->id }}</td>
                        <td class="px-4 py-2">{{ $order->created_at->format('M j, Y') }}</td>
                        <td class="px-4 py-2">${{ number_format($order->total,2) }}</td>
                        <td class="px-4 py-2">{{ ucfirst($order->status) }}</td>
                        <td class="px-4 py-2">
                            <a href="{{ route('order.show', $order) }}" class="text-sm text-indigo-600 hover:underline">
                                View Order
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="mt-4">{{ $allOrders->links() }}</div>
        </div>

        {{-- Account & Profile --}}
        <div class="bg-white rounded shadow overflow-hidden">
            <h3 class="bg-gray-100 px-6 py-3 font-bold">Account & Profile</h3>
            <div class="p-6 space-y-2">
                <a href="{{ route('profile.edit') }}" class="text-indigo-600 hover:underline">Edit Personal Info</a><br>
                {{-- addresses removed since not implemented --}}
            </div>
        </div>

        {{-- Your Cart --}}
        <div class="bg-white rounded shadow p-6">
            <h3 class="font-bold mb-2">Your Cart</h3>
            <a href="{{ url('/dashboard/cart') }}" class="text-indigo-600 hover:underline">
                View Current Cart
            </a>
        </div>

        {{-- Recommendations & Promotions --}}
        <div class="bg-white rounded shadow p-6">
            <h3 class="font-bold mb-2">You Might Like</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($recommendations as $prod)
                    <div class="border p-4 rounded">
                        <img src="{{ $prod->thumbnail_url }}" alt="{{ $prod->name }}" class="w-full h-24 object-cover rounded">
                        <h4 class="mt-2 text-sm font-semibold">{{ $prod->name }}</h4>
                    </div>
                @endforeach
            </div>

            <h3 class="font-bold mt-6 mb-2">Active Promo Codes</h3>
            @forelse($activePromos as $promo)
                <div class="mb-1">
                    <strong>{{ $promo->code }}</strong> — {{ $promo->description }}
                    <button @click="applyPromo('{{ $promo->code }}')" class="ml-2 text-sm text-blue-600 hover:underline">
                        Apply
                    </button>
                </div>
            @empty
                <p class="text-gray-600">No promotions available.</p>
            @endforelse
        </div>

        {{-- Support & Contact --}}
        <div class="text-center">
            <a href="{{ route('contact') }}" class="btn-primary">Contact Support</a>
            <a href="{{ route('faq') }}" class="ml-4 text-indigo-600 hover:underline">View FAQ</a>
        </div>

    </div>
@endsection
