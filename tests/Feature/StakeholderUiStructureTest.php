<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Laravel\Sanctum\Sanctum;

test('stakeholder UI structure removes duplicated navigation and keeps shared headers centered', function () {
    $home = file_get_contents(resource_path('js/Pages/Home.jsx'));
    $table = file_get_contents(resource_path('js/Components/ui/table.jsx'));
    $vendorNavigation = file_get_contents(resource_path('js/lib/nav-vendor.js'));
    $legacyVendorNavigation = file_get_contents(resource_path('views/components/vendor/sidebar.blade.php'));
    $vendorDetails = file_get_contents(resource_path('js/Components/vendor360/Vendor360.jsx'));
    $routes = file_get_contents(base_path('routes/web.php'));

    expect($home)->not->toContain('commerce-section-header border-t-2 border-foreground pt-7')
        ->and($table)->toContain('[&_th]:text-center', 'h-10 px-3 text-center')
        ->and($vendorNavigation)->not->toContain('vendor.discounts.index', 't.discounts')
        ->and($vendorNavigation)->toContain("route: 'vendor.sales'")
        ->and($legacyVendorNavigation)->not->toContain('vendor.discounts.index', 'vendor.commission')
        ->and($legacyVendorNavigation)->toContain("'route' => 'vendor.sales'")
        ->and($routes)->toContain("Route::get('/sales'")
        ->and($routes)->not->toContain("Inertia::render('Vendor/Products/Index', ['discountOnly' => true])")
        ->and($vendorDetails)->toContain("? ['products', 'orders', 'sales']")
        ->not->toContain("? ['overview', 'products', 'orders', 'finance', 'returns', 'staff', 'documents', 'activity']");
});

test('register page uses the Login family at desktop and remains single column on narrow screens', function () {
    $register = file_get_contents(resource_path('js/Pages/Auth/Register.jsx'));

    expect($register)->toContain('grid min-h-svh bg-background lg:grid-cols-', 'w-full max-w-4xl', 'grid gap-5 sm:grid-cols-2', 'h-11 text-base')
        ->toContain('<LanguageSwitcher />', '<ThemeToggle')
        ->not->toContain('role', 'business_type', 'vendor');
});

test('admin order detail response reconciles quantity prices line and final totals', function () {
    $admin = User::factory()->admin()->create();
    $vendor = Vendor::factory()->create();
    $category = Category::factory()->create(['commission' => 10]);
    $product = Product::factory()->for($vendor)->create(['category_id' => $category->id]);
    $order = Order::factory()->for($vendor)->create([
        'subtotal_amount' => 270,
        'coupon_discount_amount' => 20,
        'shipping_total' => 30,
        'tax_total' => 0,
        'grand_total' => 280,
        'total_amount' => 280,
    ]);
    OrderItem::factory()->for($order)->for($product)->create([
        'product_name' => 'Stakeholder QA product',
        'quantity' => 3,
        'original_unit_price' => 100,
        'has_discount' => true,
        'applied_discount_percentage' => 10,
        'unit_price' => 90,
        'discount_amount' => 30,
        'line_total' => 270,
    ]);
    Sanctum::actingAs($admin);

    $this->getJson("/api/admin/orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('data.items.0.quantity', 3)
        ->assertJsonPath('data.items.0.original_unit_price', '100.00')
        ->assertJsonPath('data.items.0.unit_price', '90.00')
        ->assertJsonPath('data.items.0.line_total', '270.00')
        ->assertJsonPath('data.subtotal_amount', '270.00')
        ->assertJsonPath('data.grand_total', '280.00');

    $source = file_get_contents(resource_path('js/Pages/Admin/Orders/Show.jsx'));
    expect($source)->toContain('item.has_discount && Number(item.original_unit_price) !== Number(item.unit_price)')
        ->toContain('visible: Number(order.shipping_total) > 0', 'visible: Number(order.tax_total) > 0')
        ->toContain('order.grand_total ?? order.total_amount');
});
