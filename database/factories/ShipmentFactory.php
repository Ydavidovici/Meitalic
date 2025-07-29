<?php
namespace Database\Factories;

use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition()
    {
        return [
            'order_id'        => null, // or you can factory Order::class
            'label_id'        => $this->faker->uuid,
            'tracking_number' => $this->faker->ean13(),
            'carrier_code'    => $this->faker->randomElement(['ups','usps','fedex']),
            'service_code'    => $this->faker->word(),
            'shipment_cost'   => $this->faker->randomFloat(2, 5, 50),
            'other_cost'      => $this->faker->randomFloat(2, 0, 10),
            'label_url'       => $this->faker->url,
            'created_at'      => now(),
            'updated_at'      => now(),
        ];
    }
}