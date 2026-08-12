<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\SharedProductDetail;
use App\Models\Vendor;
use App\Models\VeterinaryProductDetail;

/**
 * Product comparison (spec §29). Stateless — no comparison table exists;
 * every test drives the same GET endpoint with a fresh set of ids.
 */
function pcProduct(string $categoryType, array $overrides = []): Product
{
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $category = Category::factory()->create(['type' => $categoryType]);

    return Product::factory()->for($vendor)->create(array_merge([
        'category_id' => $category->id,
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 10,
    ], $overrides));
}

it('compares 2 to 4 products of the same type', function () {
    $a = pcProduct(Category::TYPE_AGRICULTURE);
    $b = pcProduct(Category::TYPE_AGRICULTURE);
    $c = pcProduct(Category::TYPE_AGRICULTURE);

    $response = $this->getJson('/api/products/compare?ids='.implode(',', [$a->id, $b->id, $c->id]))
        ->assertOk();

    expect($response->json('data.type'))->toBe(Category::TYPE_AGRICULTURE)
        ->and($response->json('data.products'))->toHaveCount(3);
});

it('returns products in the order the ids were requested, not database order', function () {
    $a = pcProduct(Category::TYPE_AGRICULTURE);
    $b = pcProduct(Category::TYPE_AGRICULTURE);

    $response = $this->getJson("/api/products/compare?ids={$b->id},{$a->id}")->assertOk();

    expect($response->json('data.products.0.id'))->toBe($b->id)
        ->and($response->json('data.products.1.id'))->toBe($a->id);
});

it('rejects fewer than 2 products', function () {
    $a = pcProduct(Category::TYPE_AGRICULTURE);

    $this->getJson("/api/products/compare?ids={$a->id}")->assertStatus(422);
});

it('rejects more than 4 products', function () {
    $ids = collect(range(1, 5))->map(fn () => pcProduct(Category::TYPE_AGRICULTURE)->id);

    $this->getJson('/api/products/compare?ids='.$ids->implode(','))->assertStatus(422);
});

it('rejects mixing agriculture and veterinary products', function () {
    $a = pcProduct(Category::TYPE_AGRICULTURE);
    $b = pcProduct(Category::TYPE_VETERINARY);

    $this->getJson("/api/products/compare?ids={$a->id},{$b->id}")->assertStatus(422);
});

it('rejects a product id that does not exist or is not publicly visible', function () {
    $a = pcProduct(Category::TYPE_AGRICULTURE);
    $hidden = pcProduct(Category::TYPE_AGRICULTURE, ['status' => Product::STATUS_PENDING]);

    $this->getJson("/api/products/compare?ids={$a->id},{$hidden->id}")->assertStatus(422);
    $this->getJson("/api/products/compare?ids={$a->id},999999")->assertStatus(422);
});

it('only includes specs relevant to the product\'s own type', function () {
    $vet = pcProduct(Category::TYPE_VETERINARY);
    $detail = SharedProductDetail::query()->create([
        'product_id' => $vet->id,
        'commercial_name' => 'Test Commercial Name',
    ]);
    VeterinaryProductDetail::query()->create([
        'shared_product_detail_id' => $detail->id,
        'dosage_form' => 'injection',
    ]);

    $other = pcProduct(Category::TYPE_VETERINARY);

    $response = $this->getJson("/api/products/compare?ids={$vet->id},{$other->id}")->assertOk();
    $specs = collect($response->json('data.products'))->firstWhere('id', $vet->id)['specs'];

    expect($specs)->toHaveKey('dosage_form')
        ->and($specs['dosage_form'])->toBe('injection')
        ->and($specs)->not->toHaveKey('target_crops');
});
