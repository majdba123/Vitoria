<?php

use App\Models\Category;
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

test('project source no longer contains legacy category-layer references', function () {
    $roots = [
        base_path('app'),
        base_path('routes'),
        base_path('database/factories'),
        base_path('database/migrations'),
        base_path('tests'),
    ];

    $matches = [];
    $needle = 'sub'.'categor';
    $pattern = '/'.$needle.'(?:y|ies)/i';

    foreach ($roots as $root) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($iterator as $file) {
            if (
                ! $file->isFile() ||
                str_contains($file->getFilename(), 'CategorySubcategorySeeder') ||
                str_contains($file->getFilename(), 'AdminCategoryCrudMediaTest.php') ||
                str_contains($file->getFilename(), 'SeederDataIntegrityTest.php')
            ) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if ($contents !== false && preg_match($pattern, $contents) === 1) {
                $matches[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }
    }

    expect($matches)->toBe([]);
});
