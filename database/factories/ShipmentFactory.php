<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => Shipment::STATUS_PENDING,
        ];
    }
}
