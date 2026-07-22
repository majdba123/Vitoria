<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPhoto extends Model
{
    public const TYPE_PRIMARY = 'primary';

    public const TYPE_FRONT = 'front';

    public const TYPE_BACK = 'back';

    /**
     * @return list<string>
     */
    public static function allowedTypes(): array
    {
        return [
            self::TYPE_PRIMARY,
            self::TYPE_FRONT,
            self::TYPE_BACK,
        ];
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'path',
        'image_type',
        'sort_order',
        'is_primary',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'image_type' => 'string',
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * The product this photo belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
