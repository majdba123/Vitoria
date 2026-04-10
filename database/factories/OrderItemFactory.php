<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 5);
        $unit = fake()->randomFloat(2, 10, 300);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'original_unit_price' => $unit,
            'has_discount' => false,
            'applied_discount_percentage' => null,
            'unit_price' => $unit,
            'quantity' => $qty,
            'line_total' => round($qty * $unit, 2),
            'discount_amount' => 0,
        ];
    }
}
