<?php

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorMember;
use Laravel\Sanctum\Sanctum;

/**
 * Vendor staff and the RBAC behind it (spec §22, §23).
 *
 * The load-bearing properties: an owner's access is never gated by the
 * permissions table, staff access is strictly scoped to their own vendor and
 * their role's permissions, and removing staff actually revokes access.
 */
function vsrVendor(): array
{
    $owner = User::factory()->create(['type' => User::TYPE_VENDOR]);
    $vendor = Vendor::factory()->create(['user_id' => $owner->id, 'is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);

    return ['vendor' => $vendor, 'owner' => $owner];
}

/**
 * Owner adds `$target` (or a fresh customer if omitted) as staff with the
 * given role, via the real HTTP endpoint.
 *
 * @return array{staff: User, memberId: int}
 */
function vsrAddStaff(Vendor $vendor, User $owner, string $roleKey, ?User $target = null): array
{
    $target ??= User::factory()->create(['type' => User::TYPE_USER]);

    Sanctum::actingAs($owner);
    $memberId = test()->postJson('/api/vendor/staff', [
        'identifier' => $target->email,
        'role' => $roleKey,
    ])->assertCreated()->json('data.id');

    return ['staff' => $target->fresh(), 'memberId' => $memberId];
}

it('lets an owner add a staff member, who gains vendor-scoped access by role', function () {
    $context = vsrVendor();
    $result = vsrAddStaff($context['vendor'], $context['owner'], Role::KEY_CATALOG_MANAGER);

    expect($result['staff']->type)->toBe(User::TYPE_VENDOR);

    Sanctum::actingAs($result['staff']);

    // Catalog Manager can view and manage products...
    $this->getJson('/api/vendor/products')->assertOk();

    // ...but cannot update order fulfilment (no orders.update permission).
    $order = Order::factory()->create(['vendor_id' => $context['vendor']->id, 'status' => Order::STATUS_PENDING]);
    $this->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => Order::STATUS_CONFIRMED])
        ->assertForbidden();
});

it('lets an order manager update fulfilment but not manage the product catalog', function () {
    $context = vsrVendor();
    $result = vsrAddStaff($context['vendor'], $context['owner'], Role::KEY_ORDER_MANAGER);

    Sanctum::actingAs($result['staff']);

    $order = Order::factory()->create(['vendor_id' => $context['vendor']->id, 'status' => Order::STATUS_PENDING]);
    $this->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => Order::STATUS_CONFIRMED])
        ->assertOk();

    $this->postJson('/api/vendor/products/store-basic', ['name' => 'x'])->assertForbidden();
});

it('restricts a viewer to read-only access', function () {
    $context = vsrVendor();
    $result = vsrAddStaff($context['vendor'], $context['owner'], Role::KEY_VIEWER);

    Sanctum::actingAs($result['staff']);

    $this->getJson('/api/vendor/orders')->assertOk();

    $order = Order::factory()->create(['vendor_id' => $context['vendor']->id, 'status' => Order::STATUS_PENDING]);
    $this->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => Order::STATUS_CONFIRMED])->assertForbidden();
    $this->postJson('/api/vendor/staff', ['identifier' => 'nobody@x.test', 'role' => Role::KEY_VIEWER])->assertForbidden();
});

it('always grants the owner full access, independent of the permissions table', function () {
    $context = vsrVendor();
    $vendor = $context['vendor']->fresh();

    // No vendor_members row exists for the owner at all.
    expect(VendorMember::query()->where('vendor_id', $vendor->id)->where('user_id', $context['owner']->id)->exists())->toBeFalse()
        ->and($context['owner']->hasVendorPermission($vendor, 'staff.manage'))->toBeTrue()
        ->and($context['owner']->hasVendorPermission($vendor, 'anything.not.a.real.permission'))->toBeTrue();
});

