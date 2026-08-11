<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A customer's request to return part or all of an order (spec §12).
 *
 * Table is `order_returns`: RETURN is a reserved word in MySQL, which
 * production uses.
 */
class OrderReturn extends Model
{
    /** @use HasFactory<\Database\Factories\OrderReturnFactory> */
    use HasFactory;

    protected $table = 'order_returns';

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Legal transitions. As with orders, nothing writes `status` directly —
     * ReturnService is the only path, so an arbitrary status cannot arrive
     * from the frontend.
     *
     * Stock returns to products.quantity on `received`, not on `approved`:
     * approval is a decision, receipt is the physical fact.
     *
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        self::STATUS_REQUESTED => [self::STATUS_UNDER_REVIEW, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED],
        self::STATUS_UNDER_REVIEW => [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED],
        self::STATUS_APPROVED => [self::STATUS_RECEIVED, self::STATUS_CANCELLED],
        self::STATUS_RECEIVED => [self::STATUS_COMPLETED],
        self::STATUS_REJECTED => [],
        self::STATUS_COMPLETED => [],
        self::STATUS_CANCELLED => [],
    ];

    /**
     * @var list<string>
     */
    public const REASONS = [
        'damaged_on_arrival',
        'wrong_item_received',
        'expired_product',
        'not_as_described',
        'missing_items',
        'quality_issue',
        'other',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'return_number',
        'order_id',
        'user_id',
        'vendor_id',
        'status',
        'reason',
        'notes',
        'refundable_amount',
        'requested_at',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_notes',
    ];

    // `stock_restored_at` is deliberately not fillable — claimed only by a
    // conditional UPDATE in ReturnService (decision D1).

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'refundable_amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'stock_restored_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'order_return_id');
    }

    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class, 'order_return_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
