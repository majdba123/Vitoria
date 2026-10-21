<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
     * Marketplace-employee roles (stakeholder review #24). Assigned via
     * `employee_roles`, never `vendor_members` — employees are not scoped
     * to a vendor.
     */
    public const KEY_CATALOG_MODERATOR = 'catalog_moderator';

    public const KEY_ORDER_REVIEWER = 'order_reviewer';

    /**
     * The only unrestricted platform-admin capability. Holders bypass the
     * admin permission catalog; the role itself can never be edited, deleted,
     * or granted except by another super administrator.
     */
    public const KEY_SUPER_ADMIN = 'super_admin';

    /**
     * Which actor type a role may be held by. A role is only ever honoured
     * through the assignment table that matches its scope: `vendor_members`
     * (vendor), `employee_roles` (employee), `admin_roles` (admin).
     */
    public const SCOPE_VENDOR = 'vendor';

    public const SCOPE_EMPLOYEE = 'employee';

    public const SCOPE_ADMIN = 'admin';

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
    protected $fillable = ['key', 'scope', 'name_en', 'name_ar', 'is_system'];

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

    /**
     * Platform administrators holding this (admin-scoped) role.
     */
    public function adminUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_roles')
            ->withPivot('assigned_by_user_id')
            ->withTimestamps();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     */
    public function scopeAdmin(Builder $query): void
    {
        $query->where('scope', self::SCOPE_ADMIN);
    }

    public function isSuperAdminRole(): bool
    {
        return $this->key === self::KEY_SUPER_ADMIN;
    }
}
