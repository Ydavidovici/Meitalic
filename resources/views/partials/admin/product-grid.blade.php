{{-- resources/views/partials/admin/product-grid.blade.php --}}
<div id="admin-product-grid" class="product-grid">
@forelse($products as $prod)
        <div class="product-card">
            <h4 class="product-card__title">{{ $prod->name }}</h4>
            <p class="product-card__price">Price: ${{ number_format($prod->price,2) }}</p>
            <p class="product-card__stock">Stock: {{ $prod->inventory }}</p>
            <button type="button" @click="openModal('product-edit-{{ $prod->id }}')" class="product-card__edit-btn">
                Edit
            </button>
        </div>
    @empty
        <div class="product-card__empty">No products in inventory.</div>
    @endforelse
</div>

<div class="product-grid__pagination">
    {{ $products->withQueryString()->links() }}
</div>
