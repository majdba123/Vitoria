<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    public const TYPE_AGRICULTURE = 'agriculture';

    public const TYPE_VETERINARY = 'veterinary';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'logo',
        'icon',
        'icon_class',
        'commission',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'commission' => 'decimal:2',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_AGRICULTURE => 'Agriculture',
            self::TYPE_VETERINARY => 'Veterinary',
        ];
    }

    /**
     * Get the subcategories for this category.
     */
    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class);
    }

    /**
     * Vendors allowed to sell in this category.
     */
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class)->withTimestamps();
    }
}
