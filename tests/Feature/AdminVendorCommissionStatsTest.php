<?php

use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Laravel\Sanctum\Sanctum;

/**
 * Mirrors CommissionStatsTest's coverage for the admin-side
 * Admin\VendorCommissionController, which had the same status-bucket gap as
 * the vendor-facing one (preparing/shipped/out_for_delivery orders fell
 * through pending/completed/cancelled entirely).
 */
it('accounts for every order status in the admin vendor commission status breakdown', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $vendor = Vendor::factory()->create();

    Order::factory()->for($vendor)->create(['status' => Order::STATUS_PENDING]);
    Order::factory()->for($vendor)->create(['status' => Order::STATUS_CONFIRMED]);
    Order::factory()->for($vendor)->create(['status' => Order::STATUS_PREPARING]);
    Order::factory()->for($vendor)->create(['status' => Order::STATUS_SHIPPED]);
    Order::factory()->for($vendor)->create(['status' => Order::STATUS_OUT_FOR_DELIVERY]);
    Order::factory()->for($vendor)->create(['status' => Order::STATUS_COMPLETED]);
    Order::factory()->for($vendor)->create(['status' => Order::STATUS_CANCELLED]);

    $response = $this->getJson("/api/admin/vendors/{$vendor->id}/commission-stats")->assertOk();

    expect($response->json('data.orders.total'))->toBe(7)
        ->and($response->json('data.orders.status_counts.pending'))->toBe(1)
        ->and($response->json('data.orders.status_counts.completed'))->toBe(5)
        ->and($response->json('data.orders.status_counts.cancelled'))->toBe(1);
});
