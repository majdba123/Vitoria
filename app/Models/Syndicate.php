<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Syndicate extends Model
{
    /** @use HasFactory<\Database\Factories\SyndicateFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'phone',
        'email',
        'status',
        'logo',
    ];

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAgriculture(Builder $query): Builder
    {
        return $query->where('type', Category::TYPE_AGRICULTURE);
    }

    public function scopeVeterinary(Builder $query): Builder
    {
        return $query->where('type', Category::TYPE_VETERINARY);
    }

    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function isAgriculture(): bool
    {
        return $this->type === Category::TYPE_AGRICULTURE;
    }

    public function isVeterinary(): bool
    {
        return $this->type === Category::TYPE_VETERINARY;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
