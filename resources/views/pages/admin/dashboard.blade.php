{{-- resources/views/pages/admin/dashboard.blade.php --}}
@php use Illuminate\Support\Str; @endphp

@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')

    <div x-data="adminDashboard()" class="admin-dashboard">

        {{-- 1. KPI CARDS --}}
        <div class="kpi-section">
            @foreach([
              ['orders-today',    'Orders Today',      $kpis['orders_today']],
              ['orders-week',     'Orders This Week',  $kpis['orders_week']],
              ['orders-month',    'Orders This Month', $kpis['orders_month']],
              ['revenue-today',   'Revenue Today',     '$'.number_format($kpis['revenue_today'],2)],
              ['revenue-week',    'Revenue This Week', '$'.number_format($kpis['revenue_week'],2)],
              ['revenue-month',   'Revenue This Month','$'.number_format($kpis['revenue_month'],2)],
              ['avg-order-value', 'Avg. Order Value',  '$'.number_format($kpis['avg_order_value'],2)],
            ] as [$key, $label, $value])
                <div class="kpi-card" @click="openModal('{{ $key }}')">
                    <h4 class="kpi-card__label">{{ $label }}</h4>
                    <p class="kpi-card__value">{{ $value }}</p>
                </div>
            @endforeach
        </div>



        {{-- 2. ORDER MANAGEMENT --}}
        <div class="order-management">

            <div class="order-management__header grid grid-cols-4 grid-rows-2 gap-1 items-center">
                {{-- 1) Header spans both rows in column 1 --}}
                <h3 class="col-start-1 row-span-2 font-bold">Order Management</h3>

                {{-- 2) Row 1, cols 2–4 --}}
                <button
                    type="button"
                    class="order-management__badge order-management__badge--shipped col-start-2"
                >
                    Shipped: {{ $counts['shipped'] ?? 0 }}
                </button>
                <button
                    type="button"
                    class="order-management__badge order-management__badge--delivered col-start-3"
                >
                    Delivered: {{ $counts['delivered'] ?? 0 }}
                </button>
                <button
                    type="button"
                    class="order-management__badge order-management__badge--unfulfilled col-start-4"
                >
                    Unfulfilled: {{ $counts['unfulfilled'] ?? 0 }}
                </button>

                {{-- 3) Row 2, cols 2–4 --}}
                {{-- Swap here: bulk “Delivered” button moves into row 2, col 2 --}}
                <button
                    @click="markBulk('delivered')"
                    class="btn-secondary text-xs px-2 py-1 col-start-2"
                >
                    Mark Selected Delivered
                </button>

                {{-- Now bring Pending badge down into row 2, col 3 --}}
                <button
                    type="button"
                    class="order-management__badge order-management__badge--pending col-start-3"
                >
                    Pending: {{ $counts['pending'] ?? 0 }}
                </button>

                {{-- And the other two status/bulk items fill cols 4 & 5 (but we only have 4 cols, so col 4 here) --}}
                <button
                    type="button"
                    class="order-management__badge order-management__badge--canceled col-start-4"
                >
                    Canceled: {{ $counts['canceled'] ?? 0 }}
                </button>
                <button
                    @click="markBulk('shipped')"
                    class="btn-secondary text-xs px-2 py-1 col-start-4 row-start-2 self-end"
                    style="justify-self:end;"
                >
                    Mark Selected Shipped
                </button>
            </div>

            {{-- Filters --}}
            <x-form
                id="orders-filters-form"
                method="GET"
                action="{{ route('admin.dashboard') }}"
                class="order-filters"
            >
                <!-- existing filters… -->
            </x-form>

            {{-- Orders Table --}}
            <div id="orders-grid">
                <table class="orders-table">
                    <thead>
                    <tr>
                        <th><input type="checkbox" @click="toggleAll" /></th>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Shipping</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($recentOrders as $order)
                        <tr>
                            <td class="orders-table__checkbox">
                                <input
                                    type="checkbox"
                                    value="{{ $order->id }}"
                                    x-model="selectedOrders"
                                />
                            </td>
                            <td>{{ $order->id }}</td>
                            <td>{{ optional($order->user)->name ?? 'Guest' }}</td>
                            <td><span class="orders-table__status">{{ ucfirst($order->status) }}</span></td>
                            <td>${{ number_format($order->shipping_fee, 2) }}</td>
                            <td>${{ number_format($order->total, 2) }}</td>
                            <td>{{ $order->created_at->format('M j, Y') }}</td>
                            <td class="orders-table__actions">
                                <button @click="singleMark({{ $order->id }}, 'shipped')">Mark Shipped</button>
                                <button @click="singleMark({{ $order->id }}, 'delivered')">Mark Delivered</button>
                                <button @click.stop="openOrderEdit({{ $order->id }})">Edit</button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <div class="orders-pagination">
                    {{ $recentOrders->links() }}
                </div>
            </div>
        </div>






        {{-- 5. REVIEW MANAGEMENT --}}
        <div class="review-management" x-data="{ tab: 'pending' }">
            <div class="review-management__tabs">
                @foreach(['pending','approved','rejected'] as $st)
                    <button
                        @click="tab='{{ $st }}'"
                        :class="tab==='{{ $st }}'
                    ? 'review-management__tab-btn review-management__tab-btn--active'
                    : 'review-management__tab-btn'"
                    >
                        {{ ucfirst($st) }} ({{ $reviewCounts[$st] }})
                    </button>
                @endforeach
            </div>

            @foreach(['pending','approved','rejected'] as $st)
                <div x-show="tab==='{{ $st }}'">
                    @php $list = ${$st.'Reviews'}; @endphp

                    @if($list->isEmpty())
                        <p class="review-empty">No {{ $st }} reviews.</p>
                    @else
                        <table class="review-table">
                            <thead>
                            <tr>
                                <th>Product</th>
                                <th>User</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($list as $r)
                                <tr>
                                    <td>
                                        <a href="{{ route('products.show',$r->product->slug) }}">
                                            {{ $r->product->name }}
                                        </a>
                                    </td>
                                    <td>{{ $r->user->name }}</td>
                                    <td>{{ $r->rating }} ★</td>
                                    <td>{{ Str::limit($r->body, 50) }}</td>
                                    {{-- inside your reviews table body, replace the existing “Actions” <td> with this: --}}
                                        <td class="review-table__actions">
                                              <div class="flex items-center space-x-2">
                                                    @if($st === 'pending')
                                                          <x-form method="PATCH" action="{{ route('admin.reviews.approve',$r) }}" class="inline">
                                                                <button type="submit" class="btn-primary btn-sm">Approve</button>
                                                              </x-form>
                                                          <x-form method="PATCH" action="{{ route('admin.reviews.reject',$r) }}" class="inline">
                                                                <button type="submit" class="btn-danger btn-sm">Reject</button>
                                                              </x-form>
                                                        @elseif($st === 'approved')
                                                          <x-form method="PATCH" action="{{ route('admin.reviews.reject',$r) }}" class="inline">
                                                                <button type="submit" class="btn-danger btn-sm">Reject</button>
                                                              </x-form>
                                                          <x-form method="DELETE" action="{{ route('admin.reviews.destroy',$r) }}" class="inline" onsubmit="return confirm('Delete this review?')">
                                                                <button type="submit" class="btn-secondary btn-sm">Delete</button>
                                                              </x-form>
                                                        @elseif($st === 'rejected')
                                                          <x-form method="PATCH" action="{{ route('admin.reviews.approve',$r) }}" class="inline">
                                                                <button type="submit" class="btn-primary btn-sm">Approve</button>
                                                              </x-form>
                                                          <x-form method="DELETE" action="{{ route('admin.reviews.destroy',$r) }}" class="inline" onsubmit="return confirm('Delete this review?')">
                                                                <button type="submit" class="btn-secondary btn-sm">Delete</button>
                                                              </x-form>
                                                        @endif

                                                    {{-- new “Edit” button --}}
                                                    <button
                                                          type="button"
                                                          @click="openReviewEdit({{ $r->id }})"
                                                          class="btn-secondary btn-sm"
                                                        >Edit</button>
                                                  </div>
                                            </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- 6. INVENTORY MANAGEMENT --}}
        <div class="inventory-section">
            <div class="inventory-header">
                <h3 class="inventory-title">Inventory</h3>
                <button @click="openModal('inventory-create')" class="inventory-add-btn">
                    + New Product
                </button>
            </div>

            {{-- Filters --}}
            <x-form
                id="admin-filters-form"
                method="GET"
                action="{{ route('admin.products.index') }}"
                class="filters-form"
            >
                <input
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search products…"
                />
                <select name="brand"><option value="">All Brands</option></select>
                <select name="category"><option value="">All Categories</option></select>
                <select name="featured"><option value="">Featured?</option></select>
                <select name="sort"><option value="">Sort By</option></select>
                <select name="dir"><option value="">Direction</option></select>
                <button type="submit" class="filters-submit">Apply</button>
            </x-form>

            {{-- Product Grid --}}
                @include('partials.admin.product-grid')

        </div>

        {{-- 7. CUSTOMER INSIGHTS --}}
        <div class="insights-card">
            <h3 class="insights-card__title">
                New Registrations Today: {{ $newCustomersToday }}
            </h3>
            <ul class="insights-card__list">
                @foreach($topCustomers as $user)
                    <li class="insights-card__item">
                        {{ $user->name }} (${{ number_format($user->lifetime_spend,2) }})
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- 8. PROMOTIONS --}}
        <div class="promotions-section">

            <div class="promotions-header">
                <h3 class="promotions-title">Promo Codes</h3>
                <button @click="openModal('promo-create')" class="btn-primary">+ New Promo</button>
            </div>

            <div class="promotions-list">
                @forelse($activePromos as $promo)
                    <div class="promo-row">
                        <div class="promo-info">
                            <span>{{ $promo->code }}</span>
                            — {{ $promo->type === 'percent'
               ? $promo->discount.'%'
               : '$'.number_format($promo->discount, 2) }}
                            @if($promo->expires_at)
                                <span class="promo-expires">
              (expires {{ $promo->expires_at->format('M j, Y') }})
            </span>
                            @endif
                        </div>
                        <div class="promo-actions">
                            <button @click="openModal('promo-edit-{{ $promo->id }}')">Edit</button>
                            <x-form
                                method="DELETE"
                                action="{{ route('admin.promo.destroy', $promo) }}"
                                class="inline"
                            >
                                <button
                                    type="submit"
                                    onclick="return confirm('Delete promo {{ $promo->code }}?')"
                                >
                                    Delete
                                </button>
                            </x-form>
                        </div>
                    </div>
                @empty
                    <p class="promo-empty">No promo codes defined.</p>
                @endforelse
            </div>
        </div>



        {{-- 6. DEV METRICS --}}
        <div x-show="devMetricsVisible" class="metrics-card">
            {!! $analyticsHtml !!}
        </div>

        {{-- 9. SETTINGS --}}
        <div class="settings-card">
            <ul class="settings-list">
                <li>
                    <a href="{{ route('admin.products.index') }}" class="settings-link">
                        Manage Products
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="settings-link">
                        View All Orders
                    </a>
                </li>
            </ul>
        </div>

        {{-- 8. NEWSLETTER MANAGEMENT --}}
        <div class="newsletter-section mt-12">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold">Newsletters</h3>
                <button @click="openModal('newsletter-create')" class="btn-primary">+ New Newsletter</button>
            </div>

            @if($newsletters->isEmpty())
                <p>No newsletters have been scheduled yet.</p>
            @else
                <table class="w-full text-left border-collapse mb-6">
                    <thead class="bg-gray-100">
                    <tr>
                        <th class="px-2 py-1">ID</th>
                        <th class="px-2 py-1">Subject</th>
                        <th class="px-2 py-1">Status</th>
                        <th class="px-2 py-1">Scheduled At</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($newsletters as $nl)
                        <tr class="border-t">
                            <td class="px-2 py-1">{{ $nl->id }}</td>
                            <td class="px-2 py-1">{{ $nl->subject }}</td>
                            <td class="px-2 py-1">{{ ucfirst($nl->status) }}</td>
                            <td class="px-2 py-1">
                                {{ $nl->scheduled_at
                                   ? $nl->scheduled_at->format('M j, Y g:ia')
                                   : '—' }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div>{{ $newsletters->links() }}</div>
            @endif
        </div>

        {{-- 9. Create Newsletter Modal --}}
        <x-modal name="newsletter-create" maxWidth="lg">
            <x-slot name="title">New Newsletter</x-slot>

            <x-form
                id="newsletter-create-form"
                method="POST"
                action="{{ route('admin.newsletter.store') }}"
                x-data="{ template:'{{ array_key_first($templates) }}', fields:@json(array_values($templates)[0]['fields']) }"
                @submit.prevent="validateAndSubmit($el)"
                class="space-y-4 p-6"
            >
                @csrf

                {{-- Template selector --}}
                <div>
                    <label for="template_key" class="block font-medium">Template</label>
                    <select
                        id="template_key"
                        name="template_key"
                        x-model="template"
                        @change="fields = @json($templates)[template].fields"
                        class="w-full border rounded px-3 py-2"
                    >
                        @foreach($templates as $key => $cfg)
                            <option value="{{ $key }}">{{ $cfg['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Dynamic fields --}}
                <template x-for="field in fields" :key="field">
                    <div>
                        <label :for="field" class="block font-medium" x-text="field.replace('_',' ').toUpperCase()"></label>

                        <template x-if="field!=='body_text'">
                            <input
                                :id="field"
                                :name="field"
                                type="text"
                                required
                                class="w-full border rounded px-3 py-2"
                            />
                        </template>

                        <template x-if="field==='body_text'">
                        <textarea
                            :id="field"
                            :name="field"
                            rows="4"
                            required
                            class="w-full border rounded px-3 py-2"
                        ></textarea>
                        </template>
                    </div>
                </template>

                {{-- Optional promo code --}}
                <div>
                    <label for="promo_code" class="block font-medium">Promo Code (optional)</label>
                    <input
                        id="promo_code"
                        name="promo_code"
                        type="text"
                        placeholder="PROMO2025"
                        class="w-full border rounded px-3 py-2"
                    />
                </div>

                {{-- Schedule --}}
                <div>
                    <label for="scheduled_at" class="block font-medium">Send At (optional)</label>
                    <input
                        type="datetime-local"
                        id="scheduled_at"
                        name="scheduled_at"
                        class="w-full border rounded px-3 py-2"
                    />
                </div>

                <div class="flex justify-end space-x-2 pt-4">
                    <button
                        type="button"
                        @click="$dispatch('close-modal','newsletter-create')"
                        class="btn-secondary"
                    >
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary">Schedule</button>
                </div>
            </x-form>

            <x-slot name="footer"></x-slot>
        </x-modal>

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

        {{-- Edit Order Modal --}}
        <x-modal name="order-edit" maxWidth="lg">
            <x-slot name="title">
                Edit Order # <span x-text="selectedOrder.id"></span>
            </x-slot>

            <x-form
                method="PATCH"
                x-bind:action="'/admin/orders/' + selectedOrder.id"
                @submit.prevent="updateOrder()"
                class="modal-body"
            >
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Status --}}
                    <div class="form-group">
                        <x-input-label for="status" value="Status" />
                        <select
                            id="status"
                            name="status"
                            x-model="selectedOrder.status"
                            required
                            class="form-select"
                        >
                            @foreach(['pending','shipped','delivered','unfulfilled','canceled','returned'] as $st)
                                <option value="{{ $st }}">{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Shipping Fee --}}
                    <div class="form-group">
                        <x-input-label for="shipping_fee" value="Shipping Fee" />
                        <input
                            id="shipping_fee"
                            name="shipping_fee"
                            type="number"
                            step="0.01"
                            x-model="selectedOrder.shipping_fee"
                            required
                            class="form-input"
                        />
                    </div>

                    {{-- Total --}}
                    <div class="form-group">
                        <x-input-label for="total" value="Total" />
                        <input
                            id="total"
                            name="total"
                            type="number"
                            step="0.01"
                            x-model="selectedOrder.total"
                            required
                            class="form-input"
                        />
                    </div>

                    {{-- Shipping Address --}}
                    <div class="form-group field-group-full">
                        <x-input-label for="shipping_address" value="Shipping Address" />
                        <textarea
                            id="shipping_address"
                            name="shipping_address"
                            rows="2"
                            x-model="selectedOrder.shipping_address"
                            required
                            class="form-textarea"
                        ></textarea>
                    </div>

                    {{-- Email --}}
                    <div class="form-group">
                        <x-input-label for="email" value="Email" />
                        <input
                            id="email"
                            name="email"
                            type="email"
                            x-model="selectedOrder.email"
                            class="form-input"
                        />
                    </div>

                    {{-- Phone --}}
                    <div class="form-group">
                        <x-input-label for="phone" value="Phone" />
                        <input
                            id="phone"
                            name="phone"
                            type="text"
                            x-model="selectedOrder.phone"
                            class="form-input"
                        />
                    </div>
                </div>

                <div class="modal-footer">
                    <x-secondary-button type="button" @click="$dispatch('close-modal','order-edit')">
                        Cancel
                    </x-secondary-button>
                    <x-primary-button type="submit">
                        Save Changes
                    </x-primary-button>
                </div>
            </x-form>
        </x-modal>

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

        {{-- Review Edit Modal --}}
        <x-modal name="review-edit" maxWidth="md">
            <x-slot name="title">
                Edit Review #<span x-text="selectedReview.id"></span>
            </x-slot>

            <x-form
                method="PATCH"
                x-bind:action="`/admin/reviews/${selectedReview.id}`"
                @submit.prevent="updateReview()"
                class="space-y-4 p-6"
            >
                @csrf {{-- if you need it for non-AJAX fallbacks --}}
                <div>
                    <label for="rating" class="block font-medium">Rating</label>
                    <input
                        id="rating"
                        name="rating"
                        type="number"
                        min="1"
                        max="5"
                        x-model.number="selectedReview.rating"
                        required
                        class="form-input"
                    />
                </div>

                <div>
                    <label for="body" class="block font-medium">Comment</label>
                    <textarea
                        id="body"
                        name="body"
                        rows="4"
                        x-model="selectedReview.body"
                        required
                        class="form-textarea"
                    ></textarea>
                </div>

                <div>
                    <label for="status" class="block font-medium">Status</label>
                    <select
                        id="status"
                        name="status"
                        x-model="selectedReview.status"
                        required
                        class="form-select"
                    >
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        @click="$dispatch('close-modal','review-edit')"
                        class="btn-secondary"
                    >Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </x-form>
        </x-modal>

    </div>




    @vite('resources/js/admin-dashboard.js')
@endsection
