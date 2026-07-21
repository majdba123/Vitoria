<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacturer extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'country',
        'website',
        'status',
    ];

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function sharedProductDetails(): HasMany
    {
        return $this->hasMany(SharedProductDetail::class);
    }
}
