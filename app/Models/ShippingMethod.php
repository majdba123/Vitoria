<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    public const STANDARD = 'standard_delivery';

    public const EXPRESS = 'express_delivery';

    public const VENDOR_DELIVERY = 'vendor_delivery';

    /**
     * @var list<string>
     */
    public const CODES = [
        self::STANDARD,
        self::EXPRESS,
        self::VENDOR_DELIVERY,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name_en',
        'name_ar',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }
}
