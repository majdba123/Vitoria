<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('vendor commission stats endpoint stays bounded and does not error with old order history', function () {
    $vendor = Vendor::factory()->create();
    $vendor->user->update(['type' => User::TYPE_VENDOR]);
    Sanctum::actingAs($vendor->user);

    $category = Category::query()->create([
        'name' => 'Commission Category',
        'type' => Category::TYPE_AGRICULTURE,
        'commission' => 10,
    ]);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
    ]);

    $recentOrder = Order::factory()->for($vendor)->create([
        'status' => Order::STATUS_COMPLETED,
        'total_amount' => 100,
        'created_at' => now()->subDays(5),
    ]);
    OrderItem::factory()->for($recentOrder)->for($product)->create([
        'line_total' => 100,
    ]);

    $oldOrder = Order::factory()->for($vendor)->create([
        'status' => Order::STATUS_COMPLETED,
        'total_amount' => 500,
        'created_at' => now()->subDays(400),
    ]);
    OrderItem::factory()->for($oldOrder)->for($product)->create([
        'line_total' => 500,
    ]);

    $response = $this->getJson('/api/vendor/commission-stats');

    $response->assertOk()
        ->assertJsonPath('data.financials.completed_order_total', 100)
        ->assertJsonPath('data.category_breakdown.0.sales_total', 100);
});

test('vendor commission status breakdown accounts for every order status, not just pending/completed/cancelled', function () {
    $vendor = Vendor::factory()->create();
    $vendor->user->update(['type' => User::TYPE_VENDOR]);
    Sanctum::actingAs($vendor->user);

    Order::factory()->for($vendor)->create(['status' => Order::STATUS_PENDING]);
    Order::factory()->for($vendor)->create(['status' => Order::STATUS_CONFIRMED]);
    Order::factory()->for($vendor)->create(['status' => Order::STATUS_PREPARING]);
    Order::factory()->for($vendor)->create(['status' => Order::STATUS_SHIPPED]);
    Order::factory()->for($vendor)->create(['status' => Order::STATUS_OUT_FOR_DELIVERY]);
    Order::factory()->for($vendor)->create(['status' => Order::STATUS_COMPLETED]);
    Order::factory()->for($vendor)->create(['status' => Order::STATUS_CANCELLED]);

    $response = $this->getJson('/api/vendor/commission-stats')->assertOk();

    // Every one of the 7 orders above must land in exactly one bucket —
    // preparing/shipped/out_for_delivery used to fall through all three and
    // vanish from the total instead of counting toward "completed".
    expect($response->json('data.orders.total'))->toBe(7)
        ->and($response->json('data.orders.status_counts.pending'))->toBe(1)
        ->and($response->json('data.orders.status_counts.completed'))->toBe(5)
        ->and($response->json('data.orders.status_counts.cancelled'))->toBe(1);
});
