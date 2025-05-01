@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <div x-data="adminDashboard()" class="py-12 container px-4 sm:px-6 lg:px-8">

        {{-- 1. KPI CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-12">
            @foreach([
                ['orders-today',    'Orders Today',      $kpis['orders_today']],
                ['orders-week',     'Orders This Week',  $kpis['orders_week']],
                ['orders-month',    'Orders This Month', $kpis['orders_month']],
                ['revenue-today',   'Revenue Today',     '$'.number_format($kpis['revenue_today'],2)],
                ['revenue-week',    'Revenue This Week', '$'.number_format($kpis['revenue_week'],2)],
                ['revenue-month',   'Revenue This Month','$'.number_format($kpis['revenue_month'],2)],
                ['avg-order-value', 'Avg. Order Value',  '$'.number_format($kpis['avg_order_value'],2)],
            ] as [$key, $label, $value])
                <div
                    class="card cursor-pointer"
                    @click="openKpi('{{ $key }}')"
                >
                    <h4 class="font-semibold">{{ $label }}</h4>
                    <p class="text-3xl">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        {{-- KPI MODALS --}}
        @foreach([
            'orders-today'    => 'Orders Today',
            'orders-week'     => 'Orders This Week',
            'orders-month'    => 'Orders This Month',
            'revenue-today'   => 'Revenue Today',
            'revenue-week'    => 'Revenue This Week',
            'revenue-month'   => 'Revenue This Month',
            'avg-order-value' => 'Average Order Value',
        ] as $key => $label)
            <x-modal name="{{ $key }}" maxWidth="lg">
                <div class="p-6">
                    <h3 class="text-2xl font-bold mb-4">{{ $label }} Details</h3>
                    {{-- ➤ insert charts/tables here --}}
                    <p class="text-gray-600">More detailed breakdown...</p>
                    <button
                        class="btn-secondary mt-4"
                        @click="$dispatch('close-modal', '{{ $key }}')"
                    >Close</button>
                </div>
            </x-modal>
        @endforeach

        {{-- 2. ORDER MANAGEMENT --}}
        <div class="card mb-8">
            <h3 class="font-bold mb-4 flex justify-between items-center">
                <span>Order Management</span>
                <div class="space-x-2">
                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded">
                    Pending: {{ $counts['pending'] }}
                </span>
                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded">
                    Unfulfilled: {{ $counts['unfulfilled'] }}
                </span>
                    <button @click="markBulk('shipped')" class="text-sm text-blue-600 hover:underline">
                        Mark Selected Shipped
                    </button>
                    <button @click="markBulk('delivered')" class="text-sm text-green-600 hover:underline">
                        Mark Selected Delivered
                    </button>
                </div>
            </h3>
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-100">
                <tr>
                    <th><input type="checkbox" @click="toggleAll"/></th>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($recentOrders as $order)
                    <tr class="border-t"
                        x-show="matchesDate('{{ $order->filterDate }}') && matchesStatus('{{ $order->status }}')"
                    >
                        <td>
                            <input type="checkbox" value="{{ $order->id }}" x-model="selectedOrders"/>
                        </td>
                        <td>{{ $order->id }}</td>
                        <td>{{ optional($order->user)->name ?? 'Guest' }}</td>
                        <td>
                        <span class="px-2 py-1 bg-gray-100 rounded text-sm">
                            {{ ucfirst($order->status) }}
                        </span>
                        </td>
                        <td>{{ $order->created_at->format('M j, Y') }}</td>
                        <td class="space-x-2">
                            <button @click="singleMark({{ $order->id }}, 'shipped')" class="text-sm text-blue-600 hover:underline">
                                Mark Shipped
                            </button>
                            <button @click="singleMark({{ $order->id }}, 'delivered')" class="text-sm text-green-600 hover:underline">
                                Mark Delivered
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="card mb-8">
            <h3 class="font-bold mb-4">Inventory</h3>

            @if(session('product_success'))
                <div class="p-3 mb-4 bg-green-100 text-green-700 rounded">
                    {{ session('product_success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($products as $prod)
                    <div class="card flex flex-col h-full">
                        {{-- thumbnail --}}
                        <div class="h-40 bg-gray-100 rounded overflow-hidden mb-4">
                            @if($prod->image)
                                <img src="{{ asset('storage/'.$prod->image) }}"
                                     alt="{{ $prod->name }}"
                                     class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    No Image
                                </div>
                            @endif
                        </div>

                        {{-- details --}}
                        <h4 class="font-semibold">{{ $prod->name }}</h4>
                        <p class="text-gray-600 text-sm mb-2">Price: ${{ number_format($prod->price,2) }}</p>
                        <p class="text-lg font-bold mb-4">Stock: {{ $prod->inventory }}</p>

                        {{-- adjust controls --}}
                        <div class="mt-auto space-y-2">
                            <div class="flex space-x-2 justify-center">
                                <form method="POST" action="{{ route('admin.products.adjust',$prod) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="delta" value="-1">
                                    <button type="submit" class="btn-secondary px-2">−1</button>
                                </form>
                                <form method="POST" action="{{ route('admin.products.adjust',$prod) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="delta" value="1">
                                    <button type="submit" class="btn-secondary px-2">+1</button>
                                </form>
                            </div>
                            <form method="POST" action="{{ route('admin.products.adjust',$prod) }}" class="flex space-x-2">
                                @csrf @method('PATCH')
                                <input
                                    type="number"
                                    name="delta"
                                    placeholder="+10 or -5"
                                    class="w-full border rounded p-1"
                                />
                                <button type="submit" class="btn-secondary px-3">Apply</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12 text-gray-600">
                        No products in inventory.
                    </div>
                @endforelse

                {{-- “Add Product” card --}}
                <div
                    class="card flex flex-col items-center justify-center cursor-pointer hover:bg-gray-50 h-full"
                    @click="openModal('inventory-create')"
                >
                    <span class="text-5xl text-gray-400">＋</span>
                    <span class="mt-2 text-gray-500">Add Product</span>
                </div>
            </div>

            {{-- pagination --}}
            <div class="mt-6">
                {{ $products->links() }}
            </div>
        </div>

        <x-modal name="inventory-create" maxWidth="lg">
            <form
                method="POST"
                action="{{ route('admin.products.store') }}"
                enctype="multipart/form-data"
                class="p-6 space-y-4"
            >
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Name --}}
                    <div>
                        <x-input-label for="name" value="Name"/>
                        <x-text-input id="name" name="name" class="mt-1 block w-full"/>
                        <x-input-error :messages="$errors->get('name')" class="mt-1"/>
                    </div>

                    {{-- Price --}}
                    <div>
                        <x-input-label for="price" value="Price"/>
                        <x-text-input id="price" name="price" class="mt-1 block w-full"/>
                        <x-input-error :messages="$errors->get('price')" class="mt-1"/>
                    </div>

                    {{-- Inventory --}}
                    <div>
                        <x-input-label for="inventory" value="Starting Inventory"/>
                        <x-text-input id="inventory" name="inventory" type="number" class="mt-1 block w-full"/>
                        <x-input-error :messages="$errors->get('inventory')" class="mt-1"/>
                    </div>

                    {{-- Image Upload --}}
                    <div>
                        <x-input-label for="image" value="Product Image"/>
                        <input
                            id="image"
                            name="image"
                            type="file"
                            accept="image/*"
                            class="mt-1 block w-full border rounded p-1"
                        />
                        <x-input-error :messages="$errors->get('image')" class="mt-1"/>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <x-secondary-button @click="$dispatch('close-modal','inventory-create')" type="button">
                        Cancel
                    </x-secondary-button>
                    <x-primary-button>Create</x-primary-button>
                </div>
            </form>
        </x-modal>

        {{-- 4. CUSTOMER INSIGHTS --}}
        <div class="card mb-8">
            <h3 class="font-bold mb-2">New Registrations Today: {{ $newCustomersToday }}</h3>
            <h4 class="font-bold mb-2">Top Customers by Lifetime Spend</h4>
            <ul class="space-y-1">
                @foreach($topCustomers as $user)
                    <li>{{ $user->name }} (${{ number_format($user->lifetime_spend,2) }})</li>
                @endforeach
            </ul>
        </div>

        {{-- 5. PROMOTIONS --}}
        <div class="card mb-8">
            <h3 class="font-bold mb-4">Active Coupons</h3>
            @if(session('success'))
                <div class="p-2 mb-4 bg-green-100 text-green-700 rounded">{{ session('success') }}</div>
            @endif
            <form action="{{ route('admin.promo.store') }}" method="POST" class="border p-4 rounded mb-4">
                @csrf
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    <input name="code" placeholder="Code" class="border rounded p-1" required>
                    <select name="type" class="border rounded p-1">
                        <option value="fixed">Fixed</option>
                        <option value="percent">Percent</option>
                    </select>
                    <input name="discount" type="number" step="0.01" placeholder="Discount" class="border rounded p-1" required>
                    <input name="max_uses" type="number" placeholder="Max Uses" class="border rounded p-1">
                    <input name="expires_at" type="date" class="border rounded p-1">
                    <label class="flex items-center space-x-1">
                        <input type="checkbox" name="active" checked>
                        <span>Active</span>
                    </label>
                </div>
                <button type="submit" class="btn-primary mt-2">Create Coupon</button>
            </form>
            <ul class="space-y-1">
                @forelse($activeCoupons as $coupon)
                    <li class="flex justify-between items-center">
                        <div>
                            <strong>{{ $coupon->code }}</strong>
                            ({{ $coupon->type }} {{ $coupon->discount }},
                            {{ $coupon->max_uses ?? '∞' }} uses,
                            expires {{ optional($coupon->expires_at)->format('M j') ?? 'Never' }})
                        </div>
                        <div class="space-x-1">
                            <form action="{{ route('admin.promo.update', $coupon) }}" method="POST" class="inline">
                                @csrf @method('PUT')
                                <input name="discount" type="number" step="0.01" value="{{ $coupon->discount }}" class="w-16 border rounded p-1">
                                <button class="text-sm text-indigo-600 hover:underline">Update</button>
                            </form>
                            <form action="{{ route('admin.promo.destroy', $coupon) }}" method="POST" class="inline" onsubmit="return confirm('Delete {{ $coupon->code }}?');">
                                @csrf @method('DELETE')
                                <button class="text-sm text-red-600 hover:underline">Delete</button>
                            </form>
                        </div>
                    </li>
                @empty
                    <li class="text-gray-500 italic">No active coupons</li>
                @endforelse
            </ul>
            <h4 class="font-bold mt-4 mb-2">Expiring Soon</h4>
            <ul class="space-y-1">
                @forelse($expiringCoupons as $coupon)
                    <li>{{ $coupon->code }} — expires {{ $coupon->expires_at->format('M j') }}</li>
                @empty
                    <li class="text-gray-500 italic">No coupons expiring soon</li>
                @endforelse
            </ul>
        </div>

        {{-- 6. DEV METRICS --}}
        <div x-show="devMetricsVisible" class="card mb-8">
            <h3 class="font-bold mb-4">Site Analytics</h3>
            {!! $analyticsHtml !!}
        </div>

        {{-- 7. SETTINGS --}}
        <div class="card mb-8">
            <h3 class="font-bold mb-2">Settings & Shortcuts</h3>
            <ul class="space-y-1">
                <li><a href="{{ route('admin.products.index') }}" class="text-indigo-600 hover:underline">Manage Products</a></li>
                <li><a href="{{ route('admin.orders') }}" class="text-indigo-600 hover:underline">View All Orders</a></li>
            </ul>
        </div>

    </div>
@endsection
