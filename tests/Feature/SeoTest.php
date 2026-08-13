<?php

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Laravel\Sanctum\Sanctum;

/**
 * SEO meta fields, sitemap, and robots.txt (spec §39).
 */
it('persists meta_title and meta_description on a category', function () {
    $admin = User::factory()->create(['type' => User::TYPE_ADMIN]);
    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/admin/categories', [
        'name' => 'Feed',
        'type' => Category::TYPE_AGRICULTURE,
        'meta_title' => 'Animal Feed | Vetora',
        'meta_description' => 'Shop animal feed products.',
    ])->assertCreated();

    expect($response->json('data.meta_title'))->toBe('Animal Feed | Vetora')
        ->and($response->json('data.meta_description'))->toBe('Shop animal feed products.');
});

it('only lists published pages and approved active products in the sitemap', function () {
    $vendor = Vendor::factory()->create(['is_active' => true, 'status' => Vendor::STATUS_ACTIVE]);

    Page::query()->create([
        'slug' => 'faq',
        'title_en' => 'FAQ',
        'title_ar' => 'الأسئلة الشائعة',
        'content_en' => 'x',
        'content_ar' => 'x',
        'is_published' => true,
    ]);

    Product::factory()->for($vendor)->create(['status' => Product::STATUS_APPROVED, 'is_active' => true]);
    Product::factory()->for($vendor)->create(['status' => Product::STATUS_PENDING, 'is_active' => true]);

    $response = $this->get('/api/sitemap.xml')->assertOk();
    $xml = $response->getContent();

    expect($response->headers->get('Content-Type'))->toContain('xml')
        ->and($xml)->toContain('/pages/faq');

    $approvedProduct = Product::query()->where('status', Product::STATUS_APPROVED)->firstOrFail();
    $pendingProduct = Product::query()->where('status', Product::STATUS_PENDING)->firstOrFail();

    expect($xml)->toContain('/products/'.$approvedProduct->id)
        ->and($xml)->not->toContain('/products/'.$pendingProduct->id);
});

it('serves robots.txt pointing at the sitemap', function () {
    $response = $this->get('/api/robots.txt')->assertOk();

    expect($response->getContent())->toContain('Sitemap:')
        ->and($response->getContent())->toContain('Disallow: /api/admin/');
});
