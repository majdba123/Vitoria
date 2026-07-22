<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

test('admin product creation stores product photos only', function () {
    Storage::fake('public');
    Sanctum::actingAs(User::factory()->admin()->create());

    $category = Category::query()->create([
        'name' => 'Upload Agriculture',
        'type' => Category::TYPE_AGRICULTURE,
    ]);
    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$category->id]);

    $response = $this->post('/api/admin/products', [
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name' => 'Asset Enabled Product',
        'description' => 'A product with dedicated photos.',
        'price' => 125.50,
        'quantity' => 25,
        'is_active' => true,
        'photos' => [
            UploadedFile::fake()->image('product-front.jpg', 900, 700),
            UploadedFile::fake()->image('product-back.jpg', 900, 700),
        ],
        'photo_types' => ['front', 'back'],
        'photo_sort_orders' => [1, 2],
    ], ['Accept' => 'application/json']);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Asset Enabled Product')
        ->assertJsonPath('data.category.type', Category::TYPE_AGRICULTURE)
        ->assertJsonStructure([
            'data' => ['photos', 'first_photo_url'],
        ]);

    $product = Product::query()->with('photos')->where('name', 'Asset Enabled Product')->firstOrFail();

    expect($product->photos)->toHaveCount(2)
        ->and($product->photos->firstWhere('is_primary', true))->not->toBeNull();

    Storage::disk('public')->assertExists($product->photos[0]->path);
    Storage::disk('public')->assertExists($product->photos[1]->path);
});

test('product photo uploads reject non image files', function () {
    Storage::fake('public');
    Sanctum::actingAs(User::factory()->admin()->create());

    $category = Category::query()->create([
        'name' => 'Reject Upload Agriculture',
        'type' => Category::TYPE_AGRICULTURE,
    ]);
    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$category->id]);

    $this->post('/api/admin/products', [
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name' => 'Invalid Asset Product',
        'price' => 45,
        'quantity' => 8,
        'photos' => [
            UploadedFile::fake()->create('not-image.pdf', 32, 'application/pdf'),
        ],
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['photos.0']);
});
