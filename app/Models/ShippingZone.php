<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_en',
        'name_ar',
        'is_default',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function governorates(): HasMany
    {
        return $this->hasMany(ShippingZoneGovernorate::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }
}
