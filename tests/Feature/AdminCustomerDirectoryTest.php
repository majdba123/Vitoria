<?php

use App\Models\Order;
use App\Models\Syndicate;
use App\Models\User;
use App\Models\Vendor;
use Laravel\Sanctum\Sanctum;

/**
 * Admin "Users" directory (stakeholder review: delete merchants, keep app
 * users). The page now represents application customers only — vendors,
 * admins, employees, and syndicate accounts must never appear in the
 * customer listing (type=0), which is the query shape the Admin Users page
 * itself always sends.
 */
it('lists only application customers and excludes every other account type', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    $customer = User::factory()->create(['type' => User::TYPE_USER, 'name' => 'Real Customer']);
    User::factory()->create(['type' => User::TYPE_VENDOR, 'name' => 'A Vendor Account']);
    User::factory()->create(['type' => User::TYPE_SYNDICATE, 'name' => 'A Syndicate Account']);
    User::factory()->create(['type' => User::TYPE_EMPLOYEE, 'name' => 'An Employee Account']);
    Vendor::factory()->create();
    Syndicate::factory()->agriculture()->create();

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/admin/users?type=0')->assertOk();
    $names = collect($response->json('data'))->pluck('name');

    expect($names)->toContain('Real Customer')
        ->and($names)->not->toContain('A Vendor Account')
        ->and($names)->not->toContain('A Syndicate Account')
        ->and($names)->not->toContain('An Employee Account');

    foreach ($response->json('data') as $row) {
        expect($row['type'])->toBe(User::TYPE_USER);
    }

    expect($customer->id)->not->toBeNull();
});

it('searches customers by name, email, or phone', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    User::factory()->create(['type' => User::TYPE_USER, 'name' => 'Layla Hassan', 'email' => 'layla@example.test', 'phone_number' => '0955500001']);
    User::factory()->create(['type' => User::TYPE_USER, 'name' => 'Omar Khaled', 'email' => 'omar@example.test', 'phone_number' => '0955500002']);

    Sanctum::actingAs($admin);

    $byName = $this->getJson('/api/admin/users?type=0&search=Layla')->assertOk();
    expect(collect($byName->json('data'))->pluck('name')->all())->toBe(['Layla Hassan']);

    $byPhone = $this->getJson('/api/admin/users?type=0&search=0955500002')->assertOk();
    expect(collect($byPhone->json('data'))->pluck('name')->all())->toBe(['Omar Khaled']);
});

it('filters customers by profession (preferred product type) and paginates results', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    User::factory()->count(3)->create(['type' => User::TYPE_USER, 'preferred_product_type' => 'agriculture']);
    User::factory()->count(2)->create(['type' => User::TYPE_USER, 'preferred_product_type' => 'veterinary']);

    Sanctum::actingAs($admin);

    $agriculture = $this->getJson('/api/admin/users?type=0&product_type=agriculture&per_page=50')->assertOk();
    expect(collect($agriculture->json('data')))->toHaveCount(3);

    $paged = $this->getJson('/api/admin/users?type=0&per_page=2')->assertOk();
    expect($paged->json('data'))->toHaveCount(2)
        ->and($paged->json('meta.per_page'))->toBe(2);
});

it('exposes order history aggregates for a customer but never a password or token', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    $customer = User::factory()->create(['type' => User::TYPE_USER]);
    $vendor = Vendor::factory()->create();
    Order::factory()->for($customer)->for($vendor)->create(['status' => Order::STATUS_COMPLETED, 'grand_total' => 150]);

    Sanctum::actingAs($admin);

    $response = $this->getJson("/api/admin/users/{$customer->id}")->assertOk();

    $response->assertJsonPath('data.orders_count', 1)
        ->assertJsonPath('data.total_purchases', 150);

    expect($response->json('data'))->not->toHaveKey('password')
        ->and($response->json('data'))->not->toHaveKey('remember_token');
});

it('denies the customer directory to non-admin roles', function () {
    $vendorUser = User::factory()->create(['type' => User::TYPE_VENDOR]);
    Sanctum::actingAs($vendorUser);

    $this->getJson('/api/admin/users?type=0')->assertForbidden();
});
