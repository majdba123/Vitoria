<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Delivery tracking for one order (spec §14).
 *
 * Deliberately a separate entity from the order's own status: the happy path
 * (`preparing`/`shipped`/`out_for_delivery`/`delivered`) is kept in lockstep
 * with the order by ShippingService whenever OrderStatusService transitions
 * the order, so there is one source of truth for that part. `failed` and
 * `returned` exist only here — a delivery attempt failing is a courier-side
 * fact that does not by itself cancel the order.
 */
class Shipment extends Model
{
    /** @use HasFactory<\Database\Factories\ShipmentFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RETURNED = 'returned';

    /**
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_PREPARING, self::STATUS_FAILED],
        self::STATUS_PREPARING => [self::STATUS_SHIPPED, self::STATUS_FAILED],
        self::STATUS_SHIPPED => [self::STATUS_OUT_FOR_DELIVERY, self::STATUS_FAILED],
        self::STATUS_OUT_FOR_DELIVERY => [self::STATUS_DELIVERED, self::STATUS_FAILED],
        self::STATUS_FAILED => [self::STATUS_OUT_FOR_DELIVERY, self::STATUS_RETURNED],
        self::STATUS_DELIVERED => [],
        self::STATUS_RETURNED => [],
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'shipping_method_id',
        'shipping_zone_id',
        'tracking_number',
        'carrier_name',
        'status',
        'shipped_at',
        'delivered_at',
        'failed_at',
        'failure_reason',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
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

    public function method(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class)->orderBy('id');
    }
}
