<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('translation php files are synchronized between arabic and english', function () {
    $enFiles = collect(File::files(lang_path('en')))
        ->map(fn ($file) => $file->getFilename())
        ->sort()
        ->values()
        ->all();

    $arFiles = collect(File::files(lang_path('ar')))
        ->map(fn ($file) => $file->getFilename())
        ->sort()
        ->values()
        ->all();

    expect($arFiles)->toBe($enFiles);

    $filesToCompare = collect($enFiles)
        ->reject(fn (string $filename) => in_array($filename, ['validation.php'], true))
        ->values()
        ->all();

    foreach ($filesToCompare as $filename) {
        $english = require lang_path('en/'.$filename);
        $arabic = require lang_path('ar/'.$filename);

        expect(flattenTranslationKeys($arabic))
            ->toBe(flattenTranslationKeys($english), "Translation structure mismatch in {$filename}");
    }
});

test('locale switch persists and updates page direction', function () {
    $this->from('/')
        ->get('/locale/en')
        ->assertRedirect('/');

    $this->get('/')
        ->assertOk()
        ->assertSee('dir="ltr"', false)
        ->assertSee('lang="en"', false);

    $this->from('/')
        ->get('/locale/ar')
        ->assertRedirect('/');

    $this->get('/')
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('lang="ar"', false);
});

test('admin and vendor product detail pages carry the selected locale to the frontend', function () {
    // Admin/Vendor Products/Show only receive a `productId` prop and fetch
    // their own localized labels client-side, so what the backend actually
    // guarantees — and what this now checks — is that the right product and
    // the right `locale`/`i18n` context reach the right page for each role.
    $category = Category::query()->create([
        'name' => 'Localized Category',
        'type' => Category::TYPE_AGRICULTURE,
    ]);

    $vendor = Vendor::factory()->create([
        'business_type' => Vendor::BUSINESS_TYPE_AGRICULTURE,
    ]);
    $vendor->user->update(['type' => User::TYPE_VENDOR]);
    $vendor->categories()->sync([$category->id]);

    $product = Product::factory()->for($vendor)->create([
        'category_id' => $category->id,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->withSession(['locale' => 'en'])
        ->get("/admin/products/{$product->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Products/Show')
            ->where('productId', $product->id)
            ->where('locale', 'en')
            ->where('i18n.products.overview_badge', trans('products.overview_badge', [], 'en'))
        );

    $this->actingAs($vendor->user)
        ->withSession(['locale' => 'ar'])
        ->get("/vendor/products/{$product->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Vendor/Products/Show')
            ->where('productId', (string) $product->id)
            ->where('locale', 'ar')
            ->where('i18n.products.overview_badge', trans('products.overview_badge', [], 'ar'))
        );
});

/**
 * @param  array<string, mixed>  $translations
 * @return list<string>
 */
function flattenTranslationKeys(array $translations, string $prefix = ''): array
{
    $keys = [];

    foreach ($translations as $key => $value) {
        $fullKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

        if (is_array($value)) {
            $keys = array_merge($keys, flattenTranslationKeys($value, $fullKey));

            continue;
        }

        $keys[] = $fullKey;
    }

    sort($keys);

    return $keys;
}
