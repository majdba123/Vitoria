<?php

use App\Models\AgriculturalProductDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\SharedProductDetail;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VeterinaryProductDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('admin can clear nullable agriculture product fields during update', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $category = Category::query()->create([
        'name' => 'Agriculture Inputs',
        'type' => Category::TYPE_AGRICULTURE,
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$category->id]);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'description' => 'Original description',
        'discount_percentage' => 15,
        'discount_is_active' => true,
        'discount_status' => Product::DISCOUNT_STATUS_ACTIVE,
    ]);

    $sharedDetail = SharedProductDetail::query()->create([
        'product_id' => $product->id,
        'commercial_name' => 'Green Force',
        'barcodes' => ['AGR-900'],
        'manufacturer_name_en' => 'Farm Labs',
    ]);

    AgriculturalProductDetail::query()->create([
        'shared_product_detail_id' => $sharedDetail->id,
        'agricultural_product_type' => 'fertilizer',
        'formulation' => 'WP',
        'application_methods' => ['spray'],
        'warnings' => ['Use gloves'],
    ]);

    $response = $this->putJson('/api/admin/products/'.$product->id, [
        'description' => null,
        'discount_percentage' => null,
        'shared_detail' => [
            'commercial_name' => null,
            'barcodes' => [],
            'manufacturer_name_en' => '',
        ],
        'agricultural_detail' => [
            'formulation' => null,
            'application_methods' => [],
            'warnings' => [],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.description', null)
        ->assertJsonPath('data.discount_percentage', null)
        ->assertJsonPath('data.shared_detail.commercial_name', null)
        ->assertJsonPath('data.shared_detail.barcodes', null)
        ->assertJsonPath('data.shared_detail.manufacturer_name_en', null)
        ->assertJsonPath('data.agricultural_detail.formulation', null)
        ->assertJsonPath('data.agricultural_detail.application_methods', null)
        ->assertJsonPath('data.agricultural_detail.warnings', null);

    $product->refresh();
    $sharedDetail->refresh();
    $agriculturalDetail = AgriculturalProductDetail::query()->where('shared_product_detail_id', $sharedDetail->id)->firstOrFail();

    expect($product->description)->toBeNull()
        ->and($product->discount_percentage)->toBeNull()
        ->and($product->discount_is_active)->toBeFalse()
        ->and($sharedDetail->commercial_name)->toBeNull()
        ->and($sharedDetail->barcodes)->toBeNull()
        ->and($sharedDetail->manufacturer_name_en)->toBeNull()
        ->and($agriculturalDetail->formulation)->toBeNull()
        ->and($agriculturalDetail->application_methods)->toBeNull()
        ->and($agriculturalDetail->warnings)->toBeNull();
});

test('vendor can clear nullable veterinary product fields during update', function () {
    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_VETERINARY,
    ]);
    $vendor->user->update(['type' => User::TYPE_VENDOR]);
    Sanctum::actingAs($vendor->user);

    $category = Category::query()->create([
        'name' => 'Veterinary Medicine',
        'type' => Category::TYPE_VETERINARY,
    ]);
    $vendor->categories()->sync([$category->id]);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
    ]);

    $sharedDetail = SharedProductDetail::query()->create([
        'product_id' => $product->id,
        'commercial_name' => 'Vet Shield',
        'barcodes' => ['VET-111'],
    ]);

    VeterinaryProductDetail::query()->create([
        'shared_product_detail_id' => $sharedDetail->id,
        'concentration' => '20%',
        'dosage_form' => 'solution',
        'routes_of_administration' => ['oral'],
        'warnings' => ['Keep sealed'],
    ]);

    $response = $this->putJson('/api/vendor/products/'.$product->id, [
        'shared_detail' => [
            'commercial_name' => null,
            'barcodes' => [],
        ],
        'veterinary_detail' => [
            'concentration' => null,
            'routes_of_administration' => [],
            'warnings' => [],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.shared_detail.commercial_name', null)
        ->assertJsonPath('data.shared_detail.barcodes', null)
        ->assertJsonPath('data.veterinary_detail.concentration', null)
        ->assertJsonPath('data.veterinary_detail.routes_of_administration', null)
        ->assertJsonPath('data.veterinary_detail.warnings', null);

    $sharedDetail->refresh();
    $veterinaryDetail = VeterinaryProductDetail::query()->where('shared_product_detail_id', $sharedDetail->id)->firstOrFail();

    expect($sharedDetail->commercial_name)->toBeNull()
        ->and($sharedDetail->barcodes)->toBeNull()
        ->and($veterinaryDetail->concentration)->toBeNull()
        ->and($veterinaryDetail->routes_of_administration)->toBeNull()
        ->and($veterinaryDetail->warnings)->toBeNull();
});

test('product update rejects photo identifiers that do not belong to the same product', function () {
    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->user->update(['type' => User::TYPE_VENDOR]);
    Sanctum::actingAs($vendor->user);

    $category = Category::query()->create([
        'name' => 'Agriculture Inputs',
        'type' => Category::TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$category->id]);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
    ]);

    $otherProduct = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
    ]);

    $foreignPhoto = ProductPhoto::query()->create([
        'product_id' => $otherProduct->id,
        'path' => 'products/'.$otherProduct->id.'/foreign.jpg',
        'image_type' => ProductPhoto::TYPE_FRONT,
        'sort_order' => 1,
        'is_primary' => true,
    ]);

    $this->putJson('/api/vendor/products/'.$product->id, [
        'primary_photo_id' => $foreignPhoto->id,
        'photo_ids' => [$foreignPhoto->id],
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'primary_photo_id',
        'photo_ids.0',
    ]);
});

test('vendor can list their own products without a server error', function () {
    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->user->update(['type' => User::TYPE_VENDOR]);
    Sanctum::actingAs($vendor->user);

    $category = Category::query()->create([
        'name' => 'Agriculture Inputs',
        'type' => Category::TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$category->id]);

    Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
    ]);

    $this->getJson('/api/vendor/products?per_page=5')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
