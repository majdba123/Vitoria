<?php

namespace App\Models;

use App\Support\AdminPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * User type constants.
     */
    public const TYPE_USER = 0;

    public const TYPE_ADMIN = 1;

    public const TYPE_VENDOR = 2;

    public const TYPE_SYNDICATE = 3;

    public const TYPE_EMPLOYEE = 4;

    /**
     * Read-only workspace over the combined agriculture and veterinary
     * syndicate domains. Provisioned only via `php artisan ministry:provision`.
     */
    public const TYPE_MINISTRY = 5;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone_number',
        'national_id',
        'age',
        'membership_number',
        'city_id',
        'latitude',
        'longitude',
        'timezone',
        'locale',
        'preferred_product_type',
        'type',
        'email',
        'avatar',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'type' => 'integer',
            'age' => 'integer',
        ];
    }

    /**
     * Determine if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->type === self::TYPE_ADMIN;
    }

    /**
     * Determine if the user is a vendor.
     */
    public function isVendor(): bool
    {
        return $this->type === self::TYPE_VENDOR;
    }

    /**
     * Determine if the user is a syndicate agent.
     */
    public function isSyndicate(): bool
    {
        return $this->type === self::TYPE_SYNDICATE;
    }

    /**
     * Determine if the user is an employee.
     */
    public function isEmployee(): bool
    {
        return $this->type === self::TYPE_EMPLOYEE;
    }

    /**
     * Determine if the user is the Ministry account.
     */
    public function isMinistry(): bool
    {
        return $this->type === self::TYPE_MINISTRY;
    }

    /**
     * Determine if the user may act as a buyer (add to cart, checkout,
     * create orders). Normal customers and vendors (buying from another
     * vendor's store, never their own - see CartService/CheckoutService)
     * may purchase. Admin, Syndicate and Employee accounts remain
     * privileged-only and must not act as buyers, even though they stay
     * fully authenticated users for their own (order-management, admin, ...)
     * permissions.
     */
    public function canPurchase(): bool
    {
        return in_array($this->type, [self::TYPE_USER, self::TYPE_VENDOR], true);
    }

    /**
     * Where an already-authenticated user should land instead of /login or
     * /register. Lives here (not as a routes/web.php closure-scoped helper)
     * so it still resolves once routes are cached in production - cached
     * routes never re-load routes/web.php, so a function defined inline
     * there via `if (!function_exists())` silently disappears.
     */
    public function redirectAfterLogin(): \Illuminate\Http\RedirectResponse
    {
        return match ($this->type) {
            self::TYPE_ADMIN => redirect()->route('admin.home'),
            self::TYPE_VENDOR => redirect()->route('vendor.dashboard'),
            self::TYPE_SYNDICATE => redirect()->route('syndicate.dashboard'),
            self::TYPE_EMPLOYEE => redirect()->route('employee.dashboard'),
            self::TYPE_MINISTRY => redirect()->route('ministry.dashboard'),
            default => $this->preferred_product_type
                ? redirect()->route('home')
                : redirect()->route('product-type.select'),
        };
    }

    /**
     * The city of this user.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * The vendor profile *owned* by this user. Unchanged by vendor staff
     * (spec §22) — a staff member never gets a row here, only in
     * `vendorMemberships()`. Use `managedVendor()` to resolve "the vendor
     * this user may act on", which covers both.
     */
    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    /**
     * Every vendor-staff row this user holds, active or removed.
     */
    public function vendorMemberships(): HasMany
    {
        return $this->hasMany(VendorMember::class);
    }

    /**
     * The single active staff membership this user currently holds, if any.
     * A user is expected to be active staff for at most one vendor at a
     * time (enforced in VendorStaffService, not the schema).
     */
    public function activeVendorMembership(): ?VendorMember
    {
        return $this->vendorMemberships()
            ->where('status', VendorMember::STATUS_ACTIVE)
            ->with('vendor')
            ->first();
    }

    /**
     * The vendor this user may act on — as owner if `vendor()` resolves,
     * otherwise as active staff. Every controller and policy that needs
     * "which vendor is this authenticated user managing" should call this
     * rather than reading `vendor` directly, so vendor staff (spec §22)
     * transparently works everywhere ownership already did.
     */
    public function managedVendor(): ?Vendor
    {
        return $this->vendor ?: $this->activeVendorMembership()?->vendor;
    }

    /**
     * Whether this user may perform `$permission` against `$vendor`.
     *
     * The owner bypasses the permissions table entirely and unconditionally
     * returns true — a missing or misconfigured role can never lock an
     * owner out of their own vendor. Staff must hold an active membership
     * whose role grants the permission (spec §23).
     */
    public function hasVendorPermission(Vendor $vendor, string $permission): bool
    {
        if ((int) $vendor->user_id === (int) $this->id) {
            return true;
        }

        $membership = $this->vendorMemberships()
            ->where('vendor_id', $vendor->id)
            ->where('status', VendorMember::STATUS_ACTIVE)
            ->with('role.permissions')
            ->first();

        return $membership !== null
            && $membership->role->scope === Role::SCOPE_VENDOR
            && $membership->role->permissions->contains('key', $permission);
    }

    /**
     * Every role assigned to this marketplace employee (stakeholder review
     * #24). An employee may hold more than one role — e.g. both catalog
     * moderation and order review — so permissions are the union across all
     * assigned roles.
     */
    public function employeeRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'employee_roles')->withTimestamps();
    }

    /**
     * Whether this employee's assigned role(s) grant `$permission`.
     *
     * Admins are never routed through this — they bypass via
     * ProductPolicy/OrderPolicy checking `isAdmin()` first, the same pattern
     * `hasVendorPermission()` uses for vendor owners. An employee with no
     * assigned role has no permissions at all, rather than falling back to
     * the old all-or-nothing behaviour.
     */
    public function hasEmployeePermission(string $permission): bool
    {
        return $this->employeeRoles()
            ->where('roles.scope', Role::SCOPE_EMPLOYEE)
            ->whereHas('permissions', fn ($query) => $query->where('key', $permission))
            ->exists();
    }

    /**
     * Every platform-admin role assigned to this user. Only roles whose
     * `scope` is `admin` are ever honoured from this table.
     */
    public function adminRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_roles')
            ->withPivot('assigned_by_user_id')
            ->withTimestamps();
    }

    /**
     * Whether this administrator holds the protected `super_admin` role —
     * the only unrestricted back-office capability. An admin-type account
     * without it is a delegated administrator limited to its roles.
     */
    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && $this->resolveAdminAccess()['super'];
    }

    /**
     * Effective back-office permission keys: the full catalog for a super
     * administrator, otherwise the union of the admin-scoped roles' keys
     * (restricted to the catalog). Non-admin accounts have none, whatever
     * roles they hold in other scopes.
     *
     * @return list<string>
     */
    public function adminPermissionKeys(): array
    {
        return $this->isAdmin() ? $this->resolveAdminAccess()['keys'] : [];
    }

    /**
     * Whether this administrator may perform the back-office action `$key`.
     */
    public function hasAdminPermission(string $key): bool
    {
        return in_array($key, $this->adminPermissionKeys(), true);
    }

    /**
     * Resolves admin access once per request (one query). Memoized on the
     * current request rather than the model instance, so a role change is
     * honoured on the very next request — no cross-request cache to
     * invalidate, and long-lived model instances never serve revoked
     * permissions. Console processes (queue workers, scheduled commands)
     * reuse one request object for their whole lifetime, so they resolve
     * fresh every time instead of memoizing.
     *
     * @return array{super: bool, keys: list<string>}
     */
    private function resolveAdminAccess(): array
    {
        $request = request();
        $memoKey = 'rbac.admin_access.'.$this->id;
        $memoize = ! app()->runningInConsole() || app()->runningUnitTests();

        if ($memoize && $request->attributes->has($memoKey)) {
            return $request->attributes->get($memoKey);
        }

        $roles = $this->adminRoles()
            ->where('roles.scope', Role::SCOPE_ADMIN)
            ->with('permissions:id,key')
            ->get(['roles.id', 'roles.key']);

        $isSuper = $roles->contains(fn (Role $role) => $role->isSuperAdminRole());
        $keys = $isSuper
            ? AdminPermissions::all()
            : array_values(array_intersect(
                AdminPermissions::all(),
                $roles->flatMap(fn (Role $role) => $role->permissions->pluck('key'))->unique()->all(),
            ));

        $access = ['super' => $isSuper, 'keys' => $keys];
        if ($memoize) {
            $request->attributes->set($memoKey, $access);
        }

        return $access;
    }

    /**
     * Drop the memoized admin access so a role change made earlier in this
     * request is reflected immediately.
     */
    public function forgetAdminAccess(): void
    {
        request()->attributes->remove('rbac.admin_access.'.$this->id);
    }

    /**
     * The syndicate profile linked to this user.
     */
    public function syndicate(): HasOne
    {
        return $this->hasOne(Syndicate::class);
    }

    /**
     * Products this user has added to favourites.
     */
    public function favouriteProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favourites')->withTimestamps();
    }

    /**
     * Orders created by this user.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Saved delivery addresses, default first.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class)->orderByDesc('is_default')->orderByDesc('id');
    }

    /**
     * The user's persistent server-side cart.
     */
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * Product reviews written by this user.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * Admin notifications this user has marked as read (pivot has read_at).
     */
    public function notificationReads(): BelongsToMany
    {
        return $this->belongsToMany(AdminNotification::class, 'admin_notification_reads', 'user_id', 'admin_notification_id')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Contact messages submitted by this user.
     */
    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
    }
}
