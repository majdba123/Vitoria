<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Laravel\Sanctum\Sanctum;

/**
 * Final acceptance audit (post stakeholder-review fix pass): negative/adversarial
 * security tests for the new RBAC + authorization surfaces. Each test proves an
 * attempted privilege escalation or cross-tenant access is a hard failure
 * (403/422), never a silent no-op or a 200 that happens to change nothing.
 */
function employeeWithRole(string $roleKey): User
{
    $employee = User::factory()->employee()->create();
    $roleId = Role::query()->where('key', $roleKey)->value('id');
    $employee->employeeRoles()->attach($roleId);

    return $employee;
}

function vendorOwner(): array
{
    $owner = User::factory()->create(['type' => User::TYPE_VENDOR]);
    $vendor = Vendor::factory()->for($owner)->create();

    return [$owner, $vendor];
}

test('an employee without products.moderate gets a hard 403 attempting to approve a product', function () {
    $employee = User::factory()->employee()->create();
    Sanctum::actingAs($employee);
    $product = Product::factory()->create(['status' => Product::STATUS_PENDING]);

    $this->putJson("/api/employee/products/{$product->id}", ['status' => 'approved'])
        ->assertForbidden();

    expect($product->refresh()->status)->toBe(Product::STATUS_PENDING);
});

test('a vendor gets a hard 403 attempting to approve their own product', function () {
    [$owner, $vendor] = vendorOwner();
    Sanctum::actingAs($owner);
    $product = Product::factory()->for($vendor)->create(['status' => Product::STATUS_PENDING]);

    $this->putJson("/api/vendor/products/{$product->id}", ['status' => Product::STATUS_APPROVED])
        ->assertForbidden();

    expect($product->refresh()->status)->toBe(Product::STATUS_PENDING);
});

test('vendor A gets a hard 403 attempting to edit vendor B\'s product', function () {
    [$ownerA] = vendorOwner();
    [, $vendorB] = vendorOwner();
    Sanctum::actingAs($ownerA);

    $product = Product::factory()->for($vendorB)->create(['name_en' => 'Original Name', 'name_ar' => 'الاسم الأصلي']);

    $this->putJson("/api/vendor/products/{$product->id}", [
        'name_en' => 'Hijacked Name',
        'name_ar' => 'اسم مخترق',
    ])->assertForbidden();

    expect($product->refresh()->name_en)->toBe('Original Name');
});

test('vendor A gets a hard 403 attempting to delete vendor B\'s product', function () {
    [$ownerA] = vendorOwner();
    [, $vendorB] = vendorOwner();
    Sanctum::actingAs($ownerA);

    $product = Product::factory()->for($vendorB)->create();

    $this->deleteJson("/api/vendor/products/{$product->id}")->assertForbidden();

    expect(Product::query()->whereKey($product->id)->exists())->toBeTrue();
});

test('an employee without orders.view gets a hard 403 viewing a specific order', function () {
    $employee = employeeWithRole(Role::KEY_CATALOG_MODERATOR);
    Sanctum::actingAs($employee);

    $vendor = Vendor::factory()->create();
    $order = Order::factory()->for($vendor)->create();

    $this->getJson("/api/employee/orders/{$order->id}")->assertForbidden();
});

test('an employee with orders.view can see a specific order, one without it cannot', function () {
    $vendor = Vendor::factory()->create();
    $order = Order::factory()->for($vendor)->create();

    $withPermission = employeeWithRole(Role::KEY_ORDER_REVIEWER);
    Sanctum::actingAs($withPermission);
    $this->getJson("/api/employee/orders/{$order->id}")->assertOk();

    $withoutPermission = User::factory()->employee()->create();
    Sanctum::actingAs($withoutPermission);
    $this->getJson("/api/employee/orders/{$order->id}")->assertForbidden();
});

test('an admin cannot assign a vendor-scoped role to an employee (no privilege escalation via role assignment)', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $employee = User::factory()->employee()->create();

    $this->patchJson("/api/admin/users/{$employee->id}/employee-roles", [
        'role_keys' => [Role::KEY_OWNER],
    ])->assertUnprocessable();

    $this->patchJson("/api/admin/users/{$employee->id}/employee-roles", [
        'role_keys' => [Role::KEY_MANAGER],
    ])->assertUnprocessable();

    expect($employee->employeeRoles()->count())->toBe(0);
});

test('roles can only be assigned to employee accounts, not admin/vendor/customer accounts', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $vendorUser = User::factory()->create(['type' => User::TYPE_VENDOR]);

    $this->patchJson("/api/admin/users/{$vendorUser->id}/employee-roles", [
        'role_keys' => [Role::KEY_CATALOG_MODERATOR],
    ])->assertUnprocessable();

    expect($vendorUser->employeeRoles()->count())->toBe(0);
});

test('a non-admin cannot assign employee roles at all', function () {
    $employee = User::factory()->employee()->create();
    $target = User::factory()->employee()->create();
    Sanctum::actingAs($employee);

    $this->patchJson("/api/admin/users/{$target->id}/employee-roles", [
        'role_keys' => [Role::KEY_CATALOG_MODERATOR],
    ])->assertForbidden();

    $vendorOwnerUser = User::factory()->create(['type' => User::TYPE_VENDOR]);
    Sanctum::actingAs($vendorOwnerUser);

    $this->patchJson("/api/admin/users/{$target->id}/employee-roles", [
        'role_keys' => [Role::KEY_CATALOG_MODERATOR],
    ])->assertForbidden();
});

test('an employee cannot self-assign a role by hitting the admin endpoint', function () {
    $employee = User::factory()->employee()->create();
    Sanctum::actingAs($employee);

    $this->patchJson("/api/admin/users/{$employee->id}/employee-roles", [
        'role_keys' => [Role::KEY_CATALOG_MODERATOR],
    ])->assertForbidden();

    expect($employee->employeeRoles()->count())->toBe(0);
});

test('an unauthenticated request cannot reach any admin, vendor, or employee product/order endpoint', function () {
    $vendor = Vendor::factory()->create();
    $product = Product::factory()->for($vendor)->create();
    $order = Order::factory()->for($vendor)->create();

    $this->putJson("/api/vendor/products/{$product->id}", ['name_en' => 'x'])->assertUnauthorized();
    $this->putJson("/api/employee/products/{$product->id}", ['status' => 'approved'])->assertUnauthorized();
    $this->getJson('/api/employee/orders')->assertUnauthorized();
    $this->patchJson("/api/admin/products/{$product->id}/status", ['status' => 'approved'])->assertUnauthorized();
    $this->patchJson('/api/admin/users/1/employee-roles', ['role_keys' => []])->assertUnauthorized();

    expect($order->fresh())->not->toBeNull();
});
