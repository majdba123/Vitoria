<?php

namespace App\Models;

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
     * Determine if the user may act as a buyer (add to cart, checkout,
     * create orders). Only the normal customer type may purchase - Admin,
     * Vendor, Syndicate and Employee accounts are privileged roles and must
     * not act as buyers, even though they remain fully authenticated users
     * for their own (order-management, vendor, admin, ...) permissions.
     */
    public function canPurchase(): bool
    {
        return $this->type === self::TYPE_USER;
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
            self::TYPE_ADMIN => redirect()->route('admin.dashboard'),
            self::TYPE_VENDOR => redirect()->route('vendor.dashboard'),
            self::TYPE_SYNDICATE => redirect()->route('syndicate.dashboard'),
            self::TYPE_EMPLOYEE => redirect()->route('employee.dashboard'),
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

        return $membership !== null && $membership->role->permissions->contains('key', $permission);
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
            ->whereHas('permissions', fn ($query) => $query->where('key', $permission))
            ->exists();
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
