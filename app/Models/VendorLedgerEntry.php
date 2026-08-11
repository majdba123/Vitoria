<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One immutable line in a vendor's financial history (spec §20). Never
 * updated or deleted — see VendorLedgerService for why.
 */
class VendorLedgerEntry extends Model
{
    /** @use HasFactory<\Database\Factories\VendorLedgerEntryFactory> */
    use HasFactory;

    public const TYPE_SALE = 'sale';

    public const TYPE_COMMISSION = 'commission';

    public const TYPE_REFUND = 'refund';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_SETTLEMENT = 'settlement';

    public const DIRECTION_CREDIT = 'credit';

    public const DIRECTION_DEBIT = 'debit';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'order_id',
        'type',
        'direction',
        'amount',
        'description',
        'created_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /**
     * The amount as it affects the vendor's balance — positive for a credit,
     * negative for a debit.
     */
    public function signedAmount(): float
    {
        return $this->direction === self::DIRECTION_CREDIT
            ? (float) $this->amount
            : -(float) $this->amount;
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