it('rejects adding a staff member with no matching account', function () {
    $context = vsrVendor();
    Sanctum::actingAs($context['owner']);

    $this->postJson('/api/vendor/staff', ['identifier' => 'nobody@nowhere.test', 'role' => Role::KEY_VIEWER])
        ->assertStatus(422);
});

it('rejects adding a user who owns another vendor as staff', function () {
    $context = vsrVendor();
    $otherOwner = vsrVendor()['owner'];

    Sanctum::actingAs($context['owner']);
    $this->postJson('/api/vendor/staff', ['identifier' => $otherOwner->email, 'role' => Role::KEY_VIEWER])
        ->assertStatus(422);
});

it('rejects adding a user who is already active staff at another vendor', function () {
    $contextA = vsrVendor();
    $contextB = vsrVendor();
    $shared = User::factory()->create(['type' => User::TYPE_USER]);

    vsrAddStaff($contextA['vendor'], $contextA['owner'], Role::KEY_VIEWER, $shared);

    Sanctum::actingAs($contextB['owner']);
    $this->postJson('/api/vendor/staff', ['identifier' => $shared->email, 'role' => Role::KEY_VIEWER])
        ->assertStatus(422);
});

it('revokes access when a staff member is removed', function () {
    $context = vsrVendor();
    $result = vsrAddStaff($context['vendor'], $context['owner'], Role::KEY_MANAGER);

    Sanctum::actingAs($result['staff']);
    $this->getJson('/api/vendor/orders')->assertOk();

    Sanctum::actingAs($context['owner']);
    $this->deleteJson("/api/vendor/staff/{$result['memberId']}")->assertOk();

    Sanctum::actingAs($result['staff']->fresh());
    $this->getJson('/api/vendor/orders')->assertForbidden();

    expect($result['staff']->fresh()->managedVendor())->toBeNull();
});

it('reactivates a removed member instead of duplicating the row', function () {
    $context = vsrVendor();
    $target = User::factory()->create(['type' => User::TYPE_USER]);
    $result = vsrAddStaff($context['vendor'], $context['owner'], Role::KEY_VIEWER, $target);

    Sanctum::actingAs($context['owner']);
    $this->deleteJson("/api/vendor/staff/{$result['memberId']}")->assertOk();

    $this->postJson('/api/vendor/staff', ['identifier' => $target->email, 'role' => Role::KEY_MANAGER])
        ->assertCreated();

    expect(VendorMember::query()->where('vendor_id', $context['vendor']->id)->where('user_id', $target->id)->count())->toBe(1)
        ->and(VendorMember::query()->where('vendor_id', $context['vendor']->id)->where('user_id', $target->id)->value('status'))
        ->toBe(VendorMember::STATUS_ACTIVE);
});

it('isolates a staff member strictly to their own vendor', function () {
    $contextA = vsrVendor();
    $contextB = vsrVendor();
    $result = vsrAddStaff($contextA['vendor'], $contextA['owner'], Role::KEY_MANAGER);

    $foreignOrder = Order::factory()->create(['vendor_id' => $contextB['vendor']->id, 'status' => Order::STATUS_PENDING]);

    Sanctum::actingAs($result['staff']);
    // Vendor\OrderController::show() scopes its query by vendor_id, so a
    // foreign order is invisible (404) rather than a 403 — pre-existing
    // behaviour, unrelated to staff vs. owner.
    $this->getJson("/api/vendor/orders/{$foreignOrder->id}")->assertNotFound();
    $this->patchJson("/api/vendor/orders/{$foreignOrder->id}/status", ['status' => Order::STATUS_CONFIRMED])->assertForbidden();
});

it('lets finance staff view the ledger but not manage the product catalog', function () {
    $context = vsrVendor();
    $result = vsrAddStaff($context['vendor'], $context['owner'], Role::KEY_FINANCE);

    Sanctum::actingAs($result['staff']);

    $this->getJson('/api/vendor/ledger/summary')->assertOk();
    $this->postJson('/api/vendor/products/store-basic', ['name' => 'x'])->assertForbidden();
});
