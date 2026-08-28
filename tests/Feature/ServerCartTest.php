<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Commerce\CartService;
use Laravel\Sanctum\Sanctum;

/**
 * Server-side cart (spec §5).
 *
 * The property under test throughout: the backend is authoritative. The client
 * may state intent; it may never state price, discount or availability.
 */
function cartProduct(int $quantity = 10, float $price = 100): Product
{
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);

    return Product::factory()->for($vendor)->create([
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => $quantity,
        'price' => $price,
    ]);
}

it('creates a guest cart tied to the session and returns server-computed totals', function () {
    $product = cartProduct(quantity: 5, price: 250);

    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])
        ->assertOk()
        ->assertJsonPath('data.items_count', 2)
        ->assertJsonPath('data.subtotal', 500)
        ->assertJsonPath('data.items.0.unit_price', 250);

    expect(Cart::query()->whereNotNull('session_token')->count())->toBe(1);
});

it('ignores any price the client tries to send', function () {
    $product = cartProduct(quantity: 5, price: 250);

    // A malicious client attempts to pin its own price and line total.
    $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 1,
        'line_total' => 1,
        'subtotal' => 1,
    ])->assertOk()
        ->assertJsonPath('data.subtotal', 250)
        ->assertJsonPath('data.items.0.unit_price', 250);

    // And nothing resembling a price was persisted on the line.
    expect(CartItem::query()->first()->getAttributes())
        ->not->toHaveKey('unit_price');
});

it('refuses to add more than the product has in stock', function () {
    $product = cartProduct(quantity: 3);

    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 4])
        ->assertStatus(422);

    expect(CartItem::query()->count())->toBe(0);
});

it('sums repeat additions and still enforces stock against the total', function () {
    $product = cartProduct(quantity: 3);

    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();

    // 2 already in the cart + 2 more exceeds the 3 available.
    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])
        ->assertStatus(422);

    expect(CartItem::query()->first()->quantity)->toBe(2);
});

it('will not add a product that is unapproved, inactive, or from an inactive vendor', function () {
    $unapproved = cartProduct();
    $unapproved->update(['status' => Product::STATUS_PENDING]);

    $inactive = cartProduct();
    $inactive->update(['is_active' => false]);

    $suspendedVendor = cartProduct();
    $suspendedVendor->vendor->update(['is_active' => false]);

    foreach ([$unapproved, $inactive, $suspendedVendor] as $product) {
        $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])
            ->assertStatus(422);
    }

    expect(CartItem::query()->count())->toBe(0);
});

it('rejects an add-to-cart request below the product\'s minimum order quantity', function () {
    $product = cartProduct(quantity: 50);
    $product->update(['minimum_order_quantity' => 5]);

    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])
        ->assertStatus(422);

    expect(CartItem::query()->count())->toBe(0);

    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 5])
        ->assertOk()
        ->assertJsonPath('data.items.0.quantity', 5);
});

it('removes a line during reconcile once it falls below a raised minimum order quantity', function () {
    $product = cartProduct(quantity: 50);

    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();

    // Vendor raises the minimum after the line was already in the cart.
    $product->update(['minimum_order_quantity' => 10]);

    $this->getJson('/api/cart')
        ->assertOk()
        ->assertJsonPath('data.items_count', 0);

    expect(CartItem::query()->count())->toBe(0);
});

it('removes a line when its quantity is set to zero', function () {
    $product = cartProduct();
    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();

    $this->patchJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 0])
        ->assertOk()
        ->assertJsonPath('data.items_count', 0);

    expect(CartItem::query()->count())->toBe(0);
});

it('drops items that became unavailable and clamps lines that exceed stock', function () {
    $stillFine = cartProduct(quantity: 10);
    $willBeWithdrawn = cartProduct(quantity: 10);
    $willShrink = cartProduct(quantity: 10);

    foreach ([$stillFine, $willBeWithdrawn, $willShrink] as $product) {
        $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 5])->assertOk();
    }

    $willBeWithdrawn->update(['is_active' => false]);
    $willShrink->update(['quantity' => 2]);

    $response = $this->getJson('/api/cart')->assertOk();

    $lines = collect($response->json('data.items'))->keyBy('product_id');

    expect($lines)->toHaveCount(2)
        ->and($lines->has($willBeWithdrawn->id))->toBeFalse()
        ->and($lines[$willShrink->id]['quantity'])->toBe(2)
        ->and($lines[$stillFine->id]['quantity'])->toBe(5)
        ->and($response->json('notices'))->not->toBeEmpty();
});

