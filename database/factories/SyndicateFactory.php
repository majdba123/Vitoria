<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Syndicate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Syndicate>
 */
class SyndicateFactory extends Factory
{
    protected $model = Syndicate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->syndicate(),
            'name' => fake()->company().' Syndicate',
            'type' => fake()->randomElement([Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY]),
            'phone' => fake()->unique()->numerify('09########'),
            'email' => fake()->unique()->safeEmail(),
            'status' => Syndicate::STATUS_ACTIVE,
            'logo' => null,
        ];
    }

    public function agriculture(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Category::TYPE_AGRICULTURE,
        ]);
    }

    public function veterinary(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Category::TYPE_VETERINARY,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Syndicate::STATUS_INACTIVE,
        ]);
    }
}
