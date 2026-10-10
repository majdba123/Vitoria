<?php

namespace Database\Factories;

use App\Models\ImportShipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ImportShipmentItem>
 */
class ImportShipmentItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'import_shipment_id' => ImportShipment::factory(),
            'name' => fake()->word(),
            'country' => fake()->country(),
            'quantity' => fake()->numberBetween(1, 500),
            'total_price' => fake()->randomFloat(2, 10, 10000),
        ];
    }
}
