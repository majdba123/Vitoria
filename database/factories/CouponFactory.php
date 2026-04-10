<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['percentage', 'fixed']);
        $startsAt = now()->subDays(fake()->numberBetween(1, 7));
        $endsAt = now()->addDays(fake()->numberBetween(7, 30));
        $isActive = fake()->boolean(80);

        return [
            'code' => strtoupper(fake()->bothify('SAVE-###??')),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'discount_type' => $type,
            'discount_value' => $type === 'percentage'
                ? fake()->randomFloat(2, 5, 40)
                : fake()->randomFloat(2, 1000, 15000),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_active' => $isActive,
            'status' => Coupon::resolveStatus($isActive, $startsAt->toDateTimeString(), $endsAt->toDateTimeString()),
            'usage_limit' => fake()->optional()->numberBetween(10, 300),
            'used_count' => 0,
        ];
    }
}
