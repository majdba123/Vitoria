<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's opt-out of one non-critical notification category (spec §33).
 * Absence of a row means enabled — see the migration's class doc.
 */
class NotificationPreference extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationPreferenceFactory> */
    use HasFactory;

    /**
     * Order placed, order status changed, return/refund status — the
     * transaction-critical category §33 says must never be disabled.
     */
    public const CATEGORY_ORDER_UPDATES = 'order_updates';

    /**
     * Access/security-relevant notices — e.g. being added as vendor staff.
     * Also never disabled: knowing your account gained access somewhere is
     * a security notice, not a marketing one.
     */
    public const CATEGORY_ACCOUNT_SECURITY = 'account_security';

    /**
     * Vendor/product document submitted or reviewed — operationally useful,
     * not transaction-critical. Mutable.
     */
    public const CATEGORY_VENDOR_COMPLIANCE = 'vendor_compliance';

    /**
     * New product announcements, discount announcements — classic
     * promotional content. Mutable.
     */
    public const CATEGORY_MARKETING = 'marketing';

    /**
     * @var list<string>
     */
    public const CRITICAL_CATEGORIES = [
        self::CATEGORY_ORDER_UPDATES,
        self::CATEGORY_ACCOUNT_SECURITY,
    ];

    /**
     * @var list<string>
     */
    public const MUTABLE_CATEGORIES = [
        self::CATEGORY_VENDOR_COMPLIANCE,
        self::CATEGORY_MARKETING,
    ];

    /**
     * @var list<string>
     */
    public const ALL_CATEGORIES = [
        self::CATEGORY_ORDER_UPDATES,
        self::CATEGORY_ACCOUNT_SECURITY,
        self::CATEGORY_VENDOR_COMPLIANCE,
        self::CATEGORY_MARKETING,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = ['user_id', 'category', 'enabled'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
