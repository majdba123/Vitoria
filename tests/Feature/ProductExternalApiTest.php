<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\SharedProductDetail;
use App\Models\Vendor;
use App\Models\VeterinaryProductDetail;

test('external product api returns localized name and veterinary payload', function () {
    $category = Category::query()->create([
        'name' => 'External Veterinary',
        'type' => Category::TYPE_VETERINARY,
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_VETERINARY,
    ]);
    $vendor->categories()->sync([$category->id]);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
        'name' => 'اسم افتراضي',
        'name_ar' => 'لقاح بيطري',
        'name_en' => 'Veterinary Vaccine',
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 15,
    ]);

    $sharedDetail = SharedProductDetail::query()->create([
        'product_id' => $product->id,
        'commercial_name' => 'Vet Shield',
        'barcode' => 'EXT-900',
        'barcodes' => ['EXT-900', 'EXT-901'],
        'package_size' => 100,
        'package_unit' => 'ml',
    ]);

    VeterinaryProductDetail::query()->create([
        'shared_product_detail_id' => $sharedDetail->id,
        'concentration' => '20%',
        'dosage_form' => 'injection',
        'target_species' => [['name_en' => 'Sheep']],
        'routes_of_administration' => ['intramuscular'],
    ]);

    $this->withHeader('Accept-Language', 'ar')
        ->getJson('/api/external/products/'.$product->id)
        ->assertOk()
        ->assertJsonPath('data.name', 'لقاح بيطري')
        ->assertJsonPath('data.type', Category::TYPE_VETERINARY)
        ->assertJsonPath('data.shared.name_ar', 'لقاح بيطري')
        ->assertJsonPath('data.shared.name_en', 'Veterinary Vaccine')
        ->assertJsonPath('data.shared.commercial_name', 'Vet Shield')
        ->assertJsonPath('data.veterinary_detail.concentration', '20%')
        ->assertJsonPath('data.veterinary_detail.dosage_form', 'injection');
});
