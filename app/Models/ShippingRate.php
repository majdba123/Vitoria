<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'shipping_zone_id',
        'shipping_method_id',
        'rate',
        'free_over_subtotal',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'free_over_subtotal' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    /**
     * The amount actually charged for a given subtotal — 0 once the
     * free-shipping threshold is met.
     */
    public function amountFor(float $subtotal): float
    {
        if ($this->free_over_subtotal !== null && $subtotal >= (float) $this->free_over_subtotal) {
            return 0.0;
        }

        return round((float) $this->rate, 2);
    }
}
