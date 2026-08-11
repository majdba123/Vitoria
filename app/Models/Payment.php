<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    public const STATUS_REFUNDED = 'refunded';

    /**
     * Cash on delivery — the only provider the platform configures. No gateway
     * is stubbed or presented as working (decision D9).
     */
    public const PROVIDER_COD = 'cod';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'provider',
        'method',
        'status',
        'amount',
        'refunded_amount',
        'currency',
        'provider_reference',
        'paid_at',
        'failed_at',
        'failure_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * How much of this payment can still be refunded.
     *
     * Only a settled payment can be refunded at all: refunding a COD order
     * that was never collected would move money that never arrived.
     */
    public function refundableAmount(): float
    {
        if ($this->status === self::STATUS_PENDING || $this->status === self::STATUS_FAILED) {
            return 0.0;
        }

        return round(max((float) $this->amount - (float) $this->refunded_amount, 0), 2);
    }
}
