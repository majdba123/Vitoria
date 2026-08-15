<?php

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Laravel\Sanctum\Sanctum;

/**
 * Grants an employee the `catalog_moderator` role (products.view + products.moderate),
 * mirroring what the RBAC migration backfills onto every employee that existed
 * before it, so tests of "an employee that can moderate" stay realistic.
 */
function grantCatalogModeratorRole(User $employee): void
{
    $roleId = Role::query()->where('key', Role::KEY_CATALOG_MODERATOR)->value('id');
    $employee->employeeRoles()->attach($roleId);
}

test('employee product moderation update requires a status', function () {
    $employee = User::factory()->employee()->create();
    grantCatalogModeratorRole($employee);
    Sanctum::actingAs($employee);
    $product = Product::factory()->create();

    $this->putJson("/api/employee/products/{$product->id}", [
        'description' => 'Updated description without a status decision.',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

test('employee product moderation update succeeds with a status when their role grants products.moderate', function () {
    $employee = User::factory()->employee()->create();
    grantCatalogModeratorRole($employee);
    Sanctum::actingAs($employee);
    $product = Product::factory()->create(['status' => Product::STATUS_PENDING]);

    $this->putJson("/api/employee/products/{$product->id}", [
        'status' => 'approved',
    ])->assertOk();

    expect($product->refresh()->status)->toBe('approved');
});

test('an employee without the products.moderate permission cannot approve a product (stakeholder review #24)', function () {
    $employee = User::factory()->employee()->create();
    // No role assigned at all — an employee freshly created after the RBAC
    // system exists starts with zero permissions, not the old blanket access.
    Sanctum::actingAs($employee);
    $product = Product::factory()->create(['status' => Product::STATUS_PENDING]);

    $this->putJson("/api/employee/products/{$product->id}", [
        'status' => 'approved',
        'description' => 'Attempted approval without the moderate permission.',
    ])->assertOk();

    // The status field is silently stripped, same as a vendor's own update —
    // only the description change goes through.
    expect($product->refresh()->status)->toBe(Product::STATUS_PENDING);
});

test('an employee with only the order_reviewer role cannot moderate products but can view orders', function () {
    $employee = User::factory()->employee()->create();
    $roleId = Role::query()->where('key', Role::KEY_ORDER_REVIEWER)->value('id');
    $employee->employeeRoles()->attach($roleId);
    Sanctum::actingAs($employee);

    $product = Product::factory()->create(['status' => Product::STATUS_PENDING]);
    $this->putJson("/api/employee/products/{$product->id}", ['status' => 'approved'])->assertOk();
    expect($product->refresh()->status)->toBe(Product::STATUS_PENDING);

    $vendor = Vendor::factory()->create(['store_name' => 'Order Reviewer Test Vendor']);
    $order = \App\Models\Order::factory()->for($vendor)->create();

    $this->getJson('/api/employee/orders')->assertOk();
    $this->getJson("/api/employee/orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('data.vendor.store_name', 'Order Reviewer Test Vendor');
});

test('an employee without the orders.view permission cannot list employee orders', function () {
    $employee = User::factory()->employee()->create();
    grantCatalogModeratorRole($employee);
    Sanctum::actingAs($employee);

    $this->getJson('/api/employee/orders')->assertForbidden();
});

test('a vendor cannot approve their own product by sending a status field', function () {
    $owner = User::factory()->create(['type' => User::TYPE_VENDOR]);
    $vendor = Vendor::factory()->for($owner)->create();
    Sanctum::actingAs($owner);

    $product = Product::factory()->for($vendor)->create(['status' => Product::STATUS_PENDING]);

    $this->putJson("/api/vendor/products/{$product->id}", [
        'status' => Product::STATUS_APPROVED,
    ])->assertOk();

    // The status field is silently stripped from a vendor's own update — a
    // vendor never qualifies for ProductPolicy::manageStatus() (product approval
    // workflow, stakeholder review), regardless of any vendor-staff permission.
    expect($product->refresh()->status)->toBe(Product::STATUS_PENDING);
});

test('a vendor cannot delete a product they do not own', function () {
    $ownerVendor = Vendor::factory()->create();
    $intruder = User::factory()->create(['type' => User::TYPE_VENDOR]);
    Vendor::factory()->for($intruder)->create();
    Sanctum::actingAs($intruder);

    $product = Product::factory()->for($ownerVendor)->create();

    $this->deleteJson("/api/vendor/products/{$product->id}")->assertForbidden();

    expect(Product::query()->whereKey($product->id)->exists())->toBeTrue();
});
