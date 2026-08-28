<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'category_id_snapshot',
        'category_type',
        'commission_rate_snapshot',
        'product_name',
        'original_unit_price',
        'has_discount',
        'applied_discount_percentage',
        'unit_price',
        'quantity',
        'line_total',
        'discount_amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'original_unit_price' => 'decimal:2',
            'category_id_snapshot' => 'integer',
            'commission_rate_snapshot' => 'decimal:2',
            'has_discount' => 'boolean',
            'applied_discount_percentage' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'line_total' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    /**
     * Parent order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Original product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope historical lines by their checkout-time domain. The relationship
     * fallback exists only for legacy rows that could not be backfilled.
     */
    public function scopeForDomain(Builder $query, string $domain): Builder
    {
        return $query->where(function (Builder $builder) use ($domain): void {
            $builder->where('category_type', $domain)
                ->orWhere(function (Builder $legacy) use ($domain): void {
                    $legacy->whereNull('category_type')
                        ->whereHas('product.category', fn (Builder $category) => $category->where('type', $domain));
                });
        });
    }
}
