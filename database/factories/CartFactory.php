<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Str;

class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition()
    {
        return [
            'user_id' => null,
            'session_id' => Str::uuid(),
            'total' => $this->faker->randomFloat(2, 20, 200),
            'promo_code' => null,
            'discount' => 0,
        ];
    }
}
