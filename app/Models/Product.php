<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\App;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const DISCOUNT_STATUS_PENDING = 'pending';

    public const DISCOUNT_STATUS_ACTIVE = 'active';

    public const DISCOUNT_STATUS_EXPIRED = 'expired';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'category_id',
        'subcategory_id',
        'name',
        'name_ar',
        'name_en',
        'description',
        'price',
        'discount_percentage',
        'quantity',
        'is_active',
        'discount_is_active',
        'discount_starts_at',
        'discount_ends_at',
        'discount_status',
        'status',
        'rejection_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'quantity' => 'integer',
            'subcategory_id' => 'integer',
            'is_active' => 'boolean',
            'discount_is_active' => 'boolean',
            'discount_starts_at' => 'datetime',
            'discount_ends_at' => 'datetime',
            'discount_status' => 'string',
        ];
    }

    /**
     * The vendor that owns this product.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * The photos for this product.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ProductPhoto::class)->orderBy('sort_order');
    }

    /**
     * The primary photo for this product.
     */
    public function primaryPhoto(): ?ProductPhoto
    {
        return $this->photos()->where('is_primary', true)->first();
    }

    public function favouritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favourites')->withTimestamps();
    }

    /**
     * Order items containing this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Reviews for this product.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->latest();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function sharedDetail(): HasOne
    {
        return $this->hasOne(SharedProductDetail::class);
    }

    public function agriculturalDetail(): ?AgriculturalProductDetail
    {
        return $this->sharedDetail?->agriculturalDetail;
    }

    public function veterinaryDetail(): ?VeterinaryProductDetail
    {
        return $this->sharedDetail?->veterinaryDetail;
    }

    public function getLocalizedName(?string $locale = null): string
    {
        $resolvedLocale = $locale ?? App::getLocale();

        if ($resolvedLocale === 'ar') {
            return $this->name_ar ?: $this->name_en ?: $this->attributes['name'] ?? '';
        }

        return $this->name_en ?: $this->name_ar ?: $this->attributes['name'] ?? '';
    }

    public function getNameAttribute(?string $value): string
    {
        return $this->getLocalizedName();
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('status', self::STATUS_APPROVED)
            ->where('quantity', '>', 0)
            ->whereHas('vendor', fn (Builder $vendorQuery) => $vendorQuery->where('is_active', true));
    }

    public function scopeForCategoryType(Builder $query, ?string $type): Builder
    {
        if (! in_array($type, [Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY], true)) {
            return $query;
        }

        return $query->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('type', $type));
    }

    public function hasActiveDiscount(): bool
    {
        if (
            ! $this->discount_is_active ||
            ! $this->discount_percentage ||
            $this->discount_percentage <= 0 ||
            $this->discount_status !== self::DISCOUNT_STATUS_ACTIVE
        ) {
            return false;
        }

        $today = Carbon::today();
        if ($this->discount_starts_at && $this->discount_starts_at->toDateString() > $today->toDateString()) {
            return false;
        }

        if ($this->discount_ends_at && $this->discount_ends_at->toDateString() < $today->toDateString()) {
            return false;
        }

        return true;
    }

    public static function resolveDiscountStatus(
        bool $isEnabled,
        ?float $percentage,
        ?string $startsAt,
        ?string $endsAt
    ): string {
        if (! $isEnabled || ! $percentage || $percentage <= 0) {
            return self::DISCOUNT_STATUS_PENDING;
        }

        $today = Carbon::today()->toDateString();
        $start = self::normalizeDateString($startsAt);
        $end = self::normalizeDateString($endsAt);

        if ($end && $end < $today) {
            return self::DISCOUNT_STATUS_EXPIRED;
        }

        if (! $start || $start <= $today) {
            return self::DISCOUNT_STATUS_ACTIVE;
        }

        return self::DISCOUNT_STATUS_PENDING;
    }

    private static function normalizeDateString(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $candidate = trim($value);
        if (strlen($candidate) >= 10 && preg_match('/^\d{4}-\d{2}-\d{2}/', $candidate) === 1) {
            return substr($candidate, 0, 10);
        }

        return Carbon::parse($candidate)->toDateString();
    }

    public function getDiscountedPrice(): float
    {
        $price = (float) $this->price;
        if (! $this->hasActiveDiscount()) {
            return $price;
        }

        $discounted = $price - ($price * ((float) $this->discount_percentage / 100));

        return max(round($discounted, 2), 0);
    }
}
