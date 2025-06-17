{{-- resources/views/partials/admin/product-grid.blade.php --}}
<div id="admin-product-grid" class="product-grid grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
    @forelse($products as $prod)
        <div class="product-card bg-white rounded-lg shadow p-4 flex flex-col">
            <h4 class="product-card__title font-semibold mb-2">{{ $prod->name }}</h4>
            <p class="product-card__price text-gray-600 mb-1">Price: ${{ number_format($prod->price,2) }}</p>
            <p class="product-card__stock text-gray-600 mb-4">Stock: {{ $prod->inventory }}</p>

            <div class="mt-auto flex justify-end space-x-2">
                {{-- Edit --}}
                <button
                    type="button"
                    @click="openModal('product-edit-{{ $prod->id }}')"
                    class="product-card__edit-btn btn-secondary btn-sm"
                >
                    Edit
                </button>

                {{-- Delete --}}
                <x-form
                    method="DELETE"
                    action="{{ route('admin.products.destroy', $prod) }}"
                    class="inline"
                    onsubmit="return confirm('Are you sure you want to delete “{{ addslashes($prod->name) }}”?')"
                >
                    <button type="submit" class="btn-danger btn-sm">
                        Delete
                    </button>
                </x-form>
            </div>
        </div>
    @empty
        <div class="product-card__empty text-center text-gray-500">
            No products in inventory.
        </div>
    @endforelse
</div>

<div class="product-grid__pagination mt-6">
    {{ $products->withQueryString()->links() }}
</div>
