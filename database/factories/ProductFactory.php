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
        // Pull your box dims straight out of config:
        $boxes   = config('shipping.boxes');
        // Find the “biggest” box by volume (or assume the last one):
        $biggest = collect($boxes)
            ->sortBy(fn($b) => $b['length'] * $b['width'] * $b['height'])
            ->last();

        // Envelope is smaller—ignore, since we want box fits
        $maxL = $biggest['length'];     // 9.0
        $maxW = $biggest['width'];      // 6.5
        $maxH = $biggest['height'];     // 3.5
        $maxWt = $biggest['max_weight']; // 50

        // 1) Brands and categories as before
        $brands     = ['Meitalic','Repechage'];
        $categories = ['Skincare','Make up','Starter Kits','Accessories'];
        $skincare   = ['Brightening line','Acne line','Rosacea line','Make up line'];

        $brand    = $this->faker->randomElement($brands);
        $category = $this->faker->randomElement($categories);
        $line     = $category === 'Skincare'
            ? $this->faker->randomElement($skincare)
            : null;

        return [
            'name'         => ucfirst($this->faker->words(3, true)),
            'brand'        => $brand,
            'category'     => $category,
            'line'         => $line,
            'description'  => $this->faker->sentences(3, true),

            // **Constrain dims & weight to fit in your biggest box:**
            'length'       => $this->faker->randomFloat(2, 0.1, $maxL),
            'width'        => $this->faker->randomFloat(2, 0.1, $maxW),
            'height'       => $this->faker->randomFloat(2, 0.1, $maxH),
            'weight'       => $this->faker->randomFloat(2, 0.1, $maxWt),

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