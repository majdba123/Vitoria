<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Laravel\Sanctum\Sanctum;

function categoryIdsVisibleToVendor(Vendor $vendor): array
{
    Sanctum::actingAs($vendor->user);

    return collect(test()->getJson('/api/vendor/allowed-categories')
        ->assertOk()
        ->json('data'))
        ->pluck('id')
        ->all();
}

test('vendor category endpoint returns every database category compatible with the vendor business type', function () {
    $agriculture = Category::factory()->create(['type' => Category::TYPE_AGRICULTURE]);
    $veterinary = Category::factory()->create(['type' => Category::TYPE_VETERINARY]);

    $agricultureVendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE]);
    $veterinaryVendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_VETERINARY]);
    $bothVendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_BOTH]);

    expect(categoryIdsVisibleToVendor($agricultureVendor))
        ->toContain($agriculture->id)
        ->not->toContain($veterinary->id)
        ->and(categoryIdsVisibleToVendor($veterinaryVendor))
        ->toContain($veterinary->id)
        ->not->toContain($agriculture->id)
        ->and(categoryIdsVisibleToVendor($bothVendor))
        ->toContain($agriculture->id, $veterinary->id);
});

test('new admin category is immediately visible only to compatible vendors', function () {
    $admin = User::factory()->admin()->create();
    $agricultureVendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE]);
    $veterinaryVendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_VETERINARY]);

    Sanctum::actingAs($admin);
    $categoryId = $this->postJson('/api/admin/categories', [
        'name' => 'New Admin Agriculture Category',
        'type' => Category::TYPE_AGRICULTURE,
    ])->assertCreated()->json('data.id');

    expect(categoryIdsVisibleToVendor($agricultureVendor))->toContain($categoryId)
        ->and(categoryIdsVisibleToVendor($veterinaryVendor))->not->toContain($categoryId);
});

test('vendor cannot mutate categories through real admin routes', function () {
    $vendor = Vendor::factory()->create();
    $category = Category::factory()->create();
    Sanctum::actingAs($vendor->user);

    $this->postJson('/api/admin/categories', [
        'name' => 'Forbidden Category',
        'type' => Category::TYPE_AGRICULTURE,
    ])->assertForbidden();
    $this->patchJson('/api/admin/categories/'.$category->id, ['name' => 'Forbidden Update'])->assertForbidden();
    $this->deleteJson('/api/admin/categories/'.$category->id)->assertForbidden();

    expect($category->refresh()->name)->not->toBe('Forbidden Update')
        ->and(Category::query()->where('name', 'Forbidden Category')->exists())->toBeFalse();
});

test('vendor add and edit product pages use the shared backend category endpoint', function () {
    $vendor = Vendor::factory()->create(['business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE]);
    $category = Category::factory()->create(['type' => Category::TYPE_AGRICULTURE]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'category_id' => $category->id]);

    $this->actingAs($vendor->user)
        ->get('/vendor/products/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Vendor/Products/Create'));

    $this->actingAs($vendor->user)
        ->get('/vendor/products/'.$product->id.'/edit')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vendor/Products/Edit')
            ->where('productId', (string) $product->id));

    expect(file_get_contents(resource_path('js/Pages/Vendor/Products/Create.jsx')))
        ->toContain("window.axios.get('/api/vendor/allowed-categories'")
        ->and(file_get_contents(resource_path('js/Pages/Vendor/Products/Edit.jsx')))
        ->toContain("window.axios.get('/api/vendor/allowed-categories'");
});
