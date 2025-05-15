{{-- resources/views/partials/admin/product-grid.blade.php --}}

<div id="admin-product-grid" class="product-grid">
    @forelse($products as $prod)
        <div class="product-card">
            <h4 class="product-card__title">{{ $prod->name }}</h4>

            <div class="product-card__image-wrap relative">
                @if($prod->image_url)
                    <img
                        src="{{ $prod->image_url }}"
                        alt="{{ $prod->name }}"
                        class="product-card__image"
                    >
                @else
                    <div class="product-card__no-image">No Image</div>
                @endif
            </div>

            <p class="product-card__price">
                Price: ${{ number_format($prod->price,2) }}
            </p>
            <p class="product-card__stock">
                Stock: {{ $prod->inventory }}
            </p>

            <button
                class="product-card__edit-btn"
                @click="openModal('product-edit-{{ $prod->id }}')"
            >Edit</button>
        </div>
    @empty
        <div class="product-card__empty">No products in inventory.</div>
    @endforelse
</div>

<div class="product-grid__pagination">
    {{ $products->withQueryString()->links() }}
</div>

{{-- Create Inventory Modal --}}
<x-modal name="inventory-create" maxWidth="lg">
    <x-slot name="title">New Product</x-slot>

    <x-form
        method="POST"
        action="{{ route('admin.products.store') }}"
        enctype="multipart/form-data"
        class="modal-body--product-edit"
        @submit.prevent="validateAndSubmit($el)"
    >
        <div class="modal-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Core fields -->
                <div class="form-group">
                    <x-input-label for="new-name" value="Name" />
                    <input id="new-name" name="name" type="text" required class="form-input" />
                </div>
                <div class="form-group">
                    <x-input-label for="new-brand" value="Brand" />
                    <input id="new-brand" name="brand" type="text" required class="form-input" />
                </div>
                <div class="form-group">
                    <x-input-label for="new-category" value="Category" />
                    <input id="new-category" name="category" type="text" required class="form-input" />
                </div>
                <div class="form-group">
                    <x-input-label for="new-price" value="Price" />
                    <input id="new-price" name="price" type="number" step="0.01" required class="form-input" />
                </div>
                <div class="form-group">
                    <x-input-label for="new-inventory" value="Inventory" />
                    <input id="new-inventory" name="inventory" type="number" required class="form-input" />
                </div>

                <!-- Shipping fields -->
                <div class="form-group">
                    <x-input-label for="new-weight" value="Weight (lb)" />
                    <input id="new-weight" name="weight" type="number" step="0.01" required class="form-input" />
                </div>
                <div class="form-group">
                    <x-input-label for="new-length" value="Length (in)" />
                    <input id="new-length" name="length" type="number" required class="form-input" />
                </div>
                <div class="form-group">
                    <x-input-label for="new-width" value="Width (in)" />
                    <input id="new-width" name="width" type="number" required class="form-input" />
                </div>
                <div class="form-group">
                    <x-input-label for="new-height" value="Height (in)" />
                    <input id="new-height" name="height" type="number" required class="form-input" />
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <x-input-label for="new-description" value="Description" />
                <textarea id="new-description" name="description" rows="4" required class="form-textarea"></textarea>
            </div>

            <!-- Image -->
            <div class="form-group">
                <x-input-label for="new-image" value="Product Image" />
                <input id="new-image" name="image" type="file" accept="image/*" class="form-input" />
            </div>

            <!-- Featured toggle -->
            <div class="form-group flex items-center">
                <input id="new-is_featured" name="is_featured" type="checkbox" value="1" class="form-input w-auto mr-2" />
                <label for="new-is_featured">Featured</label>
            </div>
        </div>

        <div class="modal-footer">
            <x-secondary-button type="button" @click="$dispatch('close-modal','inventory-create')">
                Cancel
            </x-secondary-button>
            <x-primary-button type="submit">
                Create Product
            </x-primary-button>
        </div>
    </x-form>
</x-modal>

