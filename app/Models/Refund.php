<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    /** @use HasFactory<\Database\Factories\RefundFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_PROCESSING, self::STATUS_COMPLETED, self::STATUS_CANCELLED],
        self::STATUS_PROCESSING => [self::STATUS_COMPLETED, self::STATUS_FAILED],
        self::STATUS_COMPLETED => [],
        self::STATUS_FAILED => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
        self::STATUS_CANCELLED => [],
    ];

    /**
     * Reasons for a refund raised directly by an admin, without a return
     * behind it (e.g. a duplicate COD collection). Kept deliberately small
     * and separate from OrderReturn::REASONS, which describe why a *product*
     * came back rather than why *money* is being adjusted (spec §13).
     *
     * @var list<string>
     */
    public const ADHOC_REASONS = [
        'duplicate_payment',
        'overcharge',
        'goodwill_adjustment',
        'other',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'refund_number',
        'order_id',
        'order_return_id',
        'payment_id',
        'amount',
        'currency',
        'status',
        'reason',
        'notes',
        'initiated_by_user_id',
        'completed_at',
        'provider_reference',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class, 'order_return_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }
}
