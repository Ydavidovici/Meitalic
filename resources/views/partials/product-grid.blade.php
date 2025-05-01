<div id="product-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
    @foreach($products as $product)
        <x-product-card :product="$product"/>
    @endforeach

    <div class="col-span-full mt-8">
        {{ $products->withQueryString()->links() }}
    </div>
</div>
