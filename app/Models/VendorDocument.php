<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A vendor compliance document (spec §24).
 */
class VendorDocument extends Model
{
    /** @use HasFactory<\Database\Factories\VendorDocumentFactory> */
    use HasFactory;

    public const TYPE_COMMERCIAL_REGISTRATION = 'commercial_registration';

    public const TYPE_BUSINESS_LICENSE = 'business_license';

    public const TYPE_TAX_REGISTRATION = 'tax_registration';

    public const TYPE_INDUSTRY_LICENSE = 'industry_license';

    public const TYPE_OTHER = 'other';

    /**
     * @var list<string>
     */
    public const TYPES = [
        self::TYPE_COMMERCIAL_REGISTRATION,
        self::TYPE_BUSINESS_LICENSE,
        self::TYPE_TAX_REGISTRATION,
        self::TYPE_INDUSTRY_LICENSE,
        self::TYPE_OTHER,
    ];

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Spec §24 lists `draft` as a lifecycle state, but nothing in this
     * feature produces a documentless draft row — a row is only ever
     * created together with an uploaded file, which is always a real
     * submission. `draft` is intentionally not reachable (same reasoning as
     * decision D4 excluding `pickup`: no workflow in this repository needs
     * it).
     *
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING_REVIEW => [self::STATUS_VERIFIED, self::STATUS_REJECTED],
        self::STATUS_VERIFIED => [self::STATUS_SUSPENDED, self::STATUS_EXPIRED],
        self::STATUS_REJECTED => [],
        self::STATUS_EXPIRED => [],
        self::STATUS_SUSPENDED => [],
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'type',
        'title',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'issued_at',
        'expires_at',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
        'rejection_reason',
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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
