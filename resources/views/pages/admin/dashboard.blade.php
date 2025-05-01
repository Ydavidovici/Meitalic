{{-- resources/views/pages/admin/dashboard.blade.php --}}
@php
    use Illuminate\Support\Str;
@endphp

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
                <div class="card cursor-pointer" @click="openKpi('{{ $key }}')">
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
                    {{-- insert charts/tables here --}}
                    <p class="text-gray-600">More detailed breakdown…</p>
                    <button class="btn-secondary mt-4"
                            @click="$dispatch('close-modal','{{ $key }}')">
                        Close
                    </button>
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
                        x-show="matchesDate('{{ $order->filterDate }}') && matchesStatus('{{ $order->status }}')">
                        <td><input type="checkbox" value="{{ $order->id }}" x-model="selectedOrders"/></td>
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

        {{-- 3. INVENTORY MANAGEMENT --}}
        <div class="card mb-8 bg-white rounded shadow">
            <div class="p-6">
                {{-- 3.1 Section Header --}}
                <h3 class="text-xl font-bold mb-4">Inventory</h3>

                {{-- 3.2 Filters Bar --}}
                <form id="admin-filters-form" class="flex flex-wrap items-center gap-4 mb-6">
                    {{-- Search (optional) --}}
                    <input
                        type="text"
                        name="q"
                        placeholder="Search products…"
                        value="{{ request('q') }}"
                        class="flex-1 min-w-[12rem] border rounded px-3 py-2"
                    >

                    {{-- Brand --}}
                    <select name="brand" class="w-48 border rounded px-3 py-2">
                        <option value="">All Brands</option>
                        @foreach($allBrands as $brand)
                            <option value="{{ $brand }}" @selected(request('brand') == $brand)>{{ $brand }}</option>
                        @endforeach
                    </select>

                    {{-- Category --}}
                    <select name="category" class="w-48 border rounded px-3 py-2">
                        <option value="">All Categories</option>
                        @foreach($allCategories as $cat)
                            <option value="{{ $cat }}" @selected(request('category') == $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>

                    {{-- Sort --}}
                    <select name="sort" class="w-40 border rounded px-3 py-2">
                        <option value="updated_at" @selected(request('sort')=='updated_at')>Last Updated</option>
                        <option value="inventory"   @selected(request('sort')=='inventory')>Stock</option>
                        <option value="name"        @selected(request('sort')=='name')>Name</option>
                    </select>

                    {{-- Direction --}}
                    <select name="dir" class="w-32 border rounded px-3 py-2">
                        <option value="desc" @selected(request('dir')=='desc')>Desc</option>
                        <option value="asc"  @selected(request('dir')=='asc')>Asc</option>
                    </select>

                    {{-- Apply Button --}}
                    <button type="submit" class="btn-secondary ml-auto whitespace-nowrap">
                        Apply
                    </button>
                </form>

                {{-- 3.3 Product Grid --}}
                @include('partials.admin.product-grid')
            </div>
        </div>

        {{-- Create Product Modal --}}
        <x-modal name="inventory-create" maxWidth="lg">
            <form method="POST"
                  action="{{ route('admin.products.store') }}"
                  enctype="multipart/form-data"
                  class="p-6 space-y-4">
                @csrf
                <h3 class="text-2xl font-bold">Create New Product</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="name" value="Name"/>
                        <x-text-input id="name" name="name" class="mt-1 block w-full"/>
                        <x-input-error :messages="$errors->get('name')" class="mt-1"/>
                    </div>
                    <div>
                        <x-input-label for="brand" value="Brand"/>
                        <x-text-input id="brand" name="brand" class="mt-1 block w-full"/>
                        <x-input-error :messages="$errors->get('brand')" class="mt-1"/>
                    </div>
                    <div>
                        <x-input-label for="category" value="Category"/>
                        <x-text-input id="category" name="category" class="mt-1 block w-full"/>
                        <x-input-error :messages="$errors->get('category')" class="mt-1"/>
                    </div>
                    <div>
                        <x-input-label for="price" value="Price"/>
                        <x-text-input id="price" name="price" type="number" step="0.01" class="mt-1 block w-full"/>
                        <x-input-error :messages="$errors->get('price')" class="mt-1"/>
                    </div>
                    <div>
                        <x-input-label for="inventory" value="Starting Inventory"/>
                        <x-text-input id="inventory" name="inventory" type="number" class="mt-1 block w-full"/>
                        <x-input-error :messages="$errors->get('inventory')" class="mt-1"/>
                    </div>
                    <div>
                        <x-input-label for="image" value="Product Image"/>
                        <input id="image" name="image" type="file" accept="image/*"
                               class="mt-1 block w-full border rounded p-1"/>
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

        {{-- Edit Product Modals --}}
        @foreach($products as $prod)
            <x-modal name="product-edit-{{ $prod->id }}" maxWidth="lg">
                <form method="POST"
                      action="{{ route('admin.products.update', $prod) }}"
                      enctype="multipart/form-data"
                      class="p-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <h3 class="text-2xl font-bold">Edit {{ $prod->name }}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Name --}}
                        <div>
                            <x-input-label for="name-{{ $prod->id }}" value="Name"/>
                            <x-text-input id="name-{{ $prod->id }}"
                                          name="name"
                                          value="{{ old('name', $prod->name) }}"
                                          class="mt-1 block w-full"/>
                            <x-input-error :messages="$errors->get('name')" class="mt-1"/>
                        </div>

                        {{-- Brand --}}
                        <div>
                            <x-input-label for="brand-{{ $prod->id }}" value="Brand"/>
                            <x-text-input id="brand-{{ $prod->id }}"
                                          name="brand"
                                          value="{{ old('brand', $prod->brand) }}"
                                          class="mt-1 block w-full"/>
                            <x-input-error :messages="$errors->get('brand')" class="mt-1"/>
                        </div>

                        {{-- Category --}}
                        <div>
                            <x-input-label for="category-{{ $prod->id }}" value="Category"/>
                            <x-text-input id="category-{{ $prod->id }}"
                                          name="category"
                                          value="{{ old('category', $prod->category) }}"
                                          class="mt-1 block w-full"/>
                            <x-input-error :messages="$errors->get('category')" class="mt-1"/>
                        </div>

                        {{-- Price --}}
                        <div>
                            <x-input-label for="price-{{ $prod->id }}" value="Price"/>
                            <x-text-input id="price-{{ $prod->id }}"
                                          name="price"
                                          type="number" step="0.01"
                                          value="{{ old('price', $prod->price) }}"
                                          class="mt-1 block w-full"/>
                            <x-input-error :messages="$errors->get('price')" class="mt-1"/>
                        </div>

                        {{-- Inventory --}}
                        <div>
                            <x-input-label for="inventory-{{ $prod->id }}" value="Inventory"/>
                            <x-text-input id="inventory-{{ $prod->id }}"
                                          name="inventory"
                                          type="number"
                                          value="{{ old('inventory', $prod->inventory) }}"
                                          class="mt-1 block w-full"/>
                            <x-input-error :messages="$errors->get('inventory')" class="mt-1"/>
                        </div>

                        {{-- Description --}}
                        <div class="md:col-span-2">
                            <x-input-label for="description-{{ $prod->id }}" value="Description"/>
                            <textarea id="description-{{ $prod->id }}"
                                      name="description"
                                      rows="4"
                                      class="mt-1 block w-full border rounded p-2">{{ old('description', $prod->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-1"/>
                        </div>

                        {{-- Image Preview & Upload --}}
                        <div>
                            <x-input-label for="image-{{ $prod->id }}" value="Product Image"/>
                            @if($prod->image)
                                <img
                                    src="{{ Str::startsWith($prod->image, ['http://','https://'])
                                     ? $prod->image
                                     : asset('storage/'.$prod->image) }}"
                                    alt="{{ $prod->name }}"
                                    class="w-32 h-32 object-cover rounded mb-2"
                                />
                            @endif
                            <input id="image-{{ $prod->id }}"
                                   name="image"
                                   type="file"
                                   accept="image/*"
                                   class="mt-1 block w-full"/>
                            <x-input-error :messages="$errors->get('image')" class="mt-1"/>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <x-secondary-button type="button"
                                            @click="$dispatch('close-modal','product-edit-{{ $prod->id }}')">
                            Cancel
                        </x-secondary-button>
                        <x-primary-button>Save Changes</x-primary-button>
                    </div>
                </form>
            </x-modal>
        @endforeach


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
            <!-- promotions unchanged… -->
        </div>

        {{-- 6. DEV METRICS --}}
        <div x-show="devMetricsVisible" class="card mb-8">
            {!! $analyticsHtml !!}
        </div>

        {{-- 7. SETTINGS --}}
        <div class="card mb-8">
            <ul class="space-y-1">
                <li><a href="{{ route('admin.products.index') }}" class="text-indigo-600 hover:underline">Manage Products</a></li>
                <li><a href="{{ route('admin.orders') }}" class="text-indigo-600 hover:underline">View All Orders</a></li>
            </ul>
        </div>

    </div>
@endsection
