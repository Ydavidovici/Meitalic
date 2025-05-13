<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition()
    {
        // pick random existing order item
        $item = OrderItem::inRandomOrder()->first();
        return [
            'order_item_id' => $item->id,
            'user_id'       => $item->order->user_id,
            'product_id'    => $item->product_id,
            'rating'        => $this->faker->numberBetween(1,5),
            'body'          => $this->faker->optional()->paragraph(),
            'status'        => 'approved',
        ];
    }
}
