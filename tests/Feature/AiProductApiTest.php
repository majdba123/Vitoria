<?php

use App\Models\AgriculturalProductDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\SharedProductDetail;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VeterinaryProductDetail;
use Laravel\Sanctum\Sanctum;

test('ai product api requires authentication', function () {
    $this->getJson('/api/ai/products')->assertUnauthorized();
});

test('ai product api returns paginated products with external product resource structure', function () {
    Sanctum::actingAs(User::factory()->create());

    $category = Category::query()->create([
        'name' => 'AI Veterinary',
        'type' => Category::TYPE_VETERINARY,
    ]);

    $subcategory = Subcategory::query()->create([
        'category_id' => $category->id,
        'name_ar' => 'مضادات حيوية',
        'name_en' => 'Antibiotics',
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_VETERINARY,
        'store_name' => 'AI Vet Store',
    ]);
    $vendor->categories()->sync([$category->id]);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'subcategory_id' => $subcategory->id,
        'name' => 'AI Default Name',
        'name_ar' => 'منتج ذكاء',
        'name_en' => 'AI Product',
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 18,
    ]);

    $sharedDetail = SharedProductDetail::query()->create([
        'product_id' => $product->id,
        'commercial_name' => 'AI Shield',
        'barcodes' => ['AI-100', 'AI-101'],
    ]);

    VeterinaryProductDetail::query()->create([
        'shared_product_detail_id' => $sharedDetail->id,
        'concentration' => '5%',
        'dosage_form' => 'tablet',
    ]);

    $this->getJson('/api/ai/products?per_page=10&status=approved&category_type=veterinary')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $product->id)
        ->assertJsonPath('data.0.vendor.store_name', 'AI Vet Store')
        ->assertJsonPath('data.0.subcategory.id', $subcategory->id)
        ->assertJsonPath('data.0.shared.commercial_name', 'AI Shield')
        ->assertJsonPath('data.0.veterinary_detail.concentration', '5%');
});

test('ai product api supports filters for subcategory product type and search', function () {
    Sanctum::actingAs(User::factory()->create());

    $category = Category::query()->create([
        'name' => 'AI Agriculture',
        'type' => Category::TYPE_AGRICULTURE,
    ]);

    $fertilizerSubcategory = Subcategory::query()->create([
        'category_id' => $category->id,
        'name_ar' => 'أسمدة',
        'name_en' => 'Fertilizers',
    ]);

    $seedSubcategory = Subcategory::query()->create([
        'category_id' => $category->id,
        'name_ar' => 'بذور',
        'name_en' => 'Seeds',
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->categories()->sync([$category->id]);

    $fertilizer = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'subcategory_id' => $fertilizerSubcategory->id,
        'name' => 'Nitro AI',
        'name_ar' => 'نيترو',
        'name_en' => 'Nitro AI',
        'status' => Product::STATUS_PENDING,
        'is_active' => true,
        'quantity' => 5,
    ]);

    $fertilizerShared = SharedProductDetail::query()->create([
        'product_id' => $fertilizer->id,
        'commercial_name' => 'Nitro Search',
        'barcodes' => ['F-1'],
    ]);

    AgriculturalProductDetail::query()->create([
        'shared_product_detail_id' => $fertilizerShared->id,
        'agricultural_product_type' => 'fertilizer',
    ]);

    $seed = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'subcategory_id' => $seedSubcategory->id,
        'name' => 'Seed AI',
        'name_ar' => 'بذور',
        'name_en' => 'Seed AI',
        'status' => Product::STATUS_APPROVED,
        'is_active' => false,
        'quantity' => 0,
    ]);

    $seedShared = SharedProductDetail::query()->create([
        'product_id' => $seed->id,
        'commercial_name' => 'Seed Search',
        'barcodes' => ['S-1'],
    ]);

    AgriculturalProductDetail::query()->create([
        'shared_product_detail_id' => $seedShared->id,
        'agricultural_product_type' => 'seed',
    ]);

    $this->getJson('/api/ai/products?subcategory_id='.$fertilizerSubcategory->id.'&product_type=fertilizer&search=Nitro&status=pending')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $fertilizer->id)
        ->assertJsonPath('filters.product_type', 'fertilizer')
        ->assertJsonPath('filters.search', 'Nitro');
});

