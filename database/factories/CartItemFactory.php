<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\CartItem;
use App\Models\Cart;
use App\Models\Product;

class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition()
    {
        $quantity = $this->faker->numberBetween(1, 5);
        $price = $this->faker->randomFloat(2, 5, 50);

        return [
            'cart_id' => Cart::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'price' => $price,
            'total' => $price * $quantity,
        ];
    }
}
