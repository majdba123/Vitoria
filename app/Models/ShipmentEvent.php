<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentEvent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'shipment_id',
        'previous_status',
        'new_status',
        'changed_by_user_id',
        'actor_type',
        'notes',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
