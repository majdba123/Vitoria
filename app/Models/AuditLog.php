<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One immutable audit entry (spec §35). Never updated or deleted — a
 * correction is a new entry, the same convention `vendor_ledger_entries`
 * already established (decision D14).
 */
class AuditLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'actor_user_id',
        'actor_type',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'request_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
