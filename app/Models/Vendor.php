<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    /** @use HasFactory<\Database\Factories\VendorFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const REGISTRATION_SOURCE_ADMIN = 'admin';

    public const REGISTRATION_SOURCE_SELF = 'self';

    public const BUSINESS_TYPE_AGRICULTURE = 'agriculture';

    public const BUSINESS_TYPE_VETERINARY = 'veterinary';

    public const BUSINESS_TYPE_BOTH = 'both';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'store_name',
        'business_type',
        'description',
        'address',
        'city_id',
        'latitude',
        'longitude',
        'logo',
        'is_active',
        'status',
        'registration_source',
        'commercial_register_file',
        'paid_amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'paid_amount' => 'decimal:2',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function businessTypeLabels(): array
    {
        return [
            self::BUSINESS_TYPE_AGRICULTURE => 'Agriculture',
            self::BUSINESS_TYPE_VETERINARY => 'Veterinary',
            self::BUSINESS_TYPE_BOTH => 'Both',
        ];
    }

    /**
     * The city where the store is located.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * The user account associated with this vendor.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The products belonging to this vendor.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Orders assigned to this vendor.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Categories this vendor is allowed to sell in.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    /**
     * Determine if the vendor is awaiting admin approval.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
