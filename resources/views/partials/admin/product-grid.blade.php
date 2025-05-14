{{-- resources/views/partials/admin/product-grid.blade.php --}}
    <div id="admin-product-grid" class="product-grid">
        @forelse($products as $prod)
            <div class="product-card">
                <h4 class="product-card__title">{{ $prod->name }}</h4>

                <div class="product-card__image-wrap">
                    @if($prod->image_url)
                        <img src="{{ $prod->image_url }}" alt="{{ $prod->name }}" class="product-card__image">
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

    {{-- the edit‑modals for just these products --}}
{{-- edit‑modals for each product --}}
@foreach($products as $prod)
    <x-modal name="product-edit-{{ $prod->id }}" maxWidth="lg">
        <form
            method="POST"
            action="{{ route('admin.products.update', $prod) }}"
            enctype="multipart/form-data"
            class="modal-body--product-edit"
            @submit.prevent="validateAndSubmit($el)"
        >
            @csrf
            @method('PUT')

            <h3 class="modal-title">Edit {{ $prod->name }}</h3>

            {{-- grid of name/brand/category/price/inventory --}}
            <div class="field-group">
                <div>
                    <x-input-label for="name-{{ $prod->id }}" value="Name"/>
                    <x-text-input id="name-{{ $prod->id }}"
                                  name="name"
                                  value="{{ old('name',$prod->name) }}"
                                  required />
                </div>

                <div>
                    <x-input-label for="brand-{{ $prod->id }}" value="Brand"/>
                    <x-text-input id="brand-{{ $prod->id }}"
                                  name="brand"
                                  value="{{ old('brand',$prod->brand) }}"
                                  required />
                </div>

                <div>
                    <x-input-label for="category-{{ $prod->id }}" value="Category"/>
                    <x-text-input id="category-{{ $prod->id }}"
                                  name="category"
                                  value="{{ old('category',$prod->category) }}"
                                  required />
                </div>

                <div>
                    <x-input-label for="price-{{ $prod->id }}" value="Price"/>
                    <x-text-input id="price-{{ $prod->id }}"
                                  name="price"
                                  type="number" step="0.01"
                                  value="{{ old('price',$prod->price) }}"
                                  required />
                </div>

                <div>
                    <x-input-label for="inventory-{{ $prod->id }}" value="Inventory"/>
                    <x-text-input id="inventory-{{ $prod->id }}"
                                  name="inventory"
                                  type="number"
                                  value="{{ old('inventory',$prod->inventory) }}"
                                  required />
                </div>
            </div>

            {{-- description full‑width --}}
            <div class="field-group field-full">
                <x-input-label for="description-{{ $prod->id }}" value="Description"/>
                <textarea id="description-{{ $prod->id }}"
                          name="description"
                          rows="4"
                          required>{{ old('description',$prod->description) }}</textarea>
            </div>

            {{-- image upload & preview --}}
            <div class="field-group">
                <x-input-label for="image-{{ $prod->id }}" value="Product Image"/>
                @if($prod->image)
                    <img src="{{ Str::startsWith($prod->image,['http://','https://'])
                      ? $prod->image
                      : asset('storage/'.$prod->image) }}"
                         alt="{{ $prod->name }}"
                         class="image-preview">
                @endif
                <input id="image-{{ $prod->id }}"
                       name="image"
                       type="file"
                       accept="image/*" />
            </div>

            {{-- featured toggle --}}
            <div class="toggle-row">
                <input type="checkbox"
                       id="is_featured-{{ $prod->id }}"
                       name="is_featured"
                       value="1"
                    @checked(old('is_featured',$prod->is_featured)) />
                <label for="is_featured-{{ $prod->id }}">Featured</label>
            </div>

            {{-- buttons --}}
            <div class="button-row">
                <x-secondary-button type="button"
                                    @click="$dispatch('close-modal','product-edit-{{ $prod->id }}')">
                    Cancel
                </x-secondary-button>
                <x-primary-button type="submit">
                    Save Changes
                </x-primary-button>
            </div>
        </form>
    </x-modal>
    @endforeach
