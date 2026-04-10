<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    /** @use HasFactory<\Database\Factories\CouponFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'title',
        'description',
        'discount_type',
        'discount_value',
        'starts_at',
        'ends_at',
        'is_active',
        'status',
        'usage_limit',
        'used_count',
        'created_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'status' => 'string',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Orders that used this coupon.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public static function resolveStatus(bool $isEnabled, ?string $startsAt, ?string $endsAt): string
    {
        if (! $isEnabled) {
            return self::STATUS_PENDING;
        }

        $now = Carbon::now();
        $start = $startsAt ? Carbon::parse($startsAt) : null;
        $end = $endsAt ? Carbon::parse($endsAt) : null;

        if ($end && $end->lt($now)) {
            return self::STATUS_EXPIRED;
        }

        if (! $start || $start->lte($now)) {
            return self::STATUS_ACTIVE;
        }

        return self::STATUS_PENDING;
    }
}
