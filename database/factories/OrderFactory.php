<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        return [
            'user_id'          => User::factory(),  // creates a new user for each order
            'shipping_address' => $this->faker->address,
            'shipping_fee'     => $this->faker->randomFloat(2, 0, 20),
            'total'            => $this->faker->randomFloat(2, 20, 500),
            'status'           => 'pending',
        ];
    }
}
