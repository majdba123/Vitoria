<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    /**
     * Allowed forward transitions. Any status change not expressed here is
     * rejected server-side, so the frontend can never submit an arbitrary
     * status (spec §8).
     *
     * `completed` is retained as the terminal success state rather than being
     * renamed to `delivered`, so historical orders stay valid (decision D4).
     *
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
        self::STATUS_CONFIRMED => [self::STATUS_PREPARING, self::STATUS_CANCELLED],
        self::STATUS_PREPARING => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
        self::STATUS_SHIPPED => [self::STATUS_OUT_FOR_DELIVERY],
        self::STATUS_OUT_FOR_DELIVERY => [self::STATUS_COMPLETED],
        self::STATUS_COMPLETED => [],
        self::STATUS_CANCELLED => [],
    ];

    /**
     * Statuses from which stock has not yet left the vendor, so cancelling
     * restores product quantity (spec §10).
     *
     * @var list<string>
     */
    public const CANCELLABLE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
    ];

    /**
     * A return can only be requested once the order has actually been
     * delivered — returning something that never arrived is a cancellation,
     * not a return (spec §12).
     *
     * @var list<string>
     */
    public const RETURNABLE_STATUSES = [
        self::STATUS_COMPLETED,
    ];

    public const CANCEL_REASONS = [
        'customer_changed_mind',
        'wrong_order',
        'unavailable_product',
        'vendor_issue',
        'delivery_issue',
        'payment_issue',
        'duplicate_order',
        'other',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_number',
        'user_id',
        'vendor_id',
        'coupon_id',
        'coupon_code',
        'coupon_type',
        'coupon_value',
        'status',
        'payment_way',
        'items_count',
        'subtotal_amount',
        'coupon_discount_amount',
        'shipping_total',
        'tax_total',
        'grand_total',
        'currency',
        'total_amount',
        'ship_recipient_name',
        'ship_phone',
        'ship_alternate_phone',
        'ship_governorate',
        'ship_city',
        'ship_district',
        'ship_street',
        'ship_building',
        'ship_floor',
        'ship_landmark',
        'ship_postal_code',
        'ship_notes',
        'cancelled_at',
        'cancelled_by_user_id',
        'cancellation_reason',
        'cancellation_notes',
    ];

    // Note: `stock_restored_at` is deliberately absent from $fillable. It is
    // claimed only by a conditional UPDATE in OrderCancellationService, which is
    // what makes stock restoration exactly-once (decision D1 / audit R1).

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'items_count' => 'integer',
            'subtotal_amount' => 'decimal:2',
            'coupon_value' => 'decimal:2',
            'coupon_discount_amount' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'stock_restored_at' => 'datetime',
        ];
    }

    /**
     * Whether `$to` is a legal next status from the order's current status.
     */
    public function canTransitionTo(string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Whether the order is in a state where cancelling should return stock.
     */
    public function isCancellable(): bool
    {
        return in_array($this->status, self::CANCELLABLE_STATUSES, true);
    }

    /**
     * Whether a return may be requested against this order at all.
     * Per-item, per-quantity eligibility is enforced by ReturnService.
     */
    public function isReturnable(): bool
    {
        return in_array($this->status, self::RETURNABLE_STATUSES, true);
    }

    /**
     * Flat, display-ready delivery address snapshot. Reads only the order's own
     * columns — never the (mutable) user_addresses row (decision D5).
     *
     * @return array<string, string|null>
     */
    public function shippingAddress(): array
    {
        return [
            'recipient_name' => $this->ship_recipient_name,
            'phone' => $this->ship_phone,
            'alternate_phone' => $this->ship_alternate_phone,
            'governorate' => $this->ship_governorate,
            'city' => $this->ship_city,
            'district' => $this->ship_district,
            'street' => $this->ship_street,
            'building' => $this->ship_building,
            'floor' => $this->ship_floor,
            'landmark' => $this->ship_landmark,
            'postal_code' => $this->ship_postal_code,
            'notes' => $this->ship_notes,
        ];
    }

    /**
     * User who placed the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vendor that owns this order.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Coupon applied to this order.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Order line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Audit trail of every status transition, oldest first.
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    /**
     * User who cancelled the order, when applicable.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    /**
     * The order's single payment record (spec §11).
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Return requests raised against this order (spec §12).
     */
    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class);
    }
}
