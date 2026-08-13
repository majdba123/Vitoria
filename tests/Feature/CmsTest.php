<?php

use App\Models\Banner;
use App\Models\Page;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * Admin-managed static pages and homepage banners (spec §38).
 *
 * The load-bearing properties: only published pages/active banners are
 * publicly visible, and admin writes are the only way to change them.
 */
it('lets an admin create, update, and delete a page', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $pageId = $this->postJson('/api/admin/pages', [
        'slug' => 'about-us',
        'title_en' => 'About Us',
        'title_ar' => 'من نحن',
        'content_en' => 'English content',
        'content_ar' => 'محتوى عربي',
    ])->assertCreated()->json('data.id');

    $this->patchJson("/api/admin/pages/{$pageId}", ['title_en' => 'About Vetora'])
        ->assertOk()
        ->assertJsonPath('data.title_en', 'About Vetora');

    $this->deleteJson("/api/admin/pages/{$pageId}")->assertOk();
    expect(Page::query()->find($pageId))->toBeNull();
});

it('only exposes published pages on the public endpoint', function () {
    Page::query()->create([
        'slug' => 'terms',
        'title_en' => 'Terms',
        'title_ar' => 'الشروط',
        'content_en' => 'Terms content',
        'content_ar' => 'محتوى الشروط',
        'is_published' => true,
    ]);

    Page::query()->create([
        'slug' => 'draft-page',
        'title_en' => 'Draft',
        'title_ar' => 'مسودة',
        'content_en' => 'Draft content',
        'content_ar' => 'محتوى المسودة',
        'is_published' => false,
    ]);

    $this->getJson('/api/pages/terms')->assertOk()->assertJsonPath('data.slug', 'terms');
    $this->getJson('/api/pages/draft-page')->assertNotFound();
});

it('lets an admin upload a banner and only shows currently-visible ones publicly', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/admin/banners', [
        'title_en' => 'Sale',
        'image' => UploadedFile::fake()->image('banner.jpg'),
        'sort_order' => 1,
        'is_active' => true,
    ])->assertCreated();

    Storage::disk('public')->assertExists($response->json('data.image_path'));

    Banner::query()->create([
        'image_path' => 'banners/inactive.jpg',
        'is_active' => false,
        'sort_order' => 2,
    ]);

    $publicResponse = $this->getJson('/api/banners')->assertOk();
    $titles = collect($publicResponse->json('data'))->pluck('title_en');

    expect($titles)->toContain('Sale')
        ->and($publicResponse->json('data'))->toHaveCount(1);
});

it('deletes the stored image file when a banner is deleted', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $path = UploadedFile::fake()->image('gone.jpg')->store('banners', 'public');
    $banner = Banner::query()->create(['image_path' => $path, 'is_active' => true]);

    Storage::disk('public')->assertExists($path);

    $this->deleteJson("/api/admin/banners/{$banner->id}")->assertOk();

    Storage::disk('public')->assertMissing($path);
});

it('rejects page and banner management from a non-admin user', function () {
    $customer = User::factory()->create(['type' => User::TYPE_USER]);
    Sanctum::actingAs($customer);

    $this->postJson('/api/admin/pages', ['slug' => 'x'])->assertForbidden();
    $this->getJson('/api/admin/banners')->assertForbidden();
});
