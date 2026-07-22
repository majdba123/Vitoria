<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $product_id
 * @property string $commercial_name
 * @property list<string>|null $aliases
 * @property list<string>|null $barcodes
 * @property string|null $sku
 * @property string|null $manufacturer_name_ar
 * @property string|null $manufacturer_name_en
 * @property string|null $brand_name_ar
 * @property string|null $brand_name_en
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
        'barcodes',
        'sku',
        'manufacturer_name_ar',
        'manufacturer_name_en',
        'brand_name_ar',
        'brand_name_en',
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
        $categoryType = $this->product?->category?->type;

        if ($categoryType === Category::TYPE_VETERINARY) {
            return 'veterinary_medicine';
        }

        if ($categoryType === Category::TYPE_AGRICULTURE) {
            return $this->agriculturalDetail?->agricultural_product_type ?: 'other';
        }

        return $categoryType;
    }
}