test('ai product separate agriculture and veterinary list endpoints force category type', function () {
    Sanctum::actingAs(User::factory()->create());

    $agricultureCategory = Category::query()->create([
        'name' => 'AI Agriculture Only',
        'type' => Category::TYPE_AGRICULTURE,
    ]);

    $veterinaryCategory = Category::query()->create([
        'name' => 'AI Veterinary Only',
        'type' => Category::TYPE_VETERINARY,
    ]);

    $agricultureVendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $agricultureVendor->categories()->sync([$agricultureCategory->id]);

    $veterinaryVendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_VETERINARY,
    ]);
    $veterinaryVendor->categories()->sync([$veterinaryCategory->id]);

    $agricultureProduct = Product::factory()->for($agricultureVendor)->create([
        'category_id' => $agricultureCategory->id,
        'name' => 'Separated Agriculture Product',
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 7,
    ]);

    $agricultureShared = SharedProductDetail::query()->create([
        'product_id' => $agricultureProduct->id,
        'commercial_name' => 'Separated Agriculture Commercial',
        'barcodes' => ['AGR-LIST-1'],
    ]);

    AgriculturalProductDetail::query()->create([
        'shared_product_detail_id' => $agricultureShared->id,
        'agricultural_product_type' => 'fertilizer',
    ]);

    $veterinaryProduct = Product::factory()->for($veterinaryVendor)->create([
        'category_id' => $veterinaryCategory->id,
        'name' => 'Separated Veterinary Product',
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 9,
    ]);

    $veterinaryShared = SharedProductDetail::query()->create([
        'product_id' => $veterinaryProduct->id,
        'commercial_name' => 'Separated Veterinary Commercial',
        'barcodes' => ['VET-LIST-1'],
    ]);

    VeterinaryProductDetail::query()->create([
        'shared_product_detail_id' => $veterinaryShared->id,
        'dosage_form' => 'solution',
    ]);

    $this->getJson('/api/ai/products/agriculture?search=Separated')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $agricultureProduct->id)
        ->assertJsonPath('filters.category_type', Category::TYPE_AGRICULTURE);

    $this->getJson('/api/ai/products/veterinary?search=Separated')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $veterinaryProduct->id)
        ->assertJsonPath('filters.category_type', Category::TYPE_VETERINARY);
});

test('ai product show returns one product using external product resource', function () {
    Sanctum::actingAs(User::factory()->create());

    $category = Category::query()->create([
        'name' => 'AI Show Category',
        'type' => Category::TYPE_AGRICULTURE,
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
        'store_name' => 'Show Vendor',
    ]);
    $vendor->categories()->sync([$category->id]);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'name' => 'Shown Product',
        'name_ar' => 'منتج معروض',
        'name_en' => 'Shown Product',
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 3,
    ]);

    $sharedDetail = SharedProductDetail::query()->create([
        'product_id' => $product->id,
        'commercial_name' => 'Shown Commercial',
        'barcodes' => ['SHOW-1'],
    ]);

    AgriculturalProductDetail::query()->create([
        'shared_product_detail_id' => $sharedDetail->id,
        'agricultural_product_type' => 'pesticide',
        'formulation' => 'WP',
    ]);

    $this->getJson('/api/ai/products/'.$product->id)
        ->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.vendor.store_name', 'Show Vendor')
        ->assertJsonPath('data.shared.commercial_name', 'Shown Commercial')
        ->assertJsonPath('data.agricultural_detail.formulation', 'WP');
});
