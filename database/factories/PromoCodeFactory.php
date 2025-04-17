<?php

namespace Database\Factories;

use App\Models\PromoCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PromoCodeFactory extends Factory
{
    protected $model = PromoCode::class;

    public function definition(): array
    {
        return [
            'code'       => strtoupper(Str::random(6)),
            'discount'   => $this->faker->randomFloat(2, 5, 30), // e.g. 5% - 30%
            'type'       => $this->faker->randomElement(['fixed', 'percent']),
            'expires_at' => now()->addDays(rand(5, 30)),
            'active'     => true,
        ];
    }
}
