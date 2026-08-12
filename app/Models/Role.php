<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const KEY_OWNER = 'owner';

    public const KEY_MANAGER = 'manager';

    public const KEY_CATALOG_MANAGER = 'catalog_manager';

    public const KEY_ORDER_MANAGER = 'order_manager';

    public const KEY_FINANCE = 'finance';

    public const KEY_VIEWER = 'viewer';

    /**
     * Roles that may actually be assigned to a `vendor_members` row.
     * `owner` is excluded — ownership is `vendors.user_id`, never a
     * membership row (spec §22; see the RBAC migration's class doc).
     *
     * @var list<string>
     */
    public const INVITABLE_KEYS = [
        self::KEY_MANAGER,
        self::KEY_CATALOG_MANAGER,
        self::KEY_ORDER_MANAGER,
        self::KEY_FINANCE,
        self::KEY_VIEWER,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = ['key', 'name_en', 'name_ar', 'is_system'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withTimestamps();
    }

    public function vendorMembers(): HasMany
    {
        return $this->hasMany(VendorMember::class);
    }
}
