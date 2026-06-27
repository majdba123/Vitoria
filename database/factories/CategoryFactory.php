<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => fake()->randomElement([Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY]),
            'logo' => null,
            'icon' => null,
            'icon_class' => null,
            'commission' => fake()->randomFloat(2, 1, 12),
        ];
    }
}