it('validates coupons server-side and computes the discount itself', function () {
    $product = cartProduct(quantity: 10, price: 1000);
    Coupon::factory()->create([
        'code' => 'SAVE10',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'is_active' => true,
        'status' => Coupon::STATUS_ACTIVE,
    ]);

    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    // Client claims a 990 discount; server applies the real 10%.
    $this->postJson('/api/cart/coupon', ['coupon_code' => 'SAVE10', 'discount' => 990])
        ->assertOk()
        ->assertJsonPath('data.discount', 100)
        ->assertJsonPath('data.total', 900);
});

it('respects a coupon maximum discount cap', function () {
    $product = cartProduct(quantity: 10, price: 1000);
    Coupon::factory()->create([
        'code' => 'CAPPED',
        'discount_type' => 'percentage',
        'discount_value' => 50,
        'max_discount_amount' => 120,
        'is_active' => true,
        'status' => Coupon::STATUS_ACTIVE,
    ]);

    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    $this->postJson('/api/cart/coupon', ['coupon_code' => 'CAPPED'])
        ->assertOk()
        ->assertJsonPath('data.discount', 120);
});

it('rejects a coupon below its minimum order subtotal', function () {
    $product = cartProduct(quantity: 10, price: 50);
    Coupon::factory()->create([
        'code' => 'BIGSPEND',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'min_order_subtotal' => 500,
        'is_active' => true,
        'status' => Coupon::STATUS_ACTIVE,
    ]);

    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    $this->postJson('/api/cart/coupon', ['coupon_code' => 'BIGSPEND'])->assertStatus(422);
});

it('merges a guest cart into the account cart at login, clamped to stock', function () {
    $user = User::factory()->create(['phone_number' => '0999111222', 'password' => bcrypt('secret123')]);

    $shared = cartProduct(quantity: 4);
    $guestOnly = cartProduct(quantity: 10);

    // The user already has 3 of $shared saved to their account.
    $userCart = Cart::factory()->create(['user_id' => $user->id]);
    CartItem::factory()->create(['cart_id' => $userCart->id, 'product_id' => $shared->id, 'quantity' => 3]);

    // As a guest, they add 3 more of $shared plus something new.
    $this->postJson('/api/cart/items', ['product_id' => $shared->id, 'quantity' => 3])->assertOk();
    $this->postJson('/api/cart/items', ['product_id' => $guestOnly->id, 'quantity' => 2])->assertOk();

    $this->postJson('/api/auth/login', [
        'phone_number' => '0999111222',
        'password' => 'secret123',
    ])->assertOk();

    $merged = CartItem::query()->where('cart_id', $userCart->id)->pluck('quantity', 'product_id');

    // 3 + 3 = 6 would exceed the 4 in stock, so the merge clamps to 4.
    expect($merged[$shared->id])->toBe(4)
        ->and($merged[$guestOnly->id])->toBe(2)
        ->and(Cart::query()->whereNotNull('session_token')->count())->toBe(0);
});

it('preserves a guest cart when the shopper creates an account during checkout', function () {
    $product = cartProduct(quantity: 10);

    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();

    $this->postJson('/api/auth/register', [
        'account_type' => 'user',
        'name' => 'Checkout Registration',
        'phone_number' => '0999222333',
        'national_id' => '9990000233',
        'age' => 30,
        'membership_number' => 'MEM-CHECKOUT-233',
        'city_id' => \App\Models\City::query()->create(['name' => 'Checkout City'])->id,
        'email' => 'checkout-registration@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated();

    $user = User::query()->where('phone_number', '0999222333')->firstOrFail();
    $userCart = Cart::query()->where('user_id', $user->id)->firstOrFail();

    expect($userCart->items()->where('product_id', $product->id)->value('quantity'))->toBe(2)
        ->and(Cart::query()->whereNotNull('session_token')->count())->toBe(0);
});

it('keeps one cart per user and one per guest session', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $product = cartProduct();

    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
    $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

    expect(Cart::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(CartItem::query()->count())->toBe(1);
});

it('caps a single line at the configured maximum', function () {
    $product = cartProduct(quantity: 99999);

    $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'quantity' => CartService::MAX_LINE_QUANTITY + 1,
    ])->assertStatus(422);
});
