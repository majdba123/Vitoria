<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Syndicate;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\ArabicDemoDatabaseSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    Storage::fake('public');
    Cache::flush();
});

function seedArabicDemo(): void
{
    test()->seed(ArabicDemoDatabaseSeeder::class);
}

test('application locale and framework messages are Arabic first', function () {
    expect(config('app.name'))->toBe('Vetora')
        ->and(config('app.locale'))->toBe('ar')
        ->and(__('auth.failed'))->toBe('بيانات الدخول غير صحيحة.')
        ->and(__('pagination.next'))->toContain('التالي')
        ->and(__('validation.required', ['attribute' => 'الاسم']))->toBe('حقل الاسم مطلوب.')
        ->and(__('nav.categories'))->toBe('التصنيفات');
});

test('arabic demo seeder creates syndicate accounts with valid credentials', function () {
    seedArabicDemo();

    $agricultureUser = User::query()->where('email', 'agriculture.syndicate@vetora.test')->firstOrFail();
    $veterinaryUser = User::query()->where('email', 'veterinary.syndicate@vetora.test')->firstOrFail();

    expect($agricultureUser->name)->toBe('النقابة الزراعية')
        ->and($agricultureUser->type)->toBe(User::TYPE_SYNDICATE)
        ->and(Hash::check('password', $agricultureUser->password))->toBeTrue()
        ->and($veterinaryUser->name)->toBe('النقابة البيطرية')
        ->and($veterinaryUser->type)->toBe(User::TYPE_SYNDICATE)
        ->and(Syndicate::query()->where('type', Category::TYPE_AGRICULTURE)->count())->toBe(1)
        ->and(Syndicate::query()->where('type', Category::TYPE_VETERINARY)->count())->toBe(1);
});

test('arabic categories subcategories and media are seeded by supported type', function () {
    seedArabicDemo();

    expect(Category::query()->where('type', Category::TYPE_AGRICULTURE)->count())->toBe(8)
        ->and(Category::query()->where('type', Category::TYPE_VETERINARY)->count())->toBe(8)
        ->and(Category::query()->where('name', 'البذور')->where('type', Category::TYPE_AGRICULTURE)->exists())->toBeTrue()
        ->and(Category::query()->where('name', 'اللقاحات')->where('type', Category::TYPE_VETERINARY)->exists())->toBeTrue()
        ->and(Category::query()->whereIn('name', ['Seeds', 'Fertilizers', 'Vaccines'])->exists())->toBeFalse();

    Category::query()->with('subcategories')->get()->each(function (Category $category): void {
        expect($category->name)->toMatch('/\p{Arabic}/u')
            ->and($category->type)->toBeIn([Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY])
            ->and($category->subcategories)->not->toBeEmpty()
            ->and(Storage::disk('public')->exists($category->logo))->toBeTrue()
            ->and(Storage::disk('public')->exists($category->icon))->toBeTrue();
    });
});

test('vendors are Arabic and attached only to categories matching their business type', function () {
    seedArabicDemo();

    $agricultureVendor = Vendor::query()->where('business_type', Vendor::BUSINESS_TYPE_AGRICULTURE)->with('categories')->firstOrFail();
    $veterinaryVendor = Vendor::query()->where('business_type', Vendor::BUSINESS_TYPE_VETERINARY)->with('categories')->firstOrFail();

    expect($agricultureVendor->store_name)->toBe('تاجر المستلزمات الزراعية')
        ->and($agricultureVendor->categories)->toHaveCount(8)
        ->and($agricultureVendor->categories->pluck('type')->unique()->values()->all())->toBe([Category::TYPE_AGRICULTURE])
        ->and($veterinaryVendor->store_name)->toBe('تاجر المستلزمات البيطرية')
        ->and($veterinaryVendor->categories)->toHaveCount(8)
        ->and($veterinaryVendor->categories->pluck('type')->unique()->values()->all())->toBe([Category::TYPE_VETERINARY]);
});

test('products are Arabic approved active and use valid images icons and vendor categories', function () {
    seedArabicDemo();

    expect(Product::query()->count())->toBe(16)
        ->and(Product::query()->where('status', Product::STATUS_APPROVED)->count())->toBe(16)
        ->and(Product::query()->where('is_active', true)->count())->toBe(16);

    Product::query()
        ->with(['vendor.categories', 'subcategory.category', 'photos'])
        ->get()
        ->each(function (Product $product): void {
            $category = $product->subcategory->category;

            expect($product->name)->toMatch('/\p{Arabic}/u')
                ->and($product->description)->toMatch('/\p{Arabic}/u')
                ->and($product->vendor->categories->pluck('id'))->toContain($category->id)
                ->and($product->vendor->business_type)->toBe($category->type)
                ->and(Storage::disk('public')->exists($product->image))->toBeTrue()
                ->and(Storage::disk('public')->exists($product->icon))->toBeTrue()
                ->and($product->photos)->toHaveCount(1)
                ->and(Storage::disk('public')->exists($product->photos->first()->path))->toBeTrue();
        });
});

test('arabic demo seeder is safe to run more than once', function () {
    seedArabicDemo();
    seedArabicDemo();

    expect(Category::query()->count())->toBe(16)
        ->and(Syndicate::query()->count())->toBe(2)
        ->and(Vendor::query()->count())->toBe(2)
        ->and(Product::query()->count())->toBe(16);
});

test('syndicate users see only seeded data for their assigned type', function () {
    seedArabicDemo();

    $agricultureUser = User::query()->where('email', 'agriculture.syndicate@vetora.test')->firstOrFail();
    Sanctum::actingAs($agricultureUser);

    $agricultureCategories = collect($this->getJson('/api/syndicate/categories')->assertOk()->json('data'));
    $agricultureProductIds = collect($this->getJson('/api/syndicate/products')->assertOk()->json('data'))->pluck('id');

    expect($agricultureCategories->pluck('type')->unique()->values()->all())->toBe([Category::TYPE_AGRICULTURE])
        ->and(Product::query()->whereIn('id', $agricultureProductIds)->whereHas('subcategory.category', fn ($query) => $query->where('type', Category::TYPE_AGRICULTURE))->count())->toBe(8);

    $veterinaryUser = User::query()->where('email', 'veterinary.syndicate@vetora.test')->firstOrFail();
    Sanctum::actingAs($veterinaryUser);

    $veterinaryCategories = collect($this->getJson('/api/syndicate/categories')->assertOk()->json('data'));
    $veterinaryProductIds = collect($this->getJson('/api/syndicate/products')->assertOk()->json('data'))->pluck('id');

    expect($veterinaryCategories->pluck('type')->unique()->values()->all())->toBe([Category::TYPE_VETERINARY])
        ->and(Product::query()->whereIn('id', $veterinaryProductIds)->whereHas('subcategory.category', fn ($query) => $query->where('type', Category::TYPE_VETERINARY))->count())->toBe(8);
});

test('admin dashboard statistics reflect seeded Arabic demo data', function () {
    seedArabicDemo();

    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/admin/dashboard/overview')->assertOk();

    $byType = collect($response->json('data.syndicates_by_type'));

    expect($response->json('data.total_vendors'))->toBe(2)
        ->and($response->json('data.total_products'))->toBe(16)
        ->and($response->json('data.total_categories'))->toBe(16)
        ->and($response->json('data.total_syndicates'))->toBe(2)
        ->and($byType->firstWhere('type', Category::TYPE_AGRICULTURE)['total'])->toBe(1)
        ->and($byType->firstWhere('type', Category::TYPE_VETERINARY)['total'])->toBe(1);
});
