<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        // 1) The only brands we want in production:
        $brands = [
            'Meitalic',
            'Repechage',
            'Melaleuca',
        ];

        // 2) The exact categories the client uses:
        $categories = [
            'Skincare',
            'Make up',
            'Starter Kits',
            'Accessories',
        ];

        // 3) Under “Skincare” we have these “lines”:
        $skincareLines = [
            'Brightening line',
            'Acne line',
            'Rosacea line',
            'Make up line',
        ];

        // pick one of each
        $brand    = $this->faker->randomElement($brands);
        $category = $this->faker->randomElement($categories);

        // only assign a line if the category is Skincare—
        // otherwise leave it null
        $line = $category === 'Skincare'
            ? $this->faker->randomElement($skincareLines)
            : null;

        return [
            'name'         => ucfirst($this->faker->words(3, true)),
            'brand'        => $brand,
            'category'     => $category,
            'line'         => $line,               // ← our new field
            'description'  => $this->faker->sentences(3, true),
            'weight'       => $this->faker->randomFloat(2, 0.1, 20),
            'length'       => $this->faker->randomFloat(2, 1, 24),
            'width'        => $this->faker->randomFloat(2, 1, 24),
            'height'       => $this->faker->randomFloat(2, 0.1, 24),
            'price'        => $this->faker->randomFloat(2, 10, 150),
            'image'        => 'images/hero-photo.png',
            'sku'          => strtoupper(Str::random(8)),
            'options'      => [
                'size'  => $this->faker->randomElement(['S','M','L']),
                'color' => $this->faker->safeColorName(),
            ],
            'active'       => $this->faker->boolean(90),
            'is_featured'  => $this->faker->boolean(10),
            'inventory'    => $this->faker->numberBetween(10, 200),
        ];
    }
}
