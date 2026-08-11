<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Refund>
 */
class RefundFactory extends Factory
{
    protected $model = Refund::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'refund_number' => 'RFD-'.now()->format('Ymd').'-'.fake()->unique()->numerify('#####'),
            'order_id' => Order::factory(),
            'order_return_id' => null,
            'payment_id' => null,
            'amount' => fake()->randomFloat(2, 10, 500),
            'currency' => 'SYP',
            'status' => Refund::STATUS_PENDING,
            'reason' => 'other',
            'initiated_by_user_id' => User::factory(),
        ];
    }
}