{{-- Edit‑modals for each product --}}
@foreach($products as $prod)
    <x-modal name="product-edit-{{ $prod->id }}" maxWidth="lg">
        <x-form
            method="PUT"
            action="{{ route('admin.products.update', $prod) }}"
            enctype="multipart/form-data"
            class="modal-body--product-edit"
            @submit.prevent="validateAndSubmit($el)"
        >
            <h3 class="modal-title">Edit {{ $prod->name }}</h3>

            <div class="modal-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Core fields -->
                    <div class="form-group">
                        <x-input-label for="name-{{ $prod->id }}" value="Name" />
                        <input
                            id="name-{{ $prod->id }}"
                            name="name"
                            type="text"
                            value="{{ old('name',$prod->name) }}"
                            required
                            class="form-input"
                        />
                    </div>
                    <div class="form-group">
                        <x-input-label for="brand-{{ $prod->id }}" value="Brand" />
                        <input
                            id="brand-{{ $prod->id }}"
                            name="brand"
                            type="text"
                            value="{{ old('brand',$prod->brand) }}"
                            required
                            class="form-input"
                        />
                    </div>
                    <div class="form-group">
                        <x-input-label for="category-{{ $prod->id }}" value="Category" />
                        <input
                            id="category-{{ $prod->id }}"
                            name="category"
                            type="text"
                            value="{{ old('category',$prod->category) }}"
                            required
                            class="form-input"
                        />
                    </div>
                    <div class="form-group">
                        <x-input-label for="price-{{ $prod->id }}" value="Price" />
                        <input
                            id="price-{{ $prod->id }}"
                            name="price"
                            type="number"
                            step="0.01"
                            value="{{ old('price',$prod->price) }}"
                            required
                            class="form-input"
                        />
                    </div>
                    <div class="form-group">
                        <x-input-label for="inventory-{{ $prod->id }}" value="Inventory" />
                        <input
                            id="inventory-{{ $prod->id }}"
                            name="inventory"
                            type="number"
                            value="{{ old('inventory',$prod->inventory) }}"
                            required
                            class="form-input"
                        />
                    </div>

                    <!-- Shipping fields -->
                    <div class="form-group">
                        <x-input-label for="weight-{{ $prod->id }}" value="Weight (lb)" />
                        <input
                            id="weight-{{ $prod->id }}"
                            name="weight"
                            type="number"
                            step="0.01"
                            value="{{ old('weight',$prod->weight) }}"
                            required
                            class="form-input"
                        />
                    </div>
                    <div class="form-group">
                        <x-input-label for="length-{{ $prod->id }}" value="Length (in)" />
                        <input
                            id="length-{{ $prod->id }}"
                            name="length"
                            type="number"
                            value="{{ old('length',$prod->length) }}"
                            required
                            class="form-input"
                        />
                    </div>
                    <div class="form-group">
                        <x-input-label for="width-{{ $prod->id }}" value="Width (in)" />
                        <input
                            id="width-{{ $prod->id }}"
                            name="width"
                            type="number"
                            value="{{ old('width',$prod->width) }}"
                            required
                            class="form-input"
                        />
                    </div>
                    <div class="form-group">
                        <x-input-label for="height-{{ $prod->id }}" value="Height (in)" />
                        <input
                            id="height-{{ $prod->id }}"
                            name="height"
                            type="number"
                            value="{{ old('height',$prod->height) }}"
                            required
                            class="form-input"
                        />
                    </div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <x-input-label for="description-{{ $prod->id }}" value="Description" />
                    <textarea
                        id="description-{{ $prod->id }}"
                        name="description"
                        rows="4"
                        required
                        class="form-textarea"
                    >{{ old('description',$prod->description) }}</textarea>
                </div

                    <!-- Image upload & preview -->
                <div class="form-group">
                    <x-input-label for="image-{{ $prod->id }}" value="Product Image" />
                    @if($prod->image)
                        <img
                            src="{{ Str::startsWith($prod->image,['http://','https://'])
                              ? $prod->image
                              : asset('storage/'.$prod->image) }}"
                            alt="{{ $prod->name }}"
                            class="image-preview mb-2"
                        >
                    @endif
                    <input
                        id="image-{{ $prod->id }}"
                        name="image"
                        type="file"
                        accept="image/*"
                        class="form-input"
                    />
                </div>

                <!-- Featured toggle -->
                <div class="form-group flex items-center">
                    <input
                        id="is_featured-{{ $prod->id }}"
                        name="is_featured"
                        type="checkbox"
                        value="1"
                        @checked(old('is_featured',$prod->is_featured))
                        class="form-input w-auto mr-2"
                    />
                    <label for="is_featured-{{ $prod->id }}">Featured</label>
                </div>
            </div>

            <div class="modal-footer">
                <x-secondary-button
                    type="button"
                    @click="$dispatch('close-modal','product-edit-{{ $prod->id }}')"
                >
                    Cancel
                </x-secondary-button>
                <x-primary-button type="submit">
                    Save Changes
                </x-primary-button>
            </div>
        </x-form>
    </x-modal>
@endforeach
