<div id="admin-product-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($products as $prod)
        <div class="card flex flex-col h-full">
            <h4 class="font-semibold mb-2">{{ $prod->name }}</h4>

            <div class="h-40 bg-gray-100 rounded overflow-hidden mb-4">
                @if($prod->image_url)
                    <img
                        src="{{ $prod->image_url }}"
                        alt="{{ $prod->name }}"
                        class="w-full h-full object-cover"
                    >
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                        No Image
                    </div>
                @endif
            </div>

            <p class="text-gray-600 text-sm mb-2">
                Price: ${{ number_format($prod->price,2) }}
            </p>
            <p class="text-lg font-bold mb-4">
                Stock: {{ $prod->inventory }}
            </p>

            <button
                class="btn-primary mt-auto"
                @click="openModal('product-edit-{{ $prod->id }}')"
            >Edit</button>
        </div>
    @empty
        <div class="col-span-full text-center py-12 text-gray-600">
            No products in inventory.
        </div>
    @endforelse
</div>

{{-- pagination lives here, inside the partial --}}
<div class="mt-6">
    {{ $products->withQueryString()->links() }}
</div>
