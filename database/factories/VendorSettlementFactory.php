<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorLedgerEntry;
use App\Models\VendorSettlement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VendorSettlement>
 */
class VendorSettlementFactory extends Factory
{
    protected $model = VendorSettlement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'ledger_entry_id' => VendorLedgerEntry::factory(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'method' => 'bank_transfer',
            'settled_at' => now(),
        ];
    }
}
