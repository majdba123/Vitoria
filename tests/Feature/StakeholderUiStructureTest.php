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
        ->and($table)->toContain('[&_th]:text-center', 'h-11 px-4 text-center')
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

test('shared desktop primitives use the approved bounded readable scale', function () {
    $styles = file_get_contents(resource_path('css/app.css'));
    $button = file_get_contents(resource_path('js/Components/ui/button.jsx'));
    $input = file_get_contents(resource_path('js/Components/ui/input.jsx'));
    $select = file_get_contents(resource_path('js/Components/ui/select.jsx'));
    $table = file_get_contents(resource_path('js/Components/ui/table.jsx'));
    $dialog = file_get_contents(resource_path('js/Components/ui/dialog.jsx'));
    $statCard = file_get_contents(resource_path('js/Components/admin/dashboard/StatCard.jsx'));
    $metricGrid = file_get_contents(resource_path('js/Components/admin/dashboard/MetricTileGrid.jsx'));
    $syndicateHeader = file_get_contents(resource_path('js/Components/syndicate/SyndicateHeader.jsx'));

    expect($styles)
        ->toContain('--page-max: 100rem;', '--content-max: 90rem;')
        ->toContain('font-size: 0.9375rem;', 'text-center text-[13px]')
        ->toContain(".dashboard-body [data-sidebar='menu-button'] > svg")
        ->and($button)->toContain('default: "h-11', 'sm: "h-10', 'icon: "size-11"')
        ->and($input)->toContain('"h-11', 'text-base')->not->toContain('md:text-sm')
        ->and($select)->toContain('data-[size=default]:h-11', 'data-[size=sm]:h-10')
        ->and($table)->toContain('lg:text-[0.9375rem]', 'lg:h-12 lg:text-sm', 'px-4 py-3')
        ->and($dialog)->toContain('sm:max-w-xl sm:p-7')
        ->and($statCard)->toContain('text-sm font-semibold', 'lg:text-3xl', 'size-12')
        ->and($metricGrid)->toContain('text-sm font-semibold', 'text-2xl font-bold');

    expect($syndicateHeader)->toContain('hidden size-11 sm:inline-flex');
});

test('Vendor 360 and report templates keep accounting and PDF labels separated and localized', function () {
    $vendor360 = file_get_contents(resource_path('js/Components/vendor360/Vendor360.jsx'));
    $vendorSales = file_get_contents(resource_path('js/Pages/Vendor/Commission.jsx'));
    $syndicateDashboard = file_get_contents(resource_path('js/Pages/Syndicate/Dashboard.jsx'));
    $arabic = require lang_path('ar/vendor_analytics.php');
    $english = require lang_path('en/vendor_analytics.php');
    $generalReport = file_get_contents(resource_path('views/reports/syndicate-general.blade.php'));
    $vendorReport = file_get_contents(resource_path('views/reports/syndicate-vendor.blade.php'));
    $generalPdfService = file_get_contents(app_path('Services/Syndicate/SyndicateReportService.php'));
    $vendorPdfService = file_get_contents(app_path('Services/Vendor/SyndicateVendorPdfService.php'));

    expect($vendor360)
        ->not->toContain('Credit / دائن', 'Debit / مدين', '<span>From</span>', '<span>To</span>', '} orders</p>')
        ->toContain('labels.credit : labels.debit', 'labels[`ledger_${row.type}`]', "locale.startsWith('ar') ? 'ل.س' : 'SYP'", 'alt={row.name}')
        ->and($arabic['credit'])->toBe('دائن')
        ->and($arabic['debit'])->toBe('مدين')
        ->and($english['credit'])->toBe('Credit')
        ->and($english['debit'])->toBe('Debit')
        ->and($vendorSales)->toContain('vendor.currency_syp', "toLocaleDateString(locale, { weekday: 'short' })")
        ->not->toContain('} SYP`', 'No completed orders found.', 'No trend data available.')
        ->and($syndicateDashboard)->toContain('syndicate.currency_syp', 'i18n[`status_${r.status}`]')
        ->not->toContain('} SYP`')
        ->and($generalReport)->toContain('{{ $l[\'vendors\'] }}<br><strong>')
        ->and($vendorReport)->toContain('{{ $translatedValue($row[\'status\']) }}')
        ->and(substr_count($vendorReport, '{{ $labels[\'city\'] }}:'))->toBe(1)
        ->and($generalPdfService)->toContain('<div dir="ltr" style="direction:ltr;')
        ->and($vendorPdfService)->toContain('<div dir="ltr" style="direction:ltr;');
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
        ->toContain("copy[order.payment_way || 'cash']")
        ->toContain('order.grand_total ?? order.total_amount');
});
