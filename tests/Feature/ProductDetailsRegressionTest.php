<?php

use App\Models\AgriculturalProductDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\SharedProductDetail;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('admin can store agriculture product detail arrays without array to string conversion', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $category = Category::query()->create([
        'name' => 'Crop Nutrition',
        'type' => Category::TYPE_AGRICULTURE,
    ]);

    $subcategory = Subcategory::query()->create([
        'category_id' => $category->id,
        'name_ar' => 'أسمدة ورقية',
        'name_en' => 'Foliar Fertilizers',
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$category->id]);

    $response = $this->postJson('/api/admin/products', [
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'subcategory_id' => $subcategory->id,
        'name_ar' => 'سماد متوازن',
        'name_en' => 'Balanced Fertilizer',
        'price' => 100,
        'quantity' => 15,
        'shared_detail' => [
            'commercial_name' => 'Balance Pro',
            'barcodes' => ['BAL-100', 'BAL-101'],
        ],
        'agricultural_detail' => [
            'agricultural_product_type' => 'fertilizer',
            'approved_uses' => [['target' => 'leaf feeding']],
            'application_methods' => ['spray', 'drip'],
            'application_rates' => [['value' => '2', 'unit' => 'L/ha']],
            'storage_conditions' => ['temperature' => 'cool', 'humidity' => 'low'],
            'warnings' => ['Keep away from children'],
            'growth_stages' => [['code' => 'GS10', 'name_en' => 'Early growth']],
            'seed_treatment' => [['name_en' => 'Protective coat']],
            'disease_resistance' => [['disease_name_en' => 'Rust', 'resistance_level' => 'medium']],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.subcategory_id', $subcategory->id)
        ->assertJsonPath('data.product_type', 'fertilizer')
        ->assertJsonPath('data.shared_detail.barcodes.0', 'BAL-100')
        ->assertJsonPath('data.agricultural_detail.storage_conditions.temperature', 'cool');

    $product = Product::query()->firstOrFail();
    $sharedDetail = SharedProductDetail::query()->where('product_id', $product->id)->firstOrFail();
    $detail = AgriculturalProductDetail::query()->where('shared_product_detail_id', $sharedDetail->id)->firstOrFail();

    expect($detail->storage_conditions)->toBe(['temperature' => 'cool', 'humidity' => 'low'])
        ->and($detail->seed_treatment)->toBe([['name_en' => 'Protective coat']])
        ->and($detail->disease_resistance)->toBe([['disease_name_en' => 'Rust', 'resistance_level' => 'medium']]);
});

test('store product validates mismatched subcategory for selected category', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $agricultureCategory = Category::query()->create([
        'name' => 'Seeds',
        'type' => Category::TYPE_AGRICULTURE,
    ]);
    $veterinaryCategory = Category::query()->create([
        'name' => 'Medicines',
        'type' => Category::TYPE_VETERINARY,
    ]);

    $foreignSubcategory = Subcategory::query()->create([
        'category_id' => $veterinaryCategory->id,
        'name_ar' => 'أدوية سائلة',
        'name_en' => 'Liquid Medicine',
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$agricultureCategory->id]);

    $this->postJson('/api/admin/products', [
        'vendor_id' => $vendor->id,
        'category_id' => $agricultureCategory->id,
        'subcategory_id' => $foreignSubcategory->id,
        'name_ar' => 'بذور ذرة',
        'name_en' => 'Corn Seeds',
        'price' => 50,
        'quantity' => 10,
    ])->assertUnprocessable()->assertJsonValidationErrors(['subcategory_id']);
});

test('seed product stores crop names instead of crop id', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $category = Category::query()->create([
        'name' => 'Seed Catalog',
        'type' => Category::TYPE_AGRICULTURE,
    ]);

    $subcategory = Subcategory::query()->create([
        'category_id' => $category->id,
        'name_ar' => 'بذور قمح',
        'name_en' => 'Wheat Seeds',
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$category->id]);

    $response = $this->postJson('/api/admin/products', [
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'subcategory_id' => $subcategory->id,
        'name_ar' => 'بذور شام',
        'name_en' => 'Sham Seeds',
        'price' => 70,
        'quantity' => 12,
        'agricultural_detail' => [
            'agricultural_product_type' => 'seed',
            'crop_name_ar' => 'قمح',
            'crop_name_en' => 'Wheat',
            'variety_name' => 'شام 5',
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.agricultural_detail.crop_name_ar', 'قمح')
        ->assertJsonPath('data.agricultural_detail.crop_name_en', 'Wheat');

    $detail = AgriculturalProductDetail::query()->firstOrFail();

    expect($detail->crop_name_ar)->toBe('قمح')
        ->and($detail->crop_name_en)->toBe('Wheat');
});

test('public product details exclude specialized detail payloads', function () {
    $category = Category::query()->create([
        'name' => 'Crop Protection',
        'type' => Category::TYPE_AGRICULTURE,
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
        'is_active' => true,
    ]);
    $vendor->categories()->sync([$category->id]);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'name' => 'Public Pesticide',
        'name_ar' => 'مبيد عام',
        'name_en' => 'Public Pesticide',
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 12,
    ]);

    $sharedDetail = SharedProductDetail::query()->create([
        'product_id' => $product->id,
        'commercial_name' => 'Protect Plus',
        'barcodes' => ['PUB-500'],
    ]);

    AgriculturalProductDetail::query()->create([
        'shared_product_detail_id' => $sharedDetail->id,
        'agricultural_product_type' => 'pesticide',
        'application_methods' => ['spray'],
    ]);

    $this->getJson('/api/products/'.$product->id.'?type='.Category::TYPE_AGRICULTURE)
        ->assertOk()
        ->assertJsonPath('data.shared_detail.commercial_name', 'Protect Plus')
        ->assertJsonMissingPath('data.agricultural_detail')
        ->assertJsonMissingPath('data.veterinary_detail');
});

test('product listing filters by subcategory product type and search', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $category = Category::query()->create([
        'name' => 'Farm Inputs',
        'type' => Category::TYPE_AGRICULTURE,
    ]);

    $fertilizerSubcategory = Subcategory::query()->create([
        'category_id' => $category->id,
        'name_ar' => 'أسمدة حبيبية',
        'name_en' => 'Granular Fertilizers',
    ]);

    $seedSubcategory = Subcategory::query()->create([
        'category_id' => $category->id,
        'name_ar' => 'بذور خضار',
        'name_en' => 'Vegetable Seeds',
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$category->id]);

    $fertilizer = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'subcategory_id' => $fertilizerSubcategory->id,
        'name' => 'Nitro Max',
        'name_ar' => 'نيترو ماكس',
        'name_en' => 'Nitro Max',
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 6,
    ]);

    $fertilizerShared = SharedProductDetail::query()->create([
        'product_id' => $fertilizer->id,
        'commercial_name' => 'Nitro Max Pro',
        'barcodes' => ['NIT-1'],
    ]);

    AgriculturalProductDetail::query()->create([
        'shared_product_detail_id' => $fertilizerShared->id,
        'agricultural_product_type' => 'fertilizer',
    ]);

    $seed = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'subcategory_id' => $seedSubcategory->id,
        'name' => 'Green Seeds',
        'name_ar' => 'بذور خضراء',
        'name_en' => 'Green Seeds',
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 9,
    ]);

    $seedShared = SharedProductDetail::query()->create([
        'product_id' => $seed->id,
        'commercial_name' => 'Green Seeds Pack',
        'barcodes' => ['SED-1'],
    ]);

    AgriculturalProductDetail::query()->create([
        'shared_product_detail_id' => $seedShared->id,
        'agricultural_product_type' => 'seed',
    ]);

    $this->getJson('/api/admin/products?subcategory_id='.$fertilizerSubcategory->id.'&product_type=fertilizer&search=Nitro')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $fertilizer->id);
});
