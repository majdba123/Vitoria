<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A staff member of a vendor organization, other than its owner (spec §22).
 * The owner is `vendors.user_id` and is never represented as a row here —
 * see the RBAC migration's class doc for why.
 */
class VendorMember extends Model
{
    /** @use HasFactory<\Database\Factories\VendorMemberFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REMOVED = 'removed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'user_id',
        'role_id',
        'status',
        'invited_by_user_id',
        'joined_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }
}
