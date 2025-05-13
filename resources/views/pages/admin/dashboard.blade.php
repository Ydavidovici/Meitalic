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
                <div class="p-6">
                    <h3 class="text-2xl font-bold mb-4">{{ $label }} Details</h3>
                    <p class="text-gray-600">More detailed breakdown…</p>
                    <button class="btn-secondary mt-4" @click="$dispatch('close-modal','{{ $key }}')">Close</button>
                </div>
            </x-modal>
        @endforeach

        {{-- 2. ORDER MANAGEMENT --}}
        <div class="card mb-8">
            <h3 class="font-bold mb-4 flex justify-between items-center">
                <span>Order Management</span>
                <div class="space-x-2">
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded">Pending: {{ $counts['pending'] }}</span>
                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded">Unfulfilled: {{ $counts['unfulfilled'] }}</span>
                    <button @click="markBulk('shipped')" class="text-sm text-blue-600 hover:underline">Mark Selected Shipped</button>
                    <button @click="markBulk('delivered')" class="text-sm text-green-600 hover:underline">Mark Selected Delivered</button>
                </div>
            </h3>

            <form id="orders-filters-form" class="mb-4 flex flex-wrap items-center space-x-2">
                <label for="status" class="font-medium">Status:</label>
                <select name="status" id="status" class="border rounded px-2 py-1">
                    @foreach($allStatuses as $st)
                        <option value="{{ $st }}" @selected(request('status','all') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
                <label for="order_number" class="font-medium">Order #:</label>
                <input type="text" name="order_number" id="order_number" value="{{ request('order_number') }}" placeholder="e.g. 1234" class="border rounded px-2 py-1"/>
                <label for="min_amount" class="font-medium">Min $:</label>
                <input type="number" name="min_amount" id="min_amount" step="0.01" value="{{ request('min_amount') }}" class="border rounded px-2 py-1 w-20"/>
                <label for="max_amount" class="font-medium">Max $:</label>
                <input type="number" name="max_amount" id="max_amount" step="0.01" value="{{ request('max_amount') }}" class="border rounded px-2 py-1 w-20"/>
                <button type="submit" class="btn-secondary whitespace-nowrap">Filter</button>
            </form>

            <div id="orders-grid">
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
                        <tr class="border-t">
                            <td><input type="checkbox" value="{{ $order->id }}" x-model="selectedOrders"/></td>
                            <td>{{ $order->id }}</td>
                            <td>{{ optional($order->user)->name ?? 'Guest' }}</td>
                            <td><span class="px-2 py-1 bg-gray-100 rounded text-sm">{{ ucfirst($order->status) }}</span></td>
                            <td>{{ $order->created_at->format('M j, Y') }}</td>
                            <td class="space-x-2">
                                <button @click="singleMark({{ $order->id }}, 'shipped')" class="text-sm text-blue-600 hover:underline">Mark Shipped</button>
                                <button @click="singleMark({{ $order->id }}, 'delivered')" class="text-sm text-green-600 hover:underline">Mark Delivered</button>
                                <button @click.stop="openOrderEdit({{ $order->id }})" class="text-sm text-indigo-600 hover:underline">Edit</button>

                                {{-- NEW: Approve / Reject Return --}}
                                @if($order->status === 'pending_return')
                                    <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="returned">
                                        <button class="text-sm text-green-600 hover:underline">Approve Return</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="return_rejected">
                                        <button class="text-sm text-red-600 hover:underline">Reject Return</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="mt-4">{{ $recentOrders->links() }}</div>
            </div>
        </div>

        {{-- 2.b) Order‑Edit Modal --}}
        <x-modal name="order-edit" maxWidth="lg">
            <div class="p-6" x-show="selectedOrder">
                <h3 class="text-2xl font-bold mb-4">Edit Order #<span x-text="selectedOrder.id"></span></h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block font-medium">Status</label>
                        <select x-model="selectedOrder.status" class="border rounded px-3 py-2 w-full">
                            <template x-for="st in ['pending','shipped','delivered','unfulfilled','canceled','returned']" :key="st">
                                <option :value="st" x-text="st.charAt(0).toUpperCase() + st.slice(1)"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium">Date</label>
                        <input type="text" readonly x-model="selectedOrder.created_at" class="border rounded px-3 py-2 w-full bg-gray-100"/>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-medium">Customer</label>
                        <input type="text" readonly x-model="selectedOrder.user?.name ?? 'Guest'" class="border rounded px-3 py-2 w-full bg-gray-100"/>
                    </div>
                    <div>
                        <label class="block font-medium">Shipping Address</label>
                        <textarea x-model="selectedOrder.shipping_address" rows="2" class="border rounded p-2 w-full"></textarea>
                    </div>
                    <div>
                        <label class="block font-medium">Email</label>
                        <input type="email" x-model="selectedOrder.email" class="border rounded px-3 py-2 w-full"/>
                    </div>
                    <div>
                        <label class="block font-medium">Phone</label>
                        <input type="text" x-model="selectedOrder.phone" class="border rounded px-3 py-2 w-full"/>
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
                            <td class="px-2 py-1"><input type="number" min="1" x-model.number="item.quantity" class="w-16 border rounded px-1"/></td>
                            <td class="px-2 py-1"><input type="number" step="0.01" min="0" x-model.number="item.price" class="w-20 border rounded px-1"/></td>
                            <td class="px-2 py-1">$<span x-text="(item.quantity * item.price).toFixed(2)"></span></td>
                        </tr>
                    </template>
                    </tbody>
                </table>

                <div class="mb-6">
                    <label class="font-medium">Order Total</label>
                    <input type="number" step="0.01" x-model="selectedOrder.total" class="border rounded px-3 py-2 w-32"/>
                </div>

                <div class="flex justify-end space-x-2">
                    <button class="btn-secondary" @click="$dispatch('close-modal','order-edit')">Cancel</button>
                    <button class="btn-primary" @click="updateOrder()">Save Changes</button>
                </div>
            </div>
        </x-modal>

        {{-- 2.c) REVIEW MANAGEMENT --}}
        <div class="card mb-8 bg-white rounded shadow p-6" x-data="{ tab:'pending' }">
            <h3 class="font-bold mb-4">Review Management</h3>

            {{-- tabs --}}
            <ul class="flex space-x-4 mb-4 border-b">
                @foreach(['pending','approved','rejected'] as $st)
                    <li>
                        <button
                            @click="tab='{{ $st }}'"
                            :class="tab==='{{ $st }}'
                   ? 'border-b-2 border-indigo-600 text-indigo-600'
                   : 'text-gray-600 hover:text-gray-800'"
                            class="pb-1"
                        >
                            {{ ucfirst($st) }} ({{ $reviewCounts[$st] }})
                        </button>
                    </li>
                @endforeach
            </ul>

            {{-- content sections --}}
            @foreach(['pending','approved','rejected'] as $st)
                <div x-show="tab==='{{ $st }}'">
                    @php
                        $list = ${$st . 'Reviews'};
                    @endphp

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
                                        <a href="{{ route('products.show',$r->product->slug) }}"
                                           class="hover:underline">
                                            {{ $r->product->name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-2">{{ $r->user->name }}</td>
                                    <td class="px-4 py-2">{{ $r->rating }} ★</td>
                                    <td class="px-4 py-2">{{ Str::limit($r->body, 50) }}</td>
                                    <td class="px-4 py-2 space-x-2">
                                        @if($st === 'pending')
                                            <form method="POST" action="{{ route('admin.reviews.approve',$r) }}" class="inline">
                                                @csrf @method('PATCH')
                                                <button class="text-green-600 hover:underline">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.reviews.reject',$r) }}" class="inline">
                                                @csrf @method('PATCH')
                                                <button class="text-red-600 hover:underline">Reject</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.reviews.destroy',$r) }}" class="inline">
                                                @csrf @method('DELETE')
                                                <button class="text-red-600 hover:underline">Delete</button>
                                            </form>
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

        {{-- 3. INVENTORY MANAGEMENT --}}
        <div class="card mb-8 bg-white rounded shadow">
            <div class="p-6 flex items-center justify-between">
                <h3 class="text-xl font-bold">Inventory</h3>
                <button @click="openModal('inventory-create')" class="btn-primary whitespace-nowrap">+ New Product</button>
            </div>

            {{-- 3.a Filters --}}
            <form id="admin-filters-form" class="grid grid-cols-1 md:grid-cols-7 gap-4 px-6 mb-6 items-center">
                <input type="text" name="q" placeholder="Search products…" value="{{ request('q') }}" class="col-span-1 md:col-span-2 border rounded px-3 py-2"/>
                <select name="brand" class="border rounded px-3 py-2">
                    <option value="">All Brands</option>
                    @foreach($allBrands as $brand)
                        <option value="{{ $brand }}" @selected(request('brand')==$brand)>{{ $brand }}</option>
                    @endforeach
                </select>
                <select name="category" class="border rounded px-3 py-2">
                    <option value="">All Categories</option>
                    @foreach($allCategories as $cat)
                        <option value="{{ $cat }}" @selected(request('category')==$cat)>{{ $cat }}</option>
                    @endforeach
                </select>
                <select name="featured" class="border rounded px-3 py-2">
                    <option value="">All Products</option>
                    <option value="1" @selected(request('featured')==='1')>Featured Only</option>
                    <option value="0" @selected(request('featured')==='0')>Non‑Featured Only</option>
                </select>
                <select name="sort" class="border rounded px-3 py-2">
                    <option value="updated_at" @selected(request('sort')=='updated_at')>Last Updated</option>
                    <option value="inventory"   @selected(request('sort')=='inventory')>Stock</option>
                    <option value="name"        @selected(request('sort')=='name')>Name</option>
                </select>
                <select name="dir" class="border rounded px-3 py-2">
                    <option value="desc" @selected(request('dir')=='desc')>Desc</option>
                    <option value="asc"  @selected(request('dir')=='asc')>Asc</option>
                </select>
                <button type="submit" class="btn-secondary whitespace-nowrap">Apply</button>
            </form>

            {{-- 3.b Grid + Modals --}}
            @include('partials.admin.product-grid')

            {{-- Create Product Modal --}}
            <x-modal name="inventory-create" maxWidth="lg">
                <form method="POST" action="{{ route('admin.products.store') }}"
                      enctype="multipart/form-data"
                      class="p-6 space-y-4"
                      @submit.prevent="validateAndSubmit($el)">
                    @csrf
                    <h3 class="text-2xl font-bold mb-4">Create New Product</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        {{-- Name --}}
                        <div>
                            <x-input-label for="name" value="Name"/>
                            <x-text-input id="name" name="name" required class="mt-1 w-full"/>
                        </div>
                        {{-- Brand --}}
                        <div>
                            <x-input-label for="brand" value="Brand"/>
                            <x-text-input id="brand" name="brand" required class="mt-1 w-full"/>
                        </div>
                        {{-- Category --}}
                        <div>
                            <x-input-label for="category" value="Category"/>
                            <x-text-input id="category" name="category" required class="mt-1 w-full"/>
                        </div>
                        {{-- Price --}}
                        <div>
                            <x-input-label for="price" value="Price"/>
                            <x-text-input id="price" name="price" type="number" step="0.01" required class="mt-1 w-full"/>
                        </div>
                        {{-- Inventory --}}
                        <div>
                            <x-input-label for="inventory" value="Starting Inventory"/>
                            <x-text-input id="inventory" name="inventory" type="number" required class="mt-1 w-full"/>
                        </div>
                        {{-- Description --}}
                        <div class="md:col-span-2">
                            <x-input-label for="description" value="Description"/>
                            <textarea id="description" name="description" rows="4"
                                      required class="mt-1 w-full border rounded p-2">{{ old('description') }}</textarea>
                        </div>
                        {{-- Image --}}
                        <div>
                            <x-input-label for="image" value="Product Image"/>
                            <input id="image" name="image" type="file" accept="image/*"
                                   class="mt-1 w-full border rounded p-1"/>
                        </div>
                    </div>
                    {{-- Featured toggle --}}
                    <div class="flex items-center space-x-2 mb-4">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1"
                               @checked(old('is_featured', false)) class="form-checkbox"/>
                        <label for="is_featured" class="ml-1">Featured</label>
                    </div>
                    <div class="flex justify-end space-x-4">
                        <x-secondary-button type="button" @click="$dispatch('close-modal','inventory-create')">Cancel</x-secondary-button>
                        <x-primary-button type="submit">Create</x-primary-button>
                    </div>
                </form>
            </x-modal>
        </div>

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
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-lg">Promo Codes</h3>
                <button @click="openModal('promo-create')" class="btn-primary">+ New Promo</button>
            </div>
            <div class="space-y-2">
                @forelse($activePromos as $promo)
                    <div class="flex items-center justify-between border p-4 rounded">
                        <div>
                            <span class="font-mono">{{ $promo->code }}</span>
                            @if($promo->type==='percent') — {{ $promo->discount }}% @else — ${{ number_format($promo->discount,2) }} @endif
                            @if($promo->expires_at)<span class="text-sm text-gray-500">(expires {{ $promo->expires_at->format('M j, Y') }})</span>@endif
                        </div>
                        <div class="space-x-2">
                            <button @click="openModal('promo-edit-{{ $promo->id }}')" class="text-indigo-600 hover:underline text-sm">Edit</button>
                            <form action="{{ route('admin.promo.destroy',$promo) }}" method="POST" class="inline" onsubmit="return confirm('Delete promo {{ $promo->code }}?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-600">No promo codes defined.</p>
                @endforelse
            </div>
        </div>

        {{-- Create Promo Modal --}}
        <x-modal name="promo-create" maxWidth="md">
            <form method="POST" action="{{ route('admin.promo.store') }}" class="p-6 space-y-4" @submit.prevent="validateAndSubmit($el)">
                @csrf
                <h3 class="text-xl font-bold mb-4">New Promo Code</h3>
                <div>
                    <label class="block font-medium">Code</label>
                    <input name="code" value="{{ old('code') }}" required class="w-full border rounded px-3 py-2"/>
                    @error('code') <p class="text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block font-medium">Type</label>
                    <select name="type" required class="w-full border rounded px-3 py-2">
                        <option value="fixed"   @selected(old('type')=='fixed')>Fixed</option>
                        <option value="percent" @selected(old('type')=='percent')>Percent</option>
                    </select>
                    @error('type') <p class="text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block font-medium">Discount</label>
                    <input name="discount" type="text" inputmode="decimal" value="{{ old('discount') }}" required class="w-full border rounded px-3 py-2"/>
                    @error('discount') <p class="text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block font-medium">Max Uses <span class="text-sm text-gray-500">(leave blank for unlimited)</span></label>
                    <input name="max_uses" type="text" inputmode="numeric" pattern="\d*" value="{{ old('max_uses') }}" class="w-full border rounded px-3 py-2"/>
                    @error('max_uses') <p class="text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block font-medium">Expires At</label>
                    <input name="expires_at" type="date" value="{{ old('expires_at') }}" class="w-full border rounded px-3 py-2"/>
                    @error('expires_at') <p class="text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="active" value="1" @checked(old('active',true)) class="form-checkbox"/>
                        <span class="ml-2">Active</span>
                    </label>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" class="btn-secondary" @click="$dispatch('close-modal','promo-create')">Cancel</button>
                    <button type="submit" class="btn-primary">Create Promo</button>
                </div>
            </form>
        </x-modal>

        {{-- Edit Promo Modals --}}
        @foreach($activePromos as $promo)
            <x-modal name="promo-edit-{{ $promo->id }}" maxWidth="md">
                <form method="POST" action="{{ route('admin.promo.update',$promo) }}" class="p-6 space-y-4" @submit.prevent="validateAndSubmit($el)">
                    @csrf @method('PUT')
                    <h3 class="text-xl font-bold mb-4">Edit Promo “{{ $promo->code }}”</h3>
                    <div>
                        <label class="block font-medium">Code</label>
                        <input name="code" value="{{ old('code',$promo->code) }}" required class="w-full border rounded px-3 py-2"/>
                        @error('code') <p class="text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block font-medium">Type</label>
                        <select name="type" required class="w-full border rounded px-3 py-2">
                            <option value="fixed"   @selected(old('type',$promo->type)=='fixed')>Fixed</option>
                            <option value="percent" @selected(old('type',$promo->type)=='percent')>Percent</option>
                        </select>
                        @error('type') <p class="text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block font-medium">Discount</label>
                        <input name="discount" type="number" step="0.01" value="{{ old('discount',$promo->discount) }}" required class="w-full border rounded px-3 py-2"/>
                        @error('discount') <p class="text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block font-medium">Max Uses</label>
                        <input name="max_uses" type="number" min="1" value="{{ old('max_uses',$promo->max_uses) }}" class="w-full border rounded px-3 py-2"/>
                        @error('max_uses') <p class="text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block font-medium">Used Count</label>
                        <input name="used_count" type="number" min="0" value="{{ old('used_count',$promo->used_count) }}" required class="w-full border rounded px-3 py-2"/>
                        @error('used_count') <p class="text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block font-medium">Expires At</label>
                        <input name="expires_at" type="date" value="{{ old('expires_at',$promo->expires_at?->format('Y-m-d')) }}" class="w-full border rounded px-3 py-2"/>
                        @error('expires_at') <p class="text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="active" value="1" @checked(old('active',$promo->active)) class="form-checkbox"/>
                            <span class="ml-2">Active</span>
                        </label>
                    </div>
                    <div class="flex justify-end space-x-4">
                        <button type="button" class="btn-secondary" @click="$dispatch('close-modal','promo-edit-{{ $promo->id }}')">Cancel</button>
                        <button type="submit" class="btn-primary">Save Changes</button>
                    </div>
                </form>
            </x-modal>
        @endforeach

        {{-- 6. DEV METRICS --}}
        <div x-show="devMetricsVisible" class="card mb-8">{!! $analyticsHtml !!}</div>

        {{-- 7. SETTINGS --}}
        <div class="card mb-8">
            <ul class="space-y-1">
                <li><a href="{{ route('admin.products.index') }}" class="text-indigo-600 hover:underline">Manage Products</a></li>
                <li><a href="{{ route('admin.orders') }}"      class="text-indigo-600 hover:underline">View All Orders</a></li>
            </ul>
        </div>

    </div>
@endsection
