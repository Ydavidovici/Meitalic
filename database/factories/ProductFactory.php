<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name'        => $this->faker->word,
            'brand'       => $this->faker->company,
            'category'    => $this->faker->randomElement(['skincare', 'makeup', 'haircare', 'fragrance', 'nail care']),
            'description' => $this->faker->paragraph,
            'price'       => $this->faker->randomFloat(2, 5, 200),
            'image'       => $this->faker->imageUrl(640, 480, 'fashion', true),
            'inventory'   => $this->faker->numberBetween(0, 100),
        ];
    }
}
