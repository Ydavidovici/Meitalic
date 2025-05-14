{{-- resources/views/partials/product-grid.blade.php --}}
<div id="product-grid" class="product-grid">
    @foreach($products as $product)
        <x-product-card :product="$product"/>
    @endforeach

    <div class="product-grid__pagination">
        {{ $products->withQueryString()->links() }}
    </div>
</div>
