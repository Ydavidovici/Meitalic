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
        $product  = Product::factory()->create();
        $quantity = $this->faker->numberBetween(1, 5);

        return [
            'order_id'   => Order::factory(),
            'product_id' => $product->id,
            'name'       => $product->name,
            'price'      => $product->price,
            'quantity'   => $quantity,
            'total'      => $product->price * $quantity,
            'sku'        => $product->sku,
            'options'    => json_encode($product->options),
        ];
    }
}
