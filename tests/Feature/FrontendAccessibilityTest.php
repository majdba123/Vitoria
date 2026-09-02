<?php

use Inertia\Testing\AssertableInertia as Assert;

test('public layout exposes direction for keyboard/screen-reader navigation', function (string $locale, string $direction) {
    // lang/dir are rendered server-side by resources/views/app.blade.php, so
    // they're still directly observable here. The skip-link/dialog/aria
    // markup this test used to check is rendered by PublicLayout/MobileDrawer
    // (React) instead of Blade now; SSR isn't running during `php artisan
    // test`, so that markup never reaches the raw response body here. Proving
    // it renders correctly now belongs to a browser-level (JS/e2e) test.
    $this->withSession(['locale' => $locale])->get('/')
        ->assertOk()
        ->assertSee('lang="'.$locale.'"', false)
        ->assertSee('dir="'.$direction.'"', false)
        ->assertInertia(fn (Assert $page) => $page->where('locale', $locale)->where('direction', $direction));
})->with([
    'English' => ['en', 'ltr'],
    'Arabic' => ['ar', 'rtl'],
]);

test('homepage renders successfully for the mobile navigation to mount on', function () {
    // The hidden/responsive nav classes and aria-controls/aria-expanded state
    // this test used to check are React-rendered (PublicHeader/MobileDrawer)
    // and, like above, aren't observable without SSR or a browser test.
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Home'));
});

test('multipart admin forms preserve array field semantics', function () {
    $source = file_get_contents(resource_path('js/hooks/use-admin-form.js'));

    expect($source)
        ->toContain('Array.isArray(value)')
        ->toContain('formData.append(`${key}[]`, item)');
});

test('vendor map is a local focusable svg rather than a third party tile map', function () {
    $source = file_get_contents(resource_path('js/Components/maps/DashboardVendorMap.jsx'));

    expect($source)
        ->toContain('<svg viewBox=')
        ->toContain('/images/syria-governorates-map.jpg')
        ->toContain('tabIndex="0"')
        ->not->toContain('TILE_URL')
        ->not->toContain('loadLeaflet');
});

test('map ownership is limited to the two dashboards and admin drilldown initializes its filter', function () {
    $adminDashboard = file_get_contents(resource_path('js/Pages/Admin/Dashboard.jsx'));
    $syndicateDashboard = file_get_contents(resource_path('js/Pages/Syndicate/Dashboard.jsx'));
    $adminVendors = file_get_contents(resource_path('js/Pages/Admin/Vendors/Index.jsx'));

    expect($adminDashboard)->toContain('<DashboardVendorMap')
        ->and($syndicateDashboard)->toContain('{isOverview && (', '<DashboardVendorMap endpoint="/api/syndicate/vendors/map"')
        ->and($adminVendors)->not->toContain('DashboardVendorMap')
        ->not->toContain('ViewSwitch')
        ->toContain("initialQuery.get('governorate')")
        ->toContain('governorate: governorate');
});

test('locale formatters use one explicit western-digit locale for Arabic screens', function () {
    $source = file_get_contents(resource_path('js/lib/date-time.js'));

    expect($source)
        ->toContain('ar-SY-u-nu-latn')
        ->toContain('Intl.DateTimeFormat')
        ->toContain('Intl.NumberFormat');
});

test('vendor map keeps its raster and SVG in one explicit physical coordinate system', function () {
    $source = file_get_contents(resource_path('js/Components/maps/DashboardVendorMap.jsx'));

    expect($source)
        ->toContain("MAP_VIEWBOX = '0 0 512 468'")
        ->toContain("MAP_ASSET = '/images/syria-governorates-map.jpg'")
        ->toContain('viewBox={MAP_VIEWBOX}')
        ->toContain('preserveAspectRatio="none"')
        ->toContain('dir="ltr"')
        ->toContain('object-contain')
        ->toContain('className="absolute inset-0 size-full"');

    expect(getimagesize(public_path('images/syria-governorates-map.jpg')))
        ->toMatchArray([0 => 512, 1 => 468]);
});

test('homepage partner presentation is logos only and banners preserve their image ratio', function () {
    $source = file_get_contents(resource_path('js/Pages/Home.jsx'));
    $partners = substr($source, strpos($source, '<section className="storefront-section border-t'));

    expect($partners)
        ->toContain('<PartnerLogoMarquee')
        ->not->toContain('home.partners_kicker')
        ->not->toContain('home.partners_subtitle')
        ->and($source)->toContain('aspect-[16/7]')
        ->and($source)->toContain('object-contain');
});

test('public storefront keeps localized footer copy and semantic category fallbacks', function () {
    app()->setLocale('ar');
    $arabicTagline = __('home.tagline');

    app()->setLocale('en');
    $englishTagline = __('home.tagline');

    expect($arabicTagline)
        ->not->toBe($englishTagline)
        ->toContain('سوق')
        ->and($englishTagline)->toContain('marketplace');

    foreach (['irrigation', 'animal-care', 'disinfectants', 'veterinary-services'] as $asset) {
        expect(public_path("images/category-fallbacks/{$asset}.svg"))->toBeFile();
    }
});
