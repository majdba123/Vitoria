<?php

use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

/**
 * The viewer's timezone is a display preference only. Timestamps must be
 * stored in UTC no matter who made the request, otherwise ordering and
 * date filters (orders, reports, audit trail) drift by the user's offset.
 */
beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-10-21 21:12:00', 'UTC'));

    Route::middleware(['web', 'auth'])->post('/_test/timezone-touch', function () {
        request()->user()->touch();

        return response()->json([
            'app_timezone' => config('app.timezone'),
            'php_timezone' => date_default_timezone_get(),
            'display_timezone' => config('app.display_timezone'),
            'request_timezone' => request()->attributes->get('timezone'),
        ]);
    });

    Route::middleware('web')->get('/_test/timezone-display', fn () => response()->json([
        'php_timezone' => date_default_timezone_get(),
        'display_timezone' => config('app.display_timezone'),
    ]));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('stores UTC timestamps for a request made by a user in a non-UTC timezone', function () {
    $user = User::factory()->create(['timezone' => 'Asia/Damascus']);

    $this->actingAs($user)
        ->postJson('/_test/timezone-touch')
        ->assertOk()
        ->assertJson([
            'app_timezone' => 'UTC',
            'php_timezone' => 'UTC',
            'display_timezone' => 'Asia/Damascus',
            'request_timezone' => 'Asia/Damascus',
        ]);

    $storedUpdatedAt = User::query()->toBase()->where('id', $user->id)->value('updated_at');

    expect($storedUpdatedAt)->toBe('2026-10-21 21:12:00');
});

it('uses the timezone cookie for display without changing storage for guests', function () {
    $this->withCookie('sz_timezone', 'America/New_York')
        ->get('/_test/timezone-display')
        ->assertOk()
        ->assertJson([
            'php_timezone' => 'UTC',
            'display_timezone' => 'America/New_York',
        ]);

    expect(config('app.timezone'))->toBe('UTC');
});

it('ignores an unknown timezone and keeps the UTC display default', function () {
    $user = User::factory()->create(['timezone' => 'Not/AZone']);

    $this->actingAs($user)
        ->postJson('/_test/timezone-touch')
        ->assertOk()
        ->assertJson([
            'php_timezone' => 'UTC',
            'display_timezone' => 'UTC',
            'request_timezone' => null,
        ]);
});

it('formats the printed invoice issue time in the viewer timezone', function () {
    $vendor = Vendor::factory()->create();
    $customer = User::factory()->create(['type' => User::TYPE_USER, 'timezone' => 'Asia/Damascus']);
    $order = Order::factory()->for($vendor)->for($customer, 'user')->create(['status' => Order::STATUS_COMPLETED]);
    $invoice = Invoice::factory()->create([
        'order_id' => $order->id,
        'vendor_id' => $vendor->id,
        'user_id' => $customer->id,
        'issued_at' => Carbon::parse('2026-10-21 21:12:00', 'UTC'),
    ]);

    expect(Invoice::query()->toBase()->where('id', $invoice->id)->value('issued_at'))->toBe('2026-10-21 21:12:00');

    $this->actingAs($customer)
        ->get(route('invoices.print', $invoice))
        ->assertOk()
        ->assertSee('2026-10-22 00:12', false)
        ->assertDontSee('2026-10-21 21:12', false);
});
