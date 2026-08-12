<?php

use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * Public product documents (spec §25).
 *
 * The load-bearing property: only an `approved` document is ever reachable
 * by an unauthenticated visitor — by live status, not by where the file
 * happens to live on disk.
 */
function pdVendorProduct(): array
{
    $owner = User::factory()->create(['type' => User::TYPE_VENDOR]);
    $vendor = Vendor::factory()->create(['user_id' => $owner->id, 'is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);
    $product = Product::factory()->for($vendor)->create([
        'status' => Product::STATUS_APPROVED,
        'is_active' => true,
        'quantity' => 5,
    ]);

    return ['owner' => $owner, 'vendor' => $vendor, 'product' => $product];
}

it('lets a vendor upload a product document for review', function () {
    Storage::fake('local');
    $context = pdVendorProduct();

    Sanctum::actingAs($context['owner']);
    $this->postJson("/api/vendor/products/{$context['product']->id}/documents", [
        'type' => ProductDocument::TYPE_LEAFLET,
        'title' => 'Product leaflet',
        'language' => 'en',
        'file' => UploadedFile::fake()->create('leaflet.pdf', 200, 'application/pdf'),
    ])->assertCreated()
        ->assertJsonPath('data.status', ProductDocument::STATUS_PENDING_REVIEW);

    $document = ProductDocument::query()->where('product_id', $context['product']->id)->firstOrFail();
    Storage::disk('local')->assertExists($document->file_path);
    Storage::disk('public')->assertMissing($document->file_path);
});

it('is not publicly visible or downloadable until approved', function () {
    Storage::fake('local');
    $context = pdVendorProduct();
    $document = ProductDocument::factory()->create([
        'product_id' => $context['product']->id,
        'vendor_id' => $context['vendor']->id,
        'status' => ProductDocument::STATUS_PENDING_REVIEW,
    ]);
    Storage::disk('local')->put($document->file_path, 'pdf-bytes');

    $this->getJson("/api/products/{$context['product']->id}/documents")
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->getJson("/api/products/{$context['product']->id}/documents/{$document->id}/download")
        ->assertNotFound();
});

it('becomes publicly visible and downloadable once approved', function () {
    Storage::fake('local');
    $context = pdVendorProduct();
    $document = ProductDocument::factory()->create([
        'product_id' => $context['product']->id,
        'vendor_id' => $context['vendor']->id,
        'status' => ProductDocument::STATUS_PENDING_REVIEW,
    ]);
    Storage::disk('local')->put($document->file_path, 'pdf-bytes');

    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);
    $this->patchJson("/api/admin/product-documents/{$document->id}/review", ['status' => ProductDocument::STATUS_APPROVED])
        ->assertOk();

    $this->getJson("/api/products/{$context['product']->id}/documents")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson("/api/products/{$context['product']->id}/documents/{$document->id}/download")
        ->assertOk();
});

it('stops being publicly downloadable once disabled', function () {
    Storage::fake('local');
    $context = pdVendorProduct();
    $document = ProductDocument::factory()->create([
        'product_id' => $context['product']->id,
        'vendor_id' => $context['vendor']->id,
        'status' => ProductDocument::STATUS_APPROVED,
    ]);
    Storage::disk('local')->put($document->file_path, 'pdf-bytes');

    Sanctum::actingAs($context['owner']);
    $this->patchJson("/api/vendor/products/{$context['product']->id}/documents/{$document->id}/disable")
        ->assertOk()
        ->assertJsonPath('data.status', ProductDocument::STATUS_DISABLED);

    $this->getJson("/api/products/{$context['product']->id}/documents/{$document->id}/download")
        ->assertNotFound();
});

it('requires a rejection reason and prevents double review', function () {
    Storage::fake('local');
    $context = pdVendorProduct();
    $document = ProductDocument::factory()->create([
        'product_id' => $context['product']->id,
        'vendor_id' => $context['vendor']->id,
    ]);

    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/product-documents/{$document->id}/review", ['status' => ProductDocument::STATUS_REJECTED])
        ->assertStatus(422);

    $this->patchJson("/api/admin/product-documents/{$document->id}/review", ['status' => ProductDocument::STATUS_APPROVED])
        ->assertOk();
    $this->patchJson("/api/admin/product-documents/{$document->id}/review", ['status' => ProductDocument::STATUS_REJECTED, 'rejection_reason' => 'x'])
        ->assertStatus(422);
});

it('stops a vendor from managing another vendor\'s product documents', function () {
    Storage::fake('local');
    $contextA = pdVendorProduct();
    $contextB = pdVendorProduct();

    Sanctum::actingAs($contextB['owner']);
    $this->postJson("/api/vendor/products/{$contextA['product']->id}/documents", [
        'type' => ProductDocument::TYPE_OTHER,
        'title' => 'x',
        'language' => 'en',
        'file' => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf'),
    ])->assertForbidden();
});

it('allows several documents of the same type on one product', function () {
    Storage::fake('local');
    $context = pdVendorProduct();
    Sanctum::actingAs($context['owner']);

    $this->postJson("/api/vendor/products/{$context['product']->id}/documents", [
        'type' => ProductDocument::TYPE_LEAFLET,
        'title' => 'Arabic leaflet',
        'language' => 'ar',
        'file' => UploadedFile::fake()->create('ar.pdf', 100, 'application/pdf'),
    ])->assertCreated();

    $this->postJson("/api/vendor/products/{$context['product']->id}/documents", [
        'type' => ProductDocument::TYPE_LEAFLET,
        'title' => 'English leaflet',
        'language' => 'en',
        'file' => UploadedFile::fake()->create('en.pdf', 100, 'application/pdf'),
    ])->assertCreated();

    expect(ProductDocument::query()->where('product_id', $context['product']->id)->count())->toBe(2);
});
