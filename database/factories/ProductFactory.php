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
        $name = $this->faker->words(3, true);

        return [
            'name'         => ucfirst($name),
            'brand'        => $this->faker->company,
            'category'     => $this->faker->randomElement([
                'Skincare', 'Makeup', 'Haircare', 'Fragrance', 'Nail Care'
            ]),
            'description'  => $this->faker->sentences(3, true),
            'weight'       => $this->faker->randomFloat(2, 0.1, 20), // pounds
            'length' => $this->faker->randomFloat(2, 1, 24),
            'width'  => $this->faker->randomFloat(2, 1, 24),
            'height' => $this->faker->randomFloat(2, 0.1, 24),
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
