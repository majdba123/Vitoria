<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

test('admin product creation stores product image and icon uploads', function () {
    Storage::fake('public');
    Sanctum::actingAs(User::factory()->admin()->create());

    $category = Category::query()->create([
        'name' => 'Upload Agriculture',
        'type' => Category::TYPE_AGRICULTURE,
    ]);
    $subcategory = Subcategory::query()->create([
        'category_id' => $category->id,
        'name' => 'Upload Seeds',
    ]);
    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$category->id]);

    $response = $this->post('/api/admin/products', [
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'subcategory_id' => $subcategory->id,
        'name' => 'Asset Enabled Product',
        'description' => 'A product with a dedicated image and icon.',
        'price' => 125.50,
        'quantity' => 25,
        'is_active' => true,
        'image' => UploadedFile::fake()->image('product.jpg', 900, 700),
        'icon' => UploadedFile::fake()->image('product-icon.png', 96, 96),
    ], ['Accept' => 'application/json']);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Asset Enabled Product')
        ->assertJsonPath('data.category.type', Category::TYPE_AGRICULTURE)
        ->assertJsonStructure([
            'data' => ['image_url', 'icon_url'],
        ]);

    $product = Product::query()->where('name', 'Asset Enabled Product')->firstOrFail();

    expect($product->image)->not->toBeNull()
        ->and($product->icon)->not->toBeNull();

    Storage::disk('public')->assertExists($product->image);
    Storage::disk('public')->assertExists($product->icon);
});

test('product asset uploads reject non image files', function () {
    Storage::fake('public');
    Sanctum::actingAs(User::factory()->admin()->create());

    $category = Category::query()->create([
        'name' => 'Reject Upload Agriculture',
        'type' => Category::TYPE_AGRICULTURE,
    ]);
    $subcategory = Subcategory::query()->create([
        'category_id' => $category->id,
        'name' => 'Reject Upload Seeds',
    ]);
    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$category->id]);

    $this->post('/api/admin/products', [
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'subcategory_id' => $subcategory->id,
        'name' => 'Invalid Asset Product',
        'price' => 45,
        'quantity' => 8,
        'image' => UploadedFile::fake()->create('not-image.pdf', 32, 'application/pdf'),
        'icon' => UploadedFile::fake()->create('not-icon.txt', 8, 'text/plain'),
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image', 'icon']);
});
