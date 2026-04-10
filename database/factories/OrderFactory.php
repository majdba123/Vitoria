<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-'.now()->format('Ymd').'-'.fake()->unique()->numerify('#####'),
            'user_id' => User::factory(),
            'vendor_id' => Vendor::factory(),
            'coupon_id' => null,
            'coupon_code' => null,
            'coupon_type' => null,
            'coupon_value' => null,
            'status' => Order::STATUS_PENDING,
            'payment_way' => 'cash',
            'items_count' => fake()->numberBetween(1, 6),
            'subtotal_amount' => fake()->randomFloat(2, 10, 1000),
            'coupon_discount_amount' => 0,
            'total_amount' => fake()->randomFloat(2, 10, 1000),
        ];
    }
}
