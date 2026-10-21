<?php

use App\Console\Commands\ActivateScheduledDiscountsAndCoupons;
use App\Console\Commands\ExpireScheduledDiscountsAndCoupons;
use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\AuditLogService;
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
    $coupon = Coupon::factory()->create([
        'is_active' => true,
        'status' => Coupon::STATUS_ACTIVE,
        'ends_at' => Carbon::parse('2026-08-11 23:00:00'),
    ]);

    $this->artisan(ExpireScheduledDiscountsAndCoupons::class)->assertSuccessful();

    $product->refresh();

    expect($product->discount_status)->toBe(Product::DISCOUNT_STATUS_EXPIRED);
    expect($coupon->refresh()->status)->toBe(Coupon::STATUS_EXPIRED);

    $productAudit = AuditLog::query()->where('action', 'product.discount.expired_by_schedule')->sole();
    $couponAudit = AuditLog::query()->where('action', 'coupon.expired_by_schedule')->sole();

    expect($productAudit->actor_type)->toBe('system')
        ->and($productAudit->actor_user_id)->toBeNull()
        ->and($productAudit->old_values)->toBe(['discount_status' => Product::DISCOUNT_STATUS_ACTIVE])
        ->and($productAudit->new_values)->toBe(['discount_status' => Product::DISCOUNT_STATUS_EXPIRED])
        ->and($couponAudit->actor_type)->toBe('system')
        ->and($couponAudit->old_values)->toBe(['status' => Coupon::STATUS_ACTIVE])
        ->and($couponAudit->new_values)->toBe(['status' => Coupon::STATUS_EXPIRED]);

    Carbon::setTestNow();
});

test('activate command records system audit entries with each scheduled status change', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-12 00:05:00'));

    $vendor = Vendor::factory()->create();
    $product = Product::factory()->for($vendor)->create([
        'discount_is_active' => true,
        'discount_percentage' => 20,
        'discount_status' => Product::DISCOUNT_STATUS_PENDING,
        'discount_starts_at' => Carbon::parse('2026-08-11 00:00:00'),
        'discount_ends_at' => Carbon::parse('2026-08-20 00:00:00'),
    ]);
    $coupon = Coupon::factory()->create([
        'is_active' => true,
        'status' => Coupon::STATUS_PENDING,
        'starts_at' => Carbon::parse('2026-08-11 00:00:00'),
        'ends_at' => Carbon::parse('2026-08-20 00:00:00'),
    ]);

    $this->artisan(ActivateScheduledDiscountsAndCoupons::class)->assertSuccessful();

    expect($product->refresh()->discount_status)->toBe(Product::DISCOUNT_STATUS_ACTIVE)
        ->and($coupon->refresh()->status)->toBe(Coupon::STATUS_ACTIVE);

    $productAudit = AuditLog::query()->where('action', 'product.discount.activated_by_schedule')->sole();
    $couponAudit = AuditLog::query()->where('action', 'coupon.activated_by_schedule')->sole();

    expect($productAudit->actor_type)->toBe('system')
        ->and($productAudit->actor_user_id)->toBeNull()
        ->and($productAudit->old_values)->toBe(['discount_status' => Product::DISCOUNT_STATUS_PENDING])
        ->and($productAudit->new_values)->toBe(['discount_status' => Product::DISCOUNT_STATUS_ACTIVE])
        ->and($couponAudit->actor_type)->toBe('system')
        ->and($couponAudit->old_values)->toBe(['status' => Coupon::STATUS_PENDING])
        ->and($couponAudit->new_values)->toBe(['status' => Coupon::STATUS_ACTIVE]);

    $this->artisan(ActivateScheduledDiscountsAndCoupons::class)->assertSuccessful();

    expect(AuditLog::query()->where('entity_id', $product->id)->where('entity_type', 'Product')->count())->toBe(1)
        ->and(AuditLog::query()->where('entity_id', $coupon->id)->where('entity_type', 'Coupon')->count())->toBe(1);

    Carbon::setTestNow();
});

test('activate command rolls status changes back when system audit recording fails', function () {
    $vendor = Vendor::factory()->create();
    $product = Product::factory()->for($vendor)->create([
        'discount_is_active' => true,
        'discount_percentage' => 20,
        'discount_status' => Product::DISCOUNT_STATUS_PENDING,
    ]);

    $this->mock(AuditLogService::class)
        ->shouldReceive('record')
        ->once()
        ->andThrow(new RuntimeException('simulated audit failure'));

    expect(fn () => $this->artisan(ActivateScheduledDiscountsAndCoupons::class))
        ->toThrow(RuntimeException::class, 'simulated audit failure');

    expect($product->refresh()->discount_status)->toBe(Product::DISCOUNT_STATUS_PENDING)
        ->and(AuditLog::query()->count())->toBe(0);
});
