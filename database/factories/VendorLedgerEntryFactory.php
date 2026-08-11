<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorLedgerEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VendorLedgerEntry>
 */
class VendorLedgerEntryFactory extends Factory
{
    protected $model = VendorLedgerEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'order_id' => null,
            'type' => VendorLedgerEntry::TYPE_ADJUSTMENT,
            'direction' => VendorLedgerEntry::DIRECTION_CREDIT,
            'amount' => fake()->randomFloat(2, 10, 500),
            'description' => fake()->sentence(),
        ];
    }
}
