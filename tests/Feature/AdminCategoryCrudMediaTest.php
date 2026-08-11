<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function actingAsAdminForCategoryCrud(): User
{
    $admin = User::factory()->admin()->create();

    Sanctum::actingAs($admin);

    return $admin;
}

test('admin category crud uses one uploaded image everywhere', function () {
    Storage::fake('public');
    actingAsAdminForCategoryCrud();

    $createResponse = $this->post('/api/admin/categories', [
        'name' => 'Seeds',
        'type' => Category::TYPE_AGRICULTURE,
        'commission' => 5,
        'logo' => UploadedFile::fake()->image('category.png', 300, 300),
    ], ['Accept' => 'application/json']);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Seeds')
        ->assertJsonPath('data.icon_class', null);

    $category = Category::query()->firstOrFail();

    expect($category->logo)->toStartWith('categories/')
        ->and($category->icon)->toBe($category->logo)
        ->and($createResponse->json('data.image_url'))->toContain('/storage/categories/');

    Storage::disk('public')->assertExists($category->logo);

    $oldImage = $category->logo;

    $updateResponse = $this->post('/api/admin/categories/'.$category->id, [
        '_method' => 'PUT',
        'name' => 'Seeds Updated',
        'type' => Category::TYPE_AGRICULTURE,
        'commission' => 7.5,
        'logo' => UploadedFile::fake()->image('category-updated.webp', 320, 320),
    ], ['Accept' => 'application/json']);

    $updateResponse->assertOk()
        ->assertJsonPath('data.name', 'Seeds Updated')
        ->assertJsonPath('data.icon_class', null);

    $category->refresh();

    expect($category->logo)->not->toBe($oldImage)
        ->and($category->icon)->toBe($category->logo)
        ->and($updateResponse->json('data.image_url'))->toContain('/storage/categories/');

    Storage::disk('public')->assertMissing($oldImage);
    Storage::disk('public')->assertExists($category->logo);
});

test('admin category creation rejects svg logo uploads', function () {
    Storage::fake('public');
    actingAsAdminForCategoryCrud();

    $response = $this->post('/api/admin/categories', [
        'name' => 'Svg Seeds',
        'type' => Category::TYPE_AGRICULTURE,
        'commission' => 5,
        'logo' => UploadedFile::fake()->createWithContent(
            'category.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
        ),
    ], ['Accept' => 'application/json']);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['logo']);

    expect(Category::query()->where('name', 'Svg Seeds')->exists())->toBeFalse();
});

test('empty category type filter returns all categories for admin and public endpoints', function () {
    actingAsAdminForCategoryCrud();

    $agricultureCategory = Category::query()->create([
        'name' => 'All Filter Agriculture',
        'type' => Category::TYPE_AGRICULTURE,
        'commission' => 5,
    ]);

    $veterinaryCategory = Category::query()->create([
        'name' => 'All Filter Veterinary',
        'type' => Category::TYPE_VETERINARY,
        'commission' => 6,
    ]);

    $adminIds = collect($this->getJson('/api/admin/categories?per_page=100&type=')
        ->assertOk()
        ->json('data'))
        ->pluck('id');

    $publicIds = collect($this->getJson('/api/categories?per_page=100&type=')
        ->assertOk()
        ->json('data'))
        ->pluck('id');

    expect($adminIds)->toContain($agricultureCategory->id, $veterinaryCategory->id)
        ->and($publicIds)->toContain($agricultureCategory->id, $veterinaryCategory->id);
});

test('admin can open and list veterinary categories without storefront type restrictions', function () {
    actingAsAdminForCategoryCrud();

    $agricultureCategory = Category::query()->create([
        'name' => 'Admin Agriculture Category',
        'type' => Category::TYPE_AGRICULTURE,
        'commission' => 5,
    ]);

    $veterinaryCategory = Category::query()->create([
        'name' => 'Admin Veterinary Category',
        'type' => Category::TYPE_VETERINARY,
        'commission' => 6,
    ]);

    $this->getJson('/api/admin/categories?per_page=100')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);

    $this->getJson('/api/admin/categories/'.$veterinaryCategory->id.'?type='.Category::TYPE_AGRICULTURE)
        ->assertOk()
        ->assertJsonPath('data.id', $veterinaryCategory->id);

    $categoryIds = collect($this->getJson('/api/admin/categories?per_page=100')
        ->assertOk()
        ->json('data'))
        ->pluck('id');

    expect($categoryIds)->toContain($agricultureCategory->id, $veterinaryCategory->id);
});

test('admin cannot delete a category that still has products assigned to it', function () {
    actingAsAdminForCategoryCrud();

    $category = Category::query()->create([
        'name' => 'Category With Products',
        'type' => Category::TYPE_AGRICULTURE,
        'commission' => 5,
    ]);

    $product = Product::factory()->create(['category_id' => $category->id]);

    $this->deleteJson('/api/admin/categories/'.$category->id)
        ->assertStatus(422);

    expect(Category::query()->find($category->id))->not->toBeNull()
        ->and(Product::query()->find($product->id))->not->toBeNull();
});

test('admin can delete a category once it has no products left', function () {
    actingAsAdminForCategoryCrud();

    $category = Category::query()->create([
        'name' => 'Empty Category',
        'type' => Category::TYPE_AGRICULTURE,
        'commission' => 5,
    ]);

    $this->deleteJson('/api/admin/categories/'.$category->id)
        ->assertOk();

    expect(Category::query()->find($category->id))->toBeNull();
});

test('project source no longer contains legacy flat category-id-only product references', function () {
    // NOTE: Subcategory is a real, currently-supported taxonomy layer (Product belongsTo
    // Subcategory, with dedicated controllers/requests/resources/migrations/factories/seeders
    // — see app/Models/Subcategory.php and database/migrations/2026_07_22_004014_create_subcategories_table.php).
    // It was intentionally reintroduced after an earlier "remove the subcategory layer" attempt
    // was reverted, so asserting zero occurrences of the word "subcategory" anywhere in app/,
    // routes/, database/factories, database/migrations, and tests/ is stale — it would only pass
    // by ripping out an intentional, actively-used feature. Instead, guard against the one thing
    // that actually mattered here: legacy code paths that only understood a flat category_id and
    // never learned about subcategory_id at all (e.g. a request validator or resource that was
    // never updated after subcategories were introduced).
    $legacyOnlyFiles = [
        base_path('app/Http/Requests/Admin/StoreProductRequest.php'),
        base_path('app/Http/Requests/Admin/UpdateProductRequest.php'),
        base_path('app/Http/Requests/Vendor/StoreProductRequest.php'),
        base_path('app/Http/Requests/Vendor/UpdateProductRequest.php'),
    ];

    $matches = [];

    foreach ($legacyOnlyFiles as $file) {
        if (! is_file($file)) {
            continue;
        }

        $contents = file_get_contents($file);

        if ($contents !== false && str_contains($contents, 'category_id') && ! str_contains($contents, 'subcategory_id')) {
            $matches[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);
        }
    }

    expect($matches)->toBe([]);
});
