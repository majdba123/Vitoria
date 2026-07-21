<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'manufacturer_id',
        'name',
        'name_ar',
        'name_en',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'manufacturer_id' => 'integer',
        ];
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function sharedProductDetails(): HasMany
    {
        return $this->hasMany(SharedProductDetail::class);
    }
}
