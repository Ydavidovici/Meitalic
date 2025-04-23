<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultationFactory extends Factory
{
    protected $model = Consultation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'notes' => $this->faker->sentence,
            'image_path' => 'consultations/sample.jpg',
        ];
    }
}
