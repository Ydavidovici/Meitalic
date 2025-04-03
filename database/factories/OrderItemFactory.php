<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition()
    {
        // Create a product to reference
        $product = Product::factory()->create();

        return [
            'order_id'   => Order::factory(),  // creates a new order
            'product_id' => $product->id,
            'quantity'   => $this->faker->numberBetween(1, 5),
            'price'      => $product->price,  // record product price at time of order
        ];
    }
}
