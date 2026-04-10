<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdminNotification extends Model
{
    public const TYPE_PUBLIC = 'public';

    public const TYPE_PRIVATE = 'private';

    public const ACTION_PRODUCT = 'product';

    public const ACTION_ORDER = 'order';

    protected $fillable = [
        'title',
        'body',
        'type',
        'action_type',
        'action_id',
        'sent_by',
    ];

    /**
     * Admin user who sent the notification.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Users who received this notification (for private type).
     */
    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_notification_recipients', 'admin_notification_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Read records (user_id, read_at).
     */
    public function reads(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_notification_reads', 'admin_notification_id', 'user_id')
            ->withPivot('read_at')
            ->withTimestamps();
    }
}
