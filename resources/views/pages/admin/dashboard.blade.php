{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <div x-data="adminDashboard()" class="py-12 container px-4 sm:px-6 lg:px-8 space-y-8">

        {{-- 1. KPIs --}}
        <div class="grid grid-cols-1 md:grid-cols-7 gap-6">
            <div class="p-6 bg-white rounded shadow">
                <h4 class="font-semibold">Orders Today</h4>
                <p class="text-3xl">{{ $kpis['orders_today'] }}</p>
            </div>
            <div class="p-6 bg-white rounded shadow">
                <h4 class="font-semibold">Orders This Week</h4>
                <p class="text-3xl">{{ $kpis['orders_week'] }}</p>
            </div>
            <div class="p-6 bg-white rounded shadow">
                <h4 class="font-semibold">Orders This Month</h4>
                <p class="text-3xl">{{ $kpis['orders_month'] }}</p>
            </div>
            <div class="p-6 bg-white rounded shadow">
                <h4 class="font-semibold">Revenue Today</h4>
                <p class="text-3xl">${{ number_format($kpis['revenue_today'],2) }}</p>
            </div>
            <div class="p-6 bg-white rounded shadow">
                <h4 class="font-semibold">Revenue This Week</h4>
                <p class="text-3xl">${{ number_format($kpis['revenue_week'],2) }}</p>
            </div>
            <div class="p-6 bg-white rounded shadow">
                <h4 class="font-semibold">Revenue This Month</h4>
                <p class="text-3xl">${{ number_format($kpis['revenue_month'],2) }}</p>
            </div>
            <div class="p-6 bg-white rounded shadow">
                <h4 class="font-semibold">Avg. Order Value</h4>
                <p class="text-3xl">${{ number_format($kpis['avg_order_value'],2) }}</p>
            </div>
            <div class="md:col-span-7 text-right">
                <button @click="toggleDevMetrics()" class="text-sm text-gray-500 underline">
                    <span x-text="devMetricsVisible ? 'Hide Dev Metrics' : 'Show Dev Metrics'">Show Dev Metrics</span>
                </button>
            </div>
        </div>

        {{-- 2. Order Management --}}
        <div class="bg-white rounded shadow p-6">
            <h3 class="font-bold mb-4 flex justify-between items-center">
                <span>Order Management</span>
                <div class="space-x-2">
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded">Pending: {{ $counts['pending'] }}</span>
                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded">Unfulfilled: {{ $counts['unfulfilled'] }}</span>
                    <button @click="markBulk('shipped')" class="text-sm text-blue-600 hover:underline">Mark Selected Shipped</button>
                    <button @click="markBulk('delivered')" class="text-sm text-green-600 hover:underline">Mark Selected Delivered</button>
                </div>
            </h3>
            <div class="mb-4 flex items-center space-x-4">
                <select x-model="dateFilter" class="border rounded p-2">
                    <option value="all">All Dates</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
                <div class="space-x-2">
                    <template x-for="s in ['pending','shipped','delivered','fulfilled']" :key="s">
                        <label class="inline-flex items-center">
                            <input type="checkbox" x-model="statusFilter" :value="s" class="form-checkbox"/>
                            <span class="ml-1 capitalize" x-text="s"></span>
                        </label>
                    </template>
                </div>
            </div>
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2"><input type="checkbox" @click="toggleAll" /></th>
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">Customer</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($recentOrders as $order)
                    <tr class="border-t"
                        x-show="matchesDate('{{ $order->filterDate }}') && matchesStatus('{{ $order->status }}')">
                        <td class="px-4 py-2">
                            <input type="checkbox" value="{{ $order->id }}" x-model="selectedOrders" />
                        </td>
                        <td class="px-4 py-2">{{ $order->id }}</td>
                        <td class="px-4 py-2">{{ optional($order->user)->name ?? 'Guest' }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 bg-gray-100 rounded text-sm">{{ ucfirst($order->status) }}</span>
                        </td>
                        <td class="px-4 py-2">{{ $order->created_at->format('M j, Y') }}</td>
                        <td class="px-4 py-2 space-x-2">
                            <button @click="singleMark({{ $order->id }}, 'shipped')" class="text-sm text-blue-600 hover:underline">Mark Shipped</button>
                            <button @click="singleMark({{ $order->id }}, 'delivered')" class="text-sm text-green-600 hover:underline">Mark Delivered</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- 3. Inventory Alerts --}}
        <div class="bg-white rounded shadow overflow-x-auto p-6">
            <h3 class="font-bold mb-4">Inventory Alerts</h3>
            <ul class="space-y-1">
                @foreach($lowStock as $prod)
                    <li class="flex justify-between">
                        <span>{{ $prod->name }} ({{ $prod->inventory }} left)</span>
                        <a href="{{ route('products.edit', $prod) }}" class="text-sm text-indigo-600 hover:underline">Restock</a>
                    </li>
                @endforeach
                @foreach($outOfStock as $prod)
                    <li class="flex justify-between text-red-600">
                        <span>{{ $prod->name }} (Out of Stock)</span>
                        <a href="{{ route('products.edit', $prod) }}" class="text-sm text-indigo-600 hover:underline">Restock</a>
                    </li>
                @endforeach
                @if($lowStock->isEmpty() && $outOfStock->isEmpty())
                    <li class="text-gray-600">All products have sufficient stock.</li>
                @endif
            </ul>
        </div>

        {{-- 4. Product Performance --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded shadow p-6">
                <h4 class="font-bold mb-2">Top Sellers (Quantity)</h4>
                <ol class="list-decimal pl-5 space-y-1">
                    @foreach($topSellers as $prod)
                        <li>{{ $prod->name }} ({{ $prod->sold }} sold)</li>
                    @endforeach
                </ol>
                <h4 class="font-bold mt-4 mb-2">Top Sellers (Revenue)</h4>
                <ol class="list-decimal pl-5 space-y-1">
                    @foreach($topRevenue as $prod)
                        <li>{{ $prod->name }} (${{ number_format($prod->revenue,2) }})</li>
                    @endforeach
                </ol>
            </div>
            <div class="bg-white rounded shadow p-6">
                <h4 class="font-bold mb-2">Slow Movers</h4>
                <ol class="list-decimal pl-5 space-y-1">
                    @foreach($slowMovers as $prod)
                        <li>{{ $prod->name }} ({{ $prod->inventory }} in stock)</li>
                    @endforeach
                </ol>
            </div>
        </div>

        {{-- 5. Customer Insights --}}
        <div class="bg-white rounded shadow p-6">
            <h3 class="font-bold mb-2">New Registrations Today: {{ $newCustomersToday }}</h3>
            <h4 class="font-bold mb-2">Top Customers by Lifetime Spend</h4>
            <ul class="space-y-1">
                @foreach($topCustomers as $user)
                    <li>{{ $user->name }} (${{ number_format($user->lifetime_spend,2) }})</li>
                @endforeach
            </ul>
        </div>

        {{-- 6. Marketing & Promotions --}}
        <div class="bg-white rounded shadow p-6">
            <h3 class="font-bold mb-2">Active Coupons & Redemption</h3>
            <ul class="space-y-1">
                @foreach($activeCoupons as $coupon)
                    <li>{{ $coupon->code }} — used {{ $coupon->used_count }}×</li>
                @endforeach
            </ul>
            <h4 class="font-bold mt-4 mb-2">Expiring Soon</h4>
            <ul class="space-y-1">
                @foreach($expiringCoupons as $coupon)
                    <li>{{ $coupon->code }} — expires {{ $coupon->expires_at->format('M j') }}</li>
                @endforeach
            </ul>
        </div>

        {{-- 7. Site Analytics --}}
        <div x-show="devMetricsVisible" class="bg-white rounded shadow p-6">
            <h3 class="font-bold mb-4">Site Analytics</h3>
            {!! $analyticsHtml !!}
        </div>

        {{-- 8. Settings & Shortcuts --}}
        <div class="bg-white rounded shadow p-6">
            <h3 class="font-bold mb-2">Settings & Shortcuts</h3>
            <ul class="space-y-1">
                <li><a href="{{ route('products.index') }}" class="text-indigo-600 hover:underline">Manage Products</a></li>
                <li><a href="{{ route('promo.index') }}" class="text-indigo-600 hover:underline">Create Promo Code</a></li>
                <li><a href="{{ route('users.index') }}" class="text-indigo-600 hover:underline">User & Role Management</a></li>
            </ul>
        </div>
    </div>
@endsection
