<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subcategory extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = [
        'image_url',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'image',
        'icon_class',
    ];

    /**
     * Get the category that owns this subcategory.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the products for this subcategory.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeForCategoryType(Builder $query, ?string $type): Builder
    {
        if (! in_array($type, [Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY], true)) {
            return $query;
        }

        return $query->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('type', $type));
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }
}
