<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderReturn>
 */
class OrderReturnFactory extends Factory
{
    protected $model = OrderReturn::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'return_number' => 'RET-'.now()->format('Ymd').'-'.fake()->unique()->numerify('#####'),
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'vendor_id' => Vendor::factory(),
            'status' => OrderReturn::STATUS_REQUESTED,
            'reason' => fake()->randomElement(OrderReturn::REASONS),
            'refundable_amount' => fake()->randomFloat(2, 10, 500),
            'requested_at' => now(),
        ];
    }
}
