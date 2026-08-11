<?php

use App\Console\Commands\ExpireScheduledDiscountsAndCoupons;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('expire command keeps a discount active through the entire end day', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-11 23:30:00'));

    $vendor = Vendor::factory()->create();

    $product = Product::factory()->for($vendor)->create([
        'discount_is_active' => true,
        'discount_percentage' => 20,
        'discount_status' => Product::DISCOUNT_STATUS_ACTIVE,
        'discount_starts_at' => Carbon::parse('2026-08-01 00:00:00'),
        'discount_ends_at' => Carbon::parse('2026-08-11 00:00:00'),
    ]);

    $this->artisan(ExpireScheduledDiscountsAndCoupons::class)->assertSuccessful();

    $product->refresh();

    expect($product->discount_status)->toBe(Product::DISCOUNT_STATUS_ACTIVE);

    Carbon::setTestNow();
});

test('expire command expires a discount the day after its end date', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-12 00:05:00'));

    $vendor = Vendor::factory()->create();

    $product = Product::factory()->for($vendor)->create([
        'discount_is_active' => true,
        'discount_percentage' => 20,
        'discount_status' => Product::DISCOUNT_STATUS_ACTIVE,
        'discount_starts_at' => Carbon::parse('2026-08-01 00:00:00'),
        'discount_ends_at' => Carbon::parse('2026-08-11 00:00:00'),
    ]);

    $this->artisan(ExpireScheduledDiscountsAndCoupons::class)->assertSuccessful();

    $product->refresh();

    expect($product->discount_status)->toBe(Product::DISCOUNT_STATUS_EXPIRED);

    Carbon::setTestNow();
});
