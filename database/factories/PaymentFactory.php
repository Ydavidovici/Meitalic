<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        return [
            'order_id'           => Order::factory(), // creates a new order for the payment
            'stripe_payment_id'  => $this->faker->uuid,
            'amount'             => $this->faker->randomFloat(2, 20, 500),
            'status'             => $this->faker->randomElement(['succeeded', 'failed', 'pending']),
        ];
    }
}
