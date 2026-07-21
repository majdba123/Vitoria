<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $product_id
 * @property string $commercial_name
 * @property list<string>|null $aliases
 * @property string|null $barcode
 * @property list<string>|null $barcodes
 * @property string|null $sku
 * @property int|null $manufacturer_id
 * @property int|null $brand_id
 * @property string|null $country_of_origin
 * @property string|null $registration_number
 * @property string|null $registration_status
 * @property string|float|int|null $package_size
 * @property string|null $package_unit
 * @property string|null $short_description
 * @property string|null $approved_description
 * @property list<string>|null $keywords
 */
class SharedProductDetail extends Model
{
    use HasFactory;

    protected $table = 'shared_product_details';

    /**
     * @var list<string>
     */
    protected $appends = [
        'category_id',
        'status',
        'product_type',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'commercial_name',
        'aliases',
        'barcode',
        'barcodes',
        'sku',
        'manufacturer_id',
        'brand_id',
        'country_of_origin',
        'registration_number',
        'registration_status',
        'package_size',
        'package_unit',
        'short_description',
        'approved_description',
        'keywords',
        'deleted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'manufacturer_id' => 'integer',
            'brand_id' => 'integer',
            'aliases' => 'array',
            'barcodes' => 'array',
            'package_size' => 'decimal:2',
            'keywords' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function agriculturalDetail(): HasOne
    {
        return $this->hasOne(AgriculturalProductDetail::class, 'shared_product_detail_id');
    }

    public function veterinaryDetail(): HasOne
    {
        return $this->hasOne(VeterinaryProductDetail::class, 'shared_product_detail_id');
    }

    public function getCategoryIdAttribute(): ?int
    {
        return $this->product?->category_id;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->product?->status;
    }

    public function getProductTypeAttribute(): ?string
    {
        return $this->product?->category?->type;
    }
}
