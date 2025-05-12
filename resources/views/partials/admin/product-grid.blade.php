{{-- resources/views/partials/admin/product-grid.blade.php --}}
<div id="admin-product-section">

    {{-- the grid --}}
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

    {{-- pagination --}}
    <div class="mt-6">
        {{ $products->withQueryString()->links() }}
    </div>

    {{-- the edit‑modals for just these products --}}
    @foreach($products as $prod)
        <x-modal name="product-edit-{{ $prod->id }}" maxWidth="lg">
            <form
                method="POST"
                action="{{ route('admin.products.update', $prod) }}"
                enctype="multipart/form-data"
                class="p-6 space-y-4"
                @submit.prevent="validateAndSubmit($el)"
            >
                @csrf
                @method('PUT')

                <h3 class="text-2xl font-bold mb-4">Edit {{ $prod->name }}</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    {{-- Name --}}
                    <div>
                        <x-input-label
                            for="name-{{ $prod->id }}"
                            value="Name"
                        />
                        <x-text-input
                            id="name-{{ $prod->id }}"
                            name="name"
                            value="{{ old('name', $prod->name) }}"
                            required
                            class="mt-1 w-full"
                        />
                    </div>

                    {{-- Brand --}}
                    <div>
                        <x-input-label
                            for="brand-{{ $prod->id }}"
                            value="Brand"
                        />
                        <x-text-input
                            id="brand-{{ $prod->id }}"
                            name="brand"
                            value="{{ old('brand', $prod->brand) }}"
                            required
                            class="mt-1 w-full"
                        />
                    </div>

                    {{-- Category --}}
                    <div>
                        <x-input-label
                            for="category-{{ $prod->id }}"
                            value="Category"
                        />
                        <x-text-input
                            id="category-{{ $prod->id }}"
                            name="category"
                            value="{{ old('category', $prod->category) }}"
                            required
                            class="mt-1 w-full"
                        />
                    </div>

                    {{-- Price --}}
                    <div>
                        <x-input-label
                            for="price-{{ $prod->id }}"
                            value="Price"
                        />
                        <x-text-input
                            id="price-{{ $prod->id }}"
                            name="price"
                            type="number"
                            step="0.01"
                            value="{{ old('price', $prod->price) }}"
                            required
                            class="mt-1 w-full"
                        />
                    </div>

                    {{-- Inventory --}}
                    <div>
                        <x-input-label
                            for="inventory-{{ $prod->id }}"
                            value="Inventory"
                        />
                        <x-text-input
                            id="inventory-{{ $prod->id }}"
                            name="inventory"
                            type="number"
                            value="{{ old('inventory', $prod->inventory) }}"
                            required
                            class="mt-1 w-full"
                        />
                    </div>

                    {{-- Description --}}
                    <div class="md:col-span-2">
                        <x-input-label
                            for="description-{{ $prod->id }}"
                            value="Description"
                        />
                        <textarea
                            id="description-{{ $prod->id }}"
                            name="description"
                            rows="4"
                            required
                            class="mt-1 w-full border rounded p-2"
                        >{{ old('description', $prod->description) }}</textarea>
                    </div>

                    {{-- Image --}}
                    <div>
                        <x-input-label
                            for="image-{{ $prod->id }}"
                            value="Product Image"
                        />
                        @if($prod->image)
                            <img
                                src="{{ Str::startsWith($prod->image, ['http://','https://']) ? $prod->image : asset('storage/'.$prod->image) }}"
                                alt="{{ $prod->name }}"
                                class="w-32 h-32 object-cover rounded mb-2"
                            />
                        @endif
                        <input
                            id="image-{{ $prod->id }}"
                            name="image"
                            type="file"
                            accept="image/*"
                            class="mt-1 w-full"
                        />
                    </div>
                </div>

                {{-- Featured toggle --}}
                <div class="flex items-center space-x-2 mb-4">
                    <input
                        type="checkbox"
                        id="is_featured-{{ $prod->id }}"
                        name="is_featured"
                        value="1"
                        @checked(old('is_featured', $prod->is_featured))
                        class="form-checkbox"
                    />
                    <label
                        for="is_featured-{{ $prod->id }}"
                        class="ml-1"
                    >Featured</label>
                </div>

                <div class="flex justify-end space-x-4">
                    <x-secondary-button
                        type="button"
                        @click="$dispatch('close-modal','product-edit-{{ $prod->id }}')"
                    >Cancel</x-secondary-button>
                    <x-primary-button type="submit">
                        Save Changes
                    </x-primary-button>
                </div>
            </form>
        </x-modal>
    @endforeach

</div>
