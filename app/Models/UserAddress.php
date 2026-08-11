<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAddress extends Model
{
    /** @use HasFactory<\Database\Factories\UserAddressFactory> */
    use HasFactory, SoftDeletes;

    public const LABEL_HOME = 'home';

    public const LABEL_WORK = 'work';

    public const LABEL_FARM = 'farm';

    public const LABEL_CLINIC = 'clinic';

    public const LABEL_PHARMACY = 'pharmacy';

    public const LABEL_OTHER = 'other';

    /**
     * Labels reflect Vetora's actual customer base: farms, veterinary clinics
     * and pharmacies alongside ordinary home/work delivery (spec §6).
     *
     * @var list<string>
     */
    public const LABELS = [
        self::LABEL_HOME,
        self::LABEL_WORK,
        self::LABEL_FARM,
        self::LABEL_CLINIC,
        self::LABEL_PHARMACY,
        self::LABEL_OTHER,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'alternate_phone',
        'governorate',
        'city_id',
        'city',
        'district',
        'street',
        'building',
        'floor',
        'landmark',
        'postal_code',
        'latitude',
        'longitude',
        'notes',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cityRecord(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    /**
     * The subset of fields an order snapshots at creation time. Keys are the
     * order's `ship_*` columns; the address row itself is never referenced by
     * a historical order (decision D5).
     *
     * @return array<string, string|null>
     */
    public function toOrderSnapshot(): array
    {
        return [
            'ship_recipient_name' => $this->recipient_name,
            'ship_phone' => $this->phone,
            'ship_alternate_phone' => $this->alternate_phone,
            'ship_governorate' => $this->governorate,
            'ship_city' => $this->city,
            'ship_district' => $this->district,
            'ship_street' => $this->street,
            'ship_building' => $this->building,
            'ship_floor' => $this->floor,
            'ship_landmark' => $this->landmark,
            'ship_postal_code' => $this->postal_code,
            'ship_notes' => $this->notes,
        ];
    }
}
