<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 1000);

        return [
            'invoice_number' => 'INV-'.now()->format('Ymd').'-'.fake()->unique()->numerify('#####'),
            'order_id' => Order::factory(),
            'vendor_id' => Vendor::factory(),
            'user_id' => User::factory(),
            'subtotal_amount' => $subtotal,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => $subtotal,
            'currency' => 'SYP',
            'payment_method' => 'cash',
            'issued_at' => now(),
        ];
    }
}
