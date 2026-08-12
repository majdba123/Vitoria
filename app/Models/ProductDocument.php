<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An official product document (spec §25) — not vendor compliance material,
 * see the migration's class doc for why those are kept separate.
 */
class ProductDocument extends Model
{
    /** @use HasFactory<\Database\Factories\ProductDocumentFactory> */
    use HasFactory;

    public const TYPE_LEAFLET = 'leaflet';

    public const TYPE_LABEL = 'label';

    public const TYPE_SAFETY_DATA_SHEET = 'safety_data_sheet';

    public const TYPE_REGISTRATION_CERTIFICATE = 'registration_certificate';

    public const TYPE_MANUFACTURER_DOCUMENT = 'manufacturer_document';

    public const TYPE_OTHER = 'other';

    /**
     * @var list<string>
     */
    public const TYPES = [
        self::TYPE_LEAFLET,
        self::TYPE_LABEL,
        self::TYPE_SAFETY_DATA_SHEET,
        self::TYPE_REGISTRATION_CERTIFICATE,
        self::TYPE_MANUFACTURER_DOCUMENT,
        self::TYPE_OTHER,
    ];

    public const SOURCE_VENDOR = 'vendor';

    public const SOURCE_ADMIN = 'admin';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_DISABLED = 'disabled';

    /**
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING_REVIEW => [self::STATUS_APPROVED, self::STATUS_REJECTED],
        self::STATUS_APPROVED => [self::STATUS_DISABLED],
        self::STATUS_REJECTED => [],
        self::STATUS_DISABLED => [],
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'vendor_id',
        'type',
        'title',
        'language',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'source',
        'status',
        'issued_at',
        'expires_at',
        'reviewed_by_user_id',
        'reviewed_at',
        'rejection_reason',
        'created_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'issued_at' => 'date',
            'expires_at' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
