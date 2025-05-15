{{-- resources/views/partials/product-grid.blade.php --}}
<div id="product-grid" class="max-w-screen-lg mx-auto px-6 sm:px-8 lg:px-12 py-16">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($products as $product)
            <div class="card hover:shadow-lg transition">
                <a href="{{ route('products.show', $product->slug) }}">
                    @if($product->image)
                        <img
                            src="{{ $product->image_url }}"
                            alt="{{ $product->name }}"
                            class="w-full h-48 object-cover rounded-t"
                        />
                    @endif

                    <div class="mt-4">
                        <h3 class="font-semibold text-lg">{{ $product->name }}</h3>
                        <p class="text-gray-600">
                            ${{ number_format($product->price, 2) }}
                        </p>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="product-grid__pagination mt-8">
        {{ $products->withQueryString()->links() }}
    </div>
</div>
