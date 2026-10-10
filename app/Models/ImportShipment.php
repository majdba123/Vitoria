<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An import shipment (الشحنة): the merchant's paperwork, the port it
 * entered through, its line items, sticker fees, the syndicate clearance
 * (براءة الذمة) and the release decision (قرار الإفراج).
 */
class ImportShipment extends Model
{
    /** @use HasFactory<\Database\Factories\ImportShipmentFactory> */
    use HasFactory;

    /**
     * @var list<int>
     */
    public const STICKER_PRICES = [200, 400];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'syndicate_id',
        'port_id',
        'created_by',
        'merchant_name',
        'merchant_register_number',
        'merchant_address',
        'invoice_number',
        'stickers_count',
        'sticker_price',
        'clearance_number',
        'release_decision',
    ];

    /**
     * @var list<string>
     */
    protected $appends = ['stickers_total'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stickers_count' => 'integer',
            'sticker_price' => 'integer',
        ];
    }

    public function getStickersTotalAttribute(): int
    {
        return (int) $this->stickers_count * (int) $this->sticker_price;
    }

    /**
     * A syndicate user only ever sees its own syndicate's shipments; admins
     * and employees see all of them.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSyndicate()) {
            return $query->where('syndicate_id', $user->syndicate?->id ?? 0);
        }

        return $query;
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function syndicate(): BelongsTo
    {
        return $this->belongsTo(Syndicate::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ImportShipmentItem::class)->orderBy('id');
    }
}
