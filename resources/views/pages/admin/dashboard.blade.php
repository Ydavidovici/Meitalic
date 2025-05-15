{{-- resources/views/pages/admin/dashboard.blade.php --}}
@php use Illuminate\Support\Str; @endphp

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
                <div class="card cursor-pointer" @click="openModal('{{ $key }}')">
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
                <x-slot name="title">{{ $label }} Details</x-slot>
                <p class="text-gray-600">More detailed breakdown…</p>
                <x-slot name="footer">
                    <button @click="$dispatch('close-modal','{{ $key }}')" class="btn-secondary">Close</button>
                </x-slot>
            </x-modal>
        @endforeach

        {{-- 2. ORDER MANAGEMENT --}}
        <div class="card mb-8">
            <h3 class="font-bold mb-4 flex justify-between items-center">
                <span>Order Management</span>
                <div class="space-x-2">
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded">Pending: {{ $counts['pending'] }}</span>
                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded">Unfulfilled: {{ $counts['unfulfilled'] }}</span>
                    <button @click="markBulk('shipped')"   class="text-sm text-blue-600 hover:underline">Mark Selected Shipped</button>
                    <button @click="markBulk('delivered')" class="text-sm text-green-600 hover:underline">Mark Selected Delivered</button>
                </div>
            </h3>

            {{-- Filters --}}
            <x-form
                id="orders-filters-form"
                method="GET"
                action="{{ route('admin.orders') }}"
                class="mb-4 flex flex-wrap items-center space-x-2"
            >
                <label for="status" class="font-medium">Status:</label>
                <select name="status" id="status" class="form-select">
                    @foreach($allStatuses as $st)
                        <option value="{{ $st }}" @selected(request('status','all') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>

                <label for="order_number" class="font-medium">Order #:</label>
                <input
                    type="text"
                    name="order_number"
                    id="order_number"
                    value="{{ request('order_number') }}"
                    placeholder="e.g. 1234"
                    class="form-input w-32"
                />

                <label for="min_amount" class="font-medium">Min $:</label>
                <input
                    type="number"
                    name="min_amount"
                    id="min_amount"
                    step="0.01"
                    value="{{ request('min_amount') }}"
                    class="form-input w-20"
                />

                <label for="max_amount" class="font-medium">Max $:</label>
                <input
                    type="number"
                    name="max_amount"
                    id="max_amount"
                    step="0.01"
                    value="{{ request('max_amount') }}"
                    class="form-input w-20"
                />

                <button type="submit" class="btn-secondary whitespace-nowrap">Filter</button>
            </x-form>

            {{-- Orders Table --}}
            <div id="orders-grid">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100">
                    <tr>
                        <th><input type="checkbox" @click="toggleAll" /></th>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($recentOrders as $order)
                        <tr class="border-t">
                            <td><input type="checkbox" value="{{ $order->id }}" x-model="selectedOrders" /></td>
                            <td>{{ $order->id }}</td>
                            <td>{{ optional($order->user)->name ?? 'Guest' }}</td>
                            <td>
                                <span class="px-2 py-1 bg-gray-100 rounded text-sm">{{ ucfirst($order->status) }}</span>
                            </td>
                            <td>{{ $order->created_at->format('M j, Y') }}</td>
                            <td class="space-x-2">
                                <button @click="singleMark({{ $order->id }}, 'shipped')"   class="text-sm text-blue-600 hover:underline">Mark Shipped</button>
                                <button @click="singleMark({{ $order->id }}, 'delivered')" class="text-sm text-green-600 hover:underline">Mark Delivered</button>
                                <button @click.stop="openOrderEdit({{ $order->id }})"       class="text-sm text-indigo-600 hover:underline">Edit</button>

                                @if($order->status === 'pending_return')
                                    <x-form
                                        method="PATCH"
                                        action="{{ route('admin.orders.updateStatus', $order) }}"
                                        class="inline"
                                    >
                                        <input type="hidden" name="status" value="returned">
                                        <button class="text-sm text-green-600 hover:underline">Approve Return</button>
                                    </x-form>

                                    <x-form
                                        method="PATCH"
                                        action="{{ route('admin.orders.updateStatus', $order) }}"
                                        class="inline"
                                    >
                                        <input type="hidden" name="status" value="return_rejected">
                                        <button class="text-sm text-red-600 hover:underline">Reject Return</button>
                                    </x-form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <div class="mt-4">{{ $recentOrders->links() }}</div>
            </div>
        </div>

        {{-- Edit Order Modal --}}
        <x-modal name="order-edit" maxWidth="lg" focusable>
            <x-slot name="title">Edit Order #<span x-text="selectedOrder.id"></span></x-slot>
            <template x-if="selectedOrder">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block font-medium">Status</label>
                            <select x-model="selectedOrder.status" class="form-select w-full">
                                <template x-for="st in ['pending','shipped','delivered','unfulfilled','canceled','returned']" :key="st">
                                    <option :value="st" x-text="st.charAt(0).toUpperCase()+st.slice(1)"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium">Date</label>
                            <input type="text" readonly x-model="selectedOrder.created_at"
                                   class="form-input bg-gray-100" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block font-medium">Customer</label>
                            <input type="text" readonly x-model="selectedOrder.user?.name ?? 'Guest'"
                                   class="form-input bg-gray-100" />
                        </div>
                        <div>
                            <label class="block font-medium">Shipping Address</label>
                            <textarea x-model="selectedOrder.shipping_address" rows="2" class="form-textarea"></textarea>
                        </div>
                        <div>
                            <label class="block font-medium">Email</label>
                            <input type="email" x-model="selectedOrder.email" class="form-input" />
                        </div>
                        <div>
                            <label class="block font-medium">Phone</label>
                            <input type="text" x-model="selectedOrder.phone" class="form-input" />
                        </div>
                    </div>

                    <h4 class="font-semibold mb-2">Items</h4>
                    <table class="w-full mb-6 border-collapse">
                        <thead class="bg-gray-100">
                        <tr>
                            <th class="px-2 py-1 text-left">Product</th>
                            <th class="px-2 py-1 text-left">Qty</th>
                            <th class="px-2 py-1 text-left">Price</th>
                            <th class="px-2 py-1 text-left">Line Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <template x-for="item in selectedOrder.items" :key="item.id">
                            <tr class="border-t">
                                <td x-text="item.name" class="px-2 py-1"></td>
                                <td class="px-2 py-1">
                                    <input type="number" min="1" x-model.number="item.quantity"
                                           class="form-input w-16 p-1" />
                                </td>
                                <td class="px-2 py-1">
                                    <input type="number" step="0.01" min="0" x-model.number="item.price"
                                           class="form-input w-20 p-1" />
                                </td>
                                <td class="px-2 py-1">$<span x-text="(item.quantity*item.price).toFixed(2)"></span></td>
                            </tr>
                        </template>
                        </tbody>
                    </table>

                    <div class="mb-6">
                        <label class="font-medium">Order Total</label>
                        <input type="number" step="0.01" x-model="selectedOrder.total"
                               class="form-input w-32" />
                    </div>
                </div>
            </template>
            <x-slot name="footer">
                <button @click="$dispatch('close-modal','order-edit')" class="btn-secondary">Cancel</button>
                <button @click="updateOrder()" class="btn-primary">Save Changes</button>
            </x-slot>
        </x-modal>

        {{-- Review Management --}}
        <div class="card mb-8 bg-white rounded shadow p-6" x-data="{ tab:'pending' }">
            <h3 class="font-bold mb-4">Review Management</h3>
            <ul class="flex space-x-4 mb-4 border-b">
                @foreach(['pending','approved','rejected'] as $st)
                    <li>
                        <button
                            @click="tab='{{ $st }}'"
                            :class="tab==='{{ $st }}' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-600 hover:text-gray-800'"
                            class="pb-1"
                        >{{ ucfirst($st) }} ({{ $reviewCounts[$st] }})</button>
                    </li>
                @endforeach
            </ul>
            @foreach(['pending','approved','rejected'] as $st)
                <div x-show="tab==='{{ $st }}'">
                    @php $list = ${$st.'Reviews'}; @endphp
                    @if($list->isEmpty())
                        <p class="text-gray-600">No {{ $st }} reviews.</p>
                    @else
                        <table class="w-full text-left border-collapse mb-4">
                            <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2">Product</th>
                                <th class="px-4 py-2">User</th>
                                <th class="px-4 py-2">Rating</th>
                                <th class="px-4 py-2">Comment</th>
                                <th class="px-4 py-2">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($list as $r)
                                <tr class="border-t">
                                    <td class="px-4 py-2">
                                        <a href="{{ route('products.show',$r->product->slug) }}" class="hover:underline">
                                            {{ $r->product->name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-2">{{ $r->user->name }}</td>
                                    <td class="px-4 py-2">{{ $r->rating }} ★</td>
                                    <td class="px-4 py-2">{{ Str::limit($r->body, 50) }}</td>
                                    <td class="px-4 py-2 space-x-2">
                                        @if($st === 'pending')
                                            <x-form method="PATCH" action="{{ route('admin.reviews.approve',$r) }}" class="inline">
                                                <button class="text-green-600 hover:underline">Approve</button>
                                            </x-form>
                                            <x-form method="PATCH" action="{{ route('admin.reviews.reject',$r) }}" class="inline">
                                                <button class="text-red-600 hover:underline">Reject</button>
                                            </x-form>
                                        @else
                                            <x-form method="DELETE" action="{{ route('admin.reviews.destroy',$r) }}" class="inline">
                                                <button class="text-red-600 hover:underline">Delete</button>
                                            </x-form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Inventory Management --}}
        <div class="inventory-section mb-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Inventory</h3>
                <button @click="openModal('inventory-create')" class="btn-primary">+ New Product</button>
            </div>

            {{-- Filters --}}
            <x-form
                id="admin-filters-form"
                method="GET"
                action="{{ route('admin.products.index') }}"
                class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6"
            >
                <input
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search products…"
                    class="form-input col-span-2"
                />
                <select name="brand" class="form-select">
                    <option value="">All Brands</option>
                    {{-- … --}}
                </select>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    {{-- … --}}
                </select>
                <select name="featured" class="form-select">
                    <option value="">Featured?</option>
                    {{-- … --}}
                </select>
                <select name="sort" class="form-select">
                    <option value="">Sort By</option>
                    {{-- … --}}
                </select>
                <select name="dir" class="form-select">
                    <option value="">Direction</option>
                    {{-- … --}}
                </select>
                <button type="submit" class="btn-secondary col-span-full">Apply</button>
            </x-form>

            {{-- Product Grid --}}
            @include('partials.admin.product-grid')

            {{-- Create Product Modal --}}
            <x-modal name="inventory-create" maxWidth="lg" focusable>
                <x-slot name="title">Create New Product</x-slot>
                <x-form
                    id="inventory-create-form"
                    method="POST"
                    action="{{ route('admin.products.store') }}"
                    enctype="multipart/form-data"
                    @submit.prevent="validateAndSubmit($el)"
                    class="space-y-4 p-6"
                >
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" required class="form-input" />
                        </div>
                        <div>
                            <x-input-label for="brand" :value="__('Brand')" />
                            <x-text-input id="brand" name="brand" required class="form-input" />
                        </div>
                        <div>
                            <x-input-label for="category" :value="__('Category')" />
                            <x-text-input id="category" name="category" required class="form-input" />
                        </div>
                        <div>
                            <x-input-label for="price" :value="__('Price')" />
                            <x-text-input id="price" name="price" type="number" step="0.01" required class="form-input"/>
                        </div>
                        <div>
                            <x-input-label for="inventory" :value="__('Starting Inventory')" />
                            <x-text-input id="inventory" name="inventory" type="number" required class="form-input"/>
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="4" required class="form-textarea w-full">{{ old('description') }}</textarea>
                        </div>
                        <div>
                            <x-input-label for="image" :value="__('Product Image')" />
                            <input id="image" name="image" type="file" accept="image/*" class="form-input" />
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input id="is_featured" name="is_featured" type="checkbox" value="1" @checked(old('is_featured')) class="mr-2"/>
                        <label for="is_featured" class="font-medium">Featured</label>
                    </div>
                </x-form>
                <x-slot name="footer">
                    <button @click="$dispatch('close-modal','inventory-create')" class="btn-secondary">Cancel</button>
                    <button type="submit" form="inventory-create-form" class="btn-primary">Create</button>
                </x-slot>
            </x-modal>
        </div>

        {{-- Customer Insights --}}
        <div class="insights-card mb-8">
            <h3 class="text-lg font-semibold">New Registrations Today: {{ $newCustomersToday }}</h3>
            <h4 class="text-base font-medium mt-2">Top Customers by Lifetime Spend</h4>
            <ul class="list-disc pl-6 mt-2">
                @foreach($topCustomers as $user)
                    <li>{{ $user->name }} (${{ number_format($user->lifetime_spend,2) }})</li>
                @endforeach
            </ul>
        </div>

        {{-- Promotions --}}
        <div class="promotions-section mb-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Promo Codes</h3>
                <button @click="openModal('promo-create')" class="btn-primary">+ New Promo</button>
            </div>
            <div class="space-y-2">
                @forelse($activePromos as $promo)
                    <div class="flex justify-between items-center p-4 bg-gray-50 rounded">
                        <div>
                            <span class="font-medium">{{ $promo->code }}</span>
                            — {{ $promo->type==='percent' ? $promo->discount.'%' : '$'.number_format($promo->discount,2) }}
                            @if($promo->expires_at)
                                <span class="text-sm text-gray-500">(expires {{ $promo->expires_at->format('M j, Y') }})</span>
                            @endif
                        </div>
                        <div class="space-x-2">
                            <button @click="openModal('promo-edit-{{ $promo->id }}')" class="text-indigo-600 hover:underline">Edit</button>
                            <x-form method="DELETE" action="{{ route('admin.promo.destroy',$promo) }}" class="inline">
                                <button type="submit" onclick="return confirm('Delete promo {{ $promo->code }}?')" class="text-red-600 hover:underline">Delete</button>
                            </x-form>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-600">No promo codes defined.</p>
                @endforelse
            </div>
        </div>

        {{-- Create Promo Modal --}}
        <x-modal name="promo-create" maxWidth="md">
            <x-slot name="title">New Promo Code</x-slot>
            <x-form method="POST" action="{{ route('admin.promo.store') }}" @submit.prevent="validateAndSubmit($el)" class="space-y-4 p-6">
                <div class="form-group">
                    <label class="block font-medium">Code</label>
                    <input name="code" value="{{ old('code') }}" required class="form-input"/>
                    @error('code') <p class="text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="block font-medium">Type</label>
                    <select name="type" required class="form-select">
                        <option value="fixed"   @selected(old('type')=='fixed')>Fixed</option>
                        <option value="percent" @selected(old('type')=='percent')>Percent</option>
                    </select>
                    @error('type') <p class="text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="block font-medium">Discount</label>
                    <input name="discount" type="number" step="0.01" value="{{ old('discount') }}" required class="form-input"/>
                    @error('discount') <p class="text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="block font-medium">Max Uses</label>
                    <input name="max_uses" type="number" value="{{ old('max_uses') }}" class="form-input"/>
                    @error('max_uses') <p class="text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="block font-medium">Expires At</label>
                    <input name="expires_at" type="date" value="{{ old('expires_at') }}" class="form-input"/>
                    @error('expires_at') <p class="text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center">
                    <input id="promo-active" name="active" type="checkbox" value="1" @checked(old('active',true)) class="mr-2"/>
                    <label for="promo-active" class="font-medium">Active</label>
                </div>
            </x-form>
            <x-slot name="footer">
                <button @click="$dispatch('close-modal','promo-create')" class="btn-secondary">Cancel</button>
                <button type="submit" form="promo-create-form" class="btn-primary">Create</button>
            </x-slot>
        </x-modal>

        {{-- Edit Promo Modals --}}
        @foreach($activePromos as $promo)
            <x-modal name="promo-edit-{{ $promo->id }}" maxWidth="md">
                <x-slot name="title">Edit Promo “{{ $promo->code }}”</x-slot>
                <x-form method="PUT" action="{{ route('admin.promo.update',$promo) }}" @submit.prevent="validateAndSubmit($el)" class="space-y-4 p-6">
                    <div class="form-group">
                        <label class="block font-medium">Code</label>
                        <input name="code" value="{{ old('code',$promo->code) }}" required class="form-input"/>
                        @error('code') <p class="text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <!-- repeat fields as above with old($promo->…) -->
                </x-form>
                <x-slot name="footer">
                    <button @click="$dispatch('close-modal','promo-edit-{{ $promo->id }}')" class="btn-secondary">Cancel</button>
                    <button type="submit" form="promo-edit-{{ $promo->id }}" class="btn-primary">Save Changes</button>
                </x-slot>
            </x-modal>
        @endforeach

        {{-- 6. DEV METRICS --}}
        <div x-show="devMetricsVisible" class="metrics-card">
            {!! $analyticsHtml !!}
        </div>

        {{-- 7. SETTINGS --}}
        <div class="settings-card">
            <ul class="settings-list space-y-2">
                <li><a href="{{ route('admin.products.index') }}" class="settings-link">Manage Products</a></li>
                <li><a href="{{ route('admin.orders') }}" class="settings-link">View All Orders</a></li>
            </ul>
        </div>

    </div>

    @vite('resources/js/admin-dashboard.js')
@endsection
