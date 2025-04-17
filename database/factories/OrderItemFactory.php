<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $product = Product::factory()->create();
        $quantity = $this->faker->numberBetween(1, 5);
        $price = $product->price;

        return [
            'order_id'   => Order::factory(),
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => $price,
            'quantity'   => $quantity,
            'total'      => $price * $quantity,
            'sku'        => $product->sku,
            'options'    => $product->options,
        ];
    }
}
