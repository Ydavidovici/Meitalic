<?php

// database/seeders/ProductImageSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;

class ProductImageSeeder extends Seeder
{
    public function run()
    {
        Product::all()->each(function($product) {
            // five placeholders per product:
            ProductImage::factory()
                ->count(5)
                ->state(['product_id' => $product->id])
                ->create();
        });
    }
}
