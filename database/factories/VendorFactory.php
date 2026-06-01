<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['type' => User::TYPE_VENDOR]),
            'store_name' => fake()->company(),
            'business_type' => Vendor::BUSINESS_TYPE_BOTH,
            'description' => fake()->sentence(),
            'address' => fake()->address(),
            'logo' => null,
            'is_active' => true,
            'status' => Vendor::STATUS_ACTIVE,
            'registration_source' => Vendor::REGISTRATION_SOURCE_ADMIN,
            'commercial_register_file' => null,
        ];
    }

    /**
     * Indicate that the vendor is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'status' => Vendor::STATUS_INACTIVE,
        ]);
    }

    /**
     * Indicate that the vendor is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'status' => Vendor::STATUS_PENDING,
            'registration_source' => Vendor::REGISTRATION_SOURCE_SELF,
        ]);
    }
}
