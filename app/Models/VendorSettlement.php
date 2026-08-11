<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSettlement extends Model
{
    /** @use HasFactory<\Database\Factories\VendorSettlementFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    public const METHODS = ['bank_transfer', 'cash', 'other'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'ledger_entry_id',
        'amount',
        'method',
        'reference',
        'notes',
        'settled_by_user_id',
        'settled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'settled_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(VendorLedgerEntry::class, 'ledger_entry_id');
    }

    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by_user_id');
    }
}
