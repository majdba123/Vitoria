<?php

use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('employee product moderation update requires a status', function () {
    $employee = User::factory()->employee()->create();
    Sanctum::actingAs($employee);
    $product = Product::factory()->create();

    $this->putJson("/api/employee/products/{$product->id}", [
        'description' => 'Updated description without a status decision.',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

test('employee product moderation update succeeds with a status', function () {
    $employee = User::factory()->employee()->create();
    Sanctum::actingAs($employee);
    $product = Product::factory()->create(['status' => Product::STATUS_PENDING]);

    $this->putJson("/api/employee/products/{$product->id}", [
        'status' => 'approved',
    ])->assertOk();

    expect($product->refresh()->status)->toBe('approved');
});
