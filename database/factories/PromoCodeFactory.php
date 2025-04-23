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
        $isPercent = $this->faker->boolean();

        return [
            'code'              => strtoupper(Str::random(6)),
            'discount_amount'   => $isPercent ? null : $this->faker->randomFloat(2, 5, 30),
            'discount_percent'  => $isPercent ? $this->faker->randomFloat(2, 5, 30) : null,
            'max_uses'          => $this->faker->optional()->numberBetween(10, 100),
            'used_count'        => 0,
            'expires_at'        => now()->addDays(rand(5, 30)),
        ];
    }
}
