<?php

// database/factories/ProductImageFactory.php

namespace Database\Factories;

use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition()
    {
        return [
            // you can later override product_id when you call it:
            'path' => 'images/hero-photo.png',
        ];
    }
}
