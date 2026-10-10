<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A border crossing, sea port or airport an import shipment enters through
 * (المنفذ). Managed by admins; selected when registering a shipment.
 */
class Port extends Model
{
    /** @use HasFactory<\Database\Factories\PortFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    public const DEFAULT_NAMES = [
        'معبر نصيب',
        'معبر جديدة يابوس',
        'معبر باب الهوى',
        'معبر البوكمال',
        'معبر كسب',
        'معبر الدبوسية',
        'مرفأ اللاذقية',
        'مرفأ طرطوس',
        'مطار دمشق الدولي',
        'مطار حلب الدولي',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function importShipments(): HasMany
    {
        return $this->hasMany(ImportShipment::class);
    }
}
