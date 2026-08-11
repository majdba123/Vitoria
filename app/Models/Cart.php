<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    /** @use HasFactory<\Database\Factories\CartFactory> */
    use HasFactory;

    /**
     * Guest carts are swept after this many days of inactivity (spec §5).
     * Authenticated carts persist.
     */
    public const GUEST_LIFETIME_DAYS = 30;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'session_token',
        'coupon_code',
        'last_activity_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function touchActivity(): void
    {
        $this->forceFill(['last_activity_at' => now()])->save();
    }
}
