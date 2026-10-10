<?php

namespace Database\Factories;

use App\Models\ImportShipment;
use App\Models\Port;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportShipment>
 */
class ImportShipmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'port_id' => Port::factory(),
            'merchant_name' => fake()->name(),
            'merchant_register_number' => fake()->numerify('REG-#####'),
            'merchant_address' => fake()->address(),
            'invoice_number' => fake()->unique()->numerify('INV-######'),
            'stickers_count' => fake()->numberBetween(1, 50),
            'sticker_price' => fake()->randomElement(ImportShipment::STICKER_PRICES),
            'clearance_number' => fake()->numerify('CLR-####'),
            'release_decision' => null,
        ];
    }
}
