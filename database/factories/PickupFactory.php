<?php
namespace Database\Factories;

use App\Models\Pickup;
use Illuminate\Database\Eloquent\Factories\Factory;

class PickupFactory extends Factory
{
    protected $model = Pickup::class;

    public function definition()
    {
        return [
            'pickup_date'         => $this->faker->date(),
            'confirmation_number' => $this->faker->uuid,
            'payload'             => ['foo'=>'bar'], // or $this->faker->randomHtml(),
            'created_at'          => now(),
            'updated_at'          => now(),
        ];
    }
}