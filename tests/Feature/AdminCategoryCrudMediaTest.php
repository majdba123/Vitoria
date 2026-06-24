<?php

use App\Models\Category;
use App\Models\Subcategory;
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

test('admin subcategory crud uses image only and returns image url', function () {
    Storage::fake('public');
    actingAsAdminForCategoryCrud();

    $category = Category::query()->create([
        'name' => 'Fertilizers',
        'type' => Category::TYPE_AGRICULTURE,
        'commission' => 4.5,
    ]);

    $createResponse = $this->post('/api/admin/subcategories', [
        'category_id' => $category->id,
        'name' => 'Organic Fertilizers',
        'image' => UploadedFile::fake()->image('subcategory.jpg', 240, 240),
    ], ['Accept' => 'application/json']);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Organic Fertilizers')
        ->assertJsonPath('data.icon_class', null);

    $subcategory = Subcategory::query()->firstOrFail();

    expect($subcategory->image)->toStartWith('subcategories/')
        ->and($createResponse->json('data.image_url'))->toContain('/storage/subcategories/');

    Storage::disk('public')->assertExists($subcategory->image);

    $oldImage = $subcategory->image;

    $updateResponse = $this->post('/api/admin/subcategories/'.$subcategory->id, [
        '_method' => 'PUT',
        'category_id' => $category->id,
        'name' => 'Organic Fertilizers Updated',
        'image' => UploadedFile::fake()->image('subcategory-updated.png', 260, 260),
    ], ['Accept' => 'application/json']);

    $updateResponse->assertOk()
        ->assertJsonPath('data.name', 'Organic Fertilizers Updated')
        ->assertJsonPath('data.icon_class', null);

    $subcategory->refresh();

    expect($subcategory->image)->not->toBe($oldImage)
        ->and($updateResponse->json('data.image_url'))->toContain('/storage/subcategories/');

    Storage::disk('public')->assertMissing($oldImage);
    Storage::disk('public')->assertExists($subcategory->image);
});
