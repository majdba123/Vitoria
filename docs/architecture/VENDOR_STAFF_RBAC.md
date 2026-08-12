# Vetora — Vendor Staff & RBAC

Scope: what is **implemented and tested**, covering spec §22 (vendor staff) and the
vendor-scoped slice of §23 (RBAC) that decision D3 deferred until vendor staff gave it a
real trigger. See [D15](IMPLEMENTATION_DECISIONS.md#d15--vendor-staff-additive-membership-owner-untouched-permissions-scoped-to-vendors-only)
for the reasoning; this document is the reference for the schema, roles, and endpoints.

---

## 1. Schema

```
vendors.user_id ──────────────────────► owner (unchanged, unmigrated)

vendor_members                          roles ◄──── role_permissions ────► permissions
  vendor_id ─► vendors.id                 key                                key
  user_id   ─► users.id                   name_en / name_ar
  role_id   ─► roles.id                   is_system
  status  (active | removed)
  invited_by_user_id ─► users.id
  joined_at
```

The owner is **not** a `vendor_members` row. `roles`/`permissions`/`role_permissions` are
generic, reusable primitives; today only `vendor_members.role_id` assigns one.

---

## 2. Resolving "which vendor can this user act on"

`User::vendor(): HasOne` is unchanged — still literally `vendors.user_id = users.id`,
still used by vendor self-registration to create the owner's `Vendor` row.

`User::managedVendor(): ?Vendor` is the new resolver every controller and policy should
call instead:

```php
public function managedVendor(): ?Vendor
{
    return $this->vendor ?: $this->activeVendorMembership()?->vendor;
}
```

Owner check is always the fast path (a direct `hasOne`, no join); staff resolution only
runs when the user does not own a vendor themselves.

---

## 3. Permission enforcement

```php
public function hasVendorPermission(Vendor $vendor, string $permission): bool
{
    if ((int) $vendor->user_id === (int) $this->id) {
        return true; // owner bypass — never consults the permissions table
    }

    $membership = $this->vendorMemberships()
        ->where('vendor_id', $vendor->id)
        ->where('status', VendorMember::STATUS_ACTIVE)
        ->with('role.permissions')
        ->first();

    return $membership !== null && $membership->role->permissions->contains('key', $permission);
}
```

Called from policies (`OrderPolicy::updateStatus`/`cancel`, `OrderReturnPolicy::review`/
`initiateRefund`, `ShipmentPolicy::manage`) and directly in controllers that have no
natural policy target (`VendorProfileController::update`, `Vendor\LedgerController`,
`Vendor\StaffController`, the mutating actions in `ProductController`).

---

## 4. Roles and permissions

| Permission | Owner | Manager | Catalog Manager | Order Manager | Finance | Viewer |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| `products.view` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `products.manage` | ✓ | ✓ | ✓ | | | |
| `orders.view` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `orders.update` | ✓ | ✓ | | ✓ | | |
| `orders.cancel` | ✓ | ✓ | | ✓ | | |
| `returns.view` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `returns.review` | ✓ | ✓ | | ✓ | | |
| `returns.refund` | ✓ | ✓ | | | ✓ | |
| `refunds.view` | ✓ | ✓ | | | ✓ | |
| `shipments.view` | ✓ | ✓ | ✓ | ✓ | | ✓ |
| `shipments.manage` | ✓ | ✓ | | ✓ | | |
| `invoices.view` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `ledger.view` | ✓ | ✓ | | | ✓ | |
| `settlements.view` | ✓ | ✓ | | | ✓ | |
| `staff.manage` | ✓ | ✓ | | | | |
| `profile.manage` | ✓ | ✓ | | | | |

Owner and Manager are functionally identical in permissions — the distinction is that
Owner is `vendors.user_id` (irrevocable, can never be removed) and Manager is a
`vendor_members` row (revocable by another Owner/Manager). All six roles are seeded by
the RBAC migration directly (production runs `migrate --force` only, never `db:seed` —
same reasoning as the shipping migration's seeded defaults).

---

## 5. Endpoints

| Method | Path | Requires |
|---|---|---|
| GET | `/api/vendor/staff` | active staff or owner |
| POST | `/api/vendor/staff` | `staff.manage` |
| PATCH | `/api/vendor/staff/{id}` | `staff.manage` |
| DELETE | `/api/vendor/staff/{id}` | `staff.manage` |
| GET | `/api/admin/vendors/{vendor}/staff` | admin (read-only) |

`POST` body: `{identifier, role}` — `identifier` is an existing user's email or phone
number; `role` must be one of `Role::INVITABLE_KEYS` (excludes `owner`).

---

## 6. Business rules (`VendorStaffService`)

- The target account must already exist — no invite-by-email-to-a-new-user flow.
- A vendor owner cannot be added as another vendor's staff.
- A user already active staff at a **different** vendor cannot be added — one active
  membership at a time, application-enforced (not a schema constraint, since a user could
  legitimately hold a `removed` row at one vendor and an `active` one at another).
- Re-adding a `removed` member reactivates the same `vendor_members` row (new role,
  `status = 'active'`, fresh `joined_at`) rather than creating a duplicate — the
  `unique(vendor_id, user_id)` constraint makes a duplicate impossible outright.
- Adding a non-vendor-type user flips their `type` to `TYPE_VENDOR`, since
  `EnsureUserIsVendor` gates on `type` before anything else runs.
- Removing sets `status = 'removed'`; the row is never deleted, preserving who was staff
  and when.

---

## 7. Vendor isolation

Every vendor-facing controller already scoped its query or policy check by `vendor_id`
before staff existed (spec §54, decision D3). Because `managedVendor()` still resolves to
exactly one vendor per request — the owned one, or the single active membership — that
isolation carries over unchanged: a staff member of vendor A gets 403 (or 404, where a
controller scopes its query directly rather than authorizing a resolved model) on vendor
B's orders, returns, shipments, and ledger, with no code path that lets one membership
leak into another vendor's data.
