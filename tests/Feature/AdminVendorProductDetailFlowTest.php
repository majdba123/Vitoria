<?php

use App\Models\AgriculturalProductDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\SharedProductDetail;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VeterinaryProductDetail;
use Laravel\Sanctum\Sanctum;

test('admin can create an agriculture product with shared and agriculture details', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $category = Category::query()->create([
        'name' => 'Agriculture Inputs',
        'type' => Category::TYPE_AGRICULTURE,
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$category->id]);

    $response = $this->postJson('/api/admin/products', [
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name_ar' => 'سماد عضوي',
        'name_en' => 'Organic Fertilizer',
        'description' => 'Agriculture detail flow',
        'price' => 99.5,
        'quantity' => 20,
        'is_active' => true,
        'shared_detail' => [
            'commercial_name' => 'Ferti Grow',
            'barcode' => 'AGR-100',
            'aliases' => ['Ferti', 'Grow'],
            'barcodes' => ['AGR-100', 'AGR-101'],
            'package_size' => 25,
            'package_unit' => 'kg',
            'keywords' => ['fertilizer', 'organic'],
        ],
        'agricultural_detail' => [
            'agricultural_product_type' => 'fertilizer',
            'fertilizer_type' => 'organic',
            'application_methods' => ['spray', 'soil'],
            'growth_stages' => [['code' => 'GS1', 'name_en' => 'Initial stage']],
            'expected_yield' => ['value' => 15, 'unit' => 'ton'],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name_ar', 'سماد عضوي')
        ->assertJsonPath('data.name_en', 'Organic Fertilizer')
        ->assertJsonPath('data.shared_detail.commercial_name', 'Ferti Grow')
        ->assertJsonPath('data.agricultural_detail.fertilizer_type', 'organic')
        ->assertJsonPath('data.product_type', Category::TYPE_AGRICULTURE);

    $product = Product::query()->firstOrFail();
    $sharedDetail = SharedProductDetail::query()->where('product_id', $product->id)->firstOrFail();
    $agriculturalDetail = AgriculturalProductDetail::query()->where('shared_product_detail_id', $sharedDetail->id)->firstOrFail();

    expect($product->getRawOriginal('name'))->toBe('سماد عضوي')
        ->and($product->getLocalizedName('ar'))->toBe('سماد عضوي')
        ->and($product->getLocalizedName('en'))->toBe('Organic Fertilizer')
        ->and($product->name_ar)->toBe('سماد عضوي')
        ->and($product->name_en)->toBe('Organic Fertilizer')
        ->and($sharedDetail->commercial_name)->toBe('Ferti Grow')
        ->and($agriculturalDetail->fertilizer_type)->toBe('organic')
        ->and($agriculturalDetail->application_methods)->toBe(['spray', 'soil']);
});

test('vendor can update a veterinary product with shared and veterinary details', function () {
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
        'name' => 'Legacy Vet Name',
        'name_ar' => 'دواء قديم',
        'name_en' => 'Legacy Vet Name',
        'status' => Product::STATUS_PENDING,
    ]);

    $response = $this->putJson('/api/vendor/products/'.$product->id, [
        'category_id' => $category->id,
        'name_ar' => 'مضاد حيوي بيطري',
        'name_en' => 'Veterinary Antibiotic',
        'price' => 150,
        'quantity' => 8,
        'shared_detail' => [
            'commercial_name' => 'Vet Cure',
            'barcode' => 'VET-500',
            'short_description' => 'Updated by vendor',
        ],
        'veterinary_detail' => [
            'concentration' => '10%',
            'dosage_form' => 'solution',
            'routes_of_administration' => ['oral'],
            'target_species' => [['name_en' => 'Cattle']],
            'withdrawal_meat_days' => 7,
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name_ar', 'مضاد حيوي بيطري')
        ->assertJsonPath('data.name_en', 'Veterinary Antibiotic')
        ->assertJsonPath('data.shared_detail.commercial_name', 'Vet Cure')
        ->assertJsonPath('data.veterinary_detail.concentration', '10%')
        ->assertJsonPath('data.product_type', Category::TYPE_VETERINARY);

    $product->refresh();
    $sharedDetail = $product->sharedDetail()->firstOrFail();
    $veterinaryDetail = VeterinaryProductDetail::query()->where('shared_product_detail_id', $sharedDetail->id)->firstOrFail();

    expect($product->getRawOriginal('name'))->toBe('مضاد حيوي بيطري')
        ->and($product->getLocalizedName('ar'))->toBe('مضاد حيوي بيطري')
        ->and($product->getLocalizedName('en'))->toBe('Veterinary Antibiotic')
        ->and($sharedDetail->barcode)->toBe('VET-500')
        ->and($veterinaryDetail->dosage_form)->toBe('solution')
        ->and($veterinaryDetail->routes_of_administration)->toBe(['oral']);
});
