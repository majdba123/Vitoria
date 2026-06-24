<?php

use App\Models\Coupon;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function couponAdmin(): User
{
    $admin = User::factory()->admin()->create();

    Sanctum::actingAs($admin);

    return $admin;
}

test('admin coupon create stores date and time values from datetime local input', function () {
    couponAdmin();

    $response = $this->postJson('/api/admin/coupons', [
        'code' => 'TIME10',
        'title' => 'Timed Coupon',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'starts_at' => '2026-06-24T14:30',
        'ends_at' => '2026-06-30T18:45',
        'is_active' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.starts_at', '2026-06-24T14:30:00.000000Z')
        ->assertJsonPath('data.ends_at', '2026-06-30T18:45:00.000000Z');

    $coupon = Coupon::query()->where('code', 'TIME10')->firstOrFail();

    expect($coupon->starts_at?->format('Y-m-d H:i:s'))->toBe('2026-06-24 14:30:00')
        ->and($coupon->ends_at?->format('Y-m-d H:i:s'))->toBe('2026-06-30 18:45:00');
});

test('admin coupon create normalizes date only values to include time', function () {
    couponAdmin();

    $response = $this->postJson('/api/admin/coupons', [
        'code' => 'DATE10',
        'title' => 'Date Only Coupon',
        'discount_type' => 'fixed',
        'discount_value' => 5000,
        'starts_at' => '2026-07-01',
        'ends_at' => '2026-07-05',
        'is_active' => true,
    ]);

    $response->assertCreated();

    $coupon = Coupon::query()->where('code', 'DATE10')->firstOrFail();

    expect($coupon->starts_at?->format('Y-m-d H:i:s'))->toBe('2026-07-01 00:00:00')
        ->and($coupon->ends_at?->format('Y-m-d H:i:s'))->toBe('2026-07-05 23:59:00');
});
