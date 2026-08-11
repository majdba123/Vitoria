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
