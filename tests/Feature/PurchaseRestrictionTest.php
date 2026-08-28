<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Vendor;
use Laravel\Sanctum\Sanctum;

/**
 * Only User::TYPE_USER may buy. Admin, Vendor, Syndicate and Employee are
 * authenticated for their own permissions (order management, vendor
 * dashboards, ...) but must be blocked from every buyer-side action - cart
 * mutation, coupon, checkout summary, checkout, and the deprecated
 * orders/checkout route. Guests keep the existing guest-cart behaviour.
 */
function purchaseTestProduct(int $quantity = 10, float $price = 100): Product
{
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);

    return Product::factory()->for($vendor)->create([
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => $quantity,
        'price' => $price,
    ]);
}

dataset('privileged_types', [
    'admin' => [User::TYPE_ADMIN],
    'vendor' => [User::TYPE_VENDOR],
    'syndicate' => [User::TYPE_SYNDICATE],
    'employee' => [User::TYPE_EMPLOYEE],
]);

it('lets a normal customer add to cart, update quantity, apply a coupon, and checkout', function () {
    $user = User::factory()->create(['type' => User::TYPE_USER]);
    Sanctum::actingAs($user);

    $product = purchaseTestProduct(quantity: 10, price: 1000);
    $address = UserAddress::factory()->default()->create(['user_id' => $user->id]);
    Coupon::factory()->create([
        'code' => 'CUSTOMER10',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'is_active' => true,
        'status' => Coupon::STATUS_ACTIVE,
    ]);

    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();
    $this->patchJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
    $this->postJson('/api/cart/coupon', ['coupon_code' => 'CUSTOMER10'])->assertOk();
    $this->getJson('/api/checkout/summary')->assertOk();

    $this->postJson('/api/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cash',
    ])->assertCreated();
});

it('lets a guest build a session cart without being blocked by the purchase gate', function () {
    $product = purchaseTestProduct(quantity: 10);

    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])
        ->assertOk()
        ->assertJsonPath('data.items_count', 2);
});

it('requires authentication for a guest at checkout, as today', function () {
    $product = purchaseTestProduct(quantity: 10);
    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    $this->getJson('/api/checkout/summary')->assertStatus(401);
    $this->postJson('/api/checkout', ['address_id' => 1, 'payment_method' => 'cash'])->assertStatus(401);
});

it('blocks a privileged authenticated role from every cart mutation action', function (int $type) {
    $user = User::factory()->create(['type' => $type]);
    Sanctum::actingAs($user);

    $product = purchaseTestProduct(quantity: 10);

    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])
        ->assertStatus(403)
        ->assertJsonPath('message', __('purchase.customer_only'));

    $this->patchJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])
        ->assertStatus(403);

    $this->deleteJson("/api/cart/items/{$product->id}")->assertStatus(403);
    $this->deleteJson('/api/cart')->assertStatus(403);
    $this->postJson('/api/cart/coupon', ['coupon_code' => 'ANY'])->assertStatus(403);
    $this->deleteJson('/api/cart/coupon')->assertStatus(403);

    expect(CartItem::query()->count())->toBe(0);
})->with('privileged_types');

it('blocks a privileged authenticated role from checkout summary and checkout', function (int $type) {
    $user = User::factory()->create(['type' => $type]);
    Sanctum::actingAs($user);

    $address = UserAddress::factory()->default()->create(['user_id' => $user->id]);
    $product = purchaseTestProduct(quantity: 10);

    $this->getJson('/api/checkout/summary')
        ->assertStatus(403)
        ->assertJsonPath('message', __('purchase.customer_only'));

    $this->postJson('/api/checkout', [
        'address_id' => $address->id,
        'payment_method' => 'cash',
    ])->assertStatus(403);

    expect(\App\Models\Order::query()->count())->toBe(0);
})->with('privileged_types');

it('blocks a privileged authenticated role from the deprecated orders/checkout route', function (int $type) {
    $user = User::factory()->create(['type' => $type]);
    Sanctum::actingAs($user);

    $product = purchaseTestProduct(quantity: 10);

    $this->postJson('/api/orders/checkout', [
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertStatus(403);

    expect(\App\Models\Order::query()->count())->toBe(0);
})->with('privileged_types');

it('cannot bypass the purchase gate by forging a request directly at the service boundary', function () {
    // Security regression: even if a privileged user somehow reached the
    // canonical order-creation service (e.g. a future code path that
    // forgets the middleware), the service itself refuses to place the
    // order. This proves the defence-in-depth guard in
    // CheckoutService::place, independent of the route middleware.
    $user = User::factory()->create(['type' => User::TYPE_ADMIN]);
    $address = UserAddress::factory()->default()->create(['user_id' => $user->id]);
    $product = purchaseTestProduct(quantity: 10);

    $cart = Cart::factory()->create(['user_id' => $user->id]);
    CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

    $service = app(\App\Services\Commerce\CheckoutService::class);

    expect(fn () => $service->place($cart, $user, $address, 'cash'))
        ->toThrow(\App\Services\Commerce\CartException::class, __('purchase.customer_only'));

    expect(\App\Models\Order::query()->count())->toBe(0);
});

it('automatically blocks a formerly-customer user the moment their type changes to a privileged role, without deleting their cart', function () {
    $user = User::factory()->create(['type' => User::TYPE_USER]);
    Sanctum::actingAs($user);

    $product = purchaseTestProduct(quantity: 10);

    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    $cartId = Cart::query()->where('user_id', $user->id)->value('id');
    expect(CartItem::query()->where('cart_id', $cartId)->count())->toBe(1);

    // Promoted to vendor after the cart already has items - eligibility is
    // re-checked live against the DB type on every request, so the stale
    // cart is left untouched but further purchase actions are blocked.
    $user->update(['type' => User::TYPE_VENDOR]);

    $this->patchJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])
        ->assertStatus(403);

    // The old cart line was not deleted by the type change or the rejection.
    expect(CartItem::query()->where('cart_id', $cartId)->count())->toBe(1);
});
