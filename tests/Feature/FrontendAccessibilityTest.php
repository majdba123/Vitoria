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
        ->toContain('<svg viewBox={SYRIA_VIEWBOX}')
        ->toContain('tabIndex="0"')
        ->not->toContain('TILE_URL')
        ->not->toContain('loadLeaflet');
});

test('map ownership is limited to the two dashboards and admin drilldown initializes its filter', function () {
    $adminDashboard = file_get_contents(resource_path('js/Pages/Admin/Dashboard.jsx'));
    $syndicateDashboard = file_get_contents(resource_path('js/Pages/Syndicate/Dashboard.jsx'));
    $adminVendors = file_get_contents(resource_path('js/Pages/Admin/Vendors/Index.jsx'));

    expect($adminDashboard)->toContain('<DashboardVendorMap')
        ->and($syndicateDashboard)->toContain('{isOverview && (', '<DashboardVendorMap endpoint={ws.isMinistry ? `${ws.apiBase}/vendors/map?sector=${ws.sector}` : `${ws.apiBase}/vendors/map`}')
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

test('vendor map covers all 14 governorates as individual svg paths', function () {
    // JS export syntax isn't require-able from PHP; parse the file instead.
    $source = file_get_contents(resource_path('js/Components/maps/syria-governorates.js'));
    preg_match('/SYRIA_GOVERNORATE_PATHS = (\{.*?\});\n/s', $source, $matches);
    expect($matches)->toHaveCount(2);

    $keys = array_keys(json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR));
    expect($keys)->toHaveCount(14)
        ->toEqualCanonicalizing(collect(App\Support\SyriaGovernorates::ALL)->pluck('key')->all());

    expect(file_get_contents(resource_path('js/Components/maps/DashboardVendorMap.jsx')))
        ->toContain('SYRIA_GOVERNORATE_PATHS')
        ->toContain('SYRIA_VIEWBOX')
        ->toContain('data-key={key}')
        ->not->toContain('MERGED_GROUPS')
        ->not->toContain('.jpg');
});

test('Damascus remains a separate polygon inside the Rif Dimashq boundary', function () {
    $source = file_get_contents(resource_path('js/Components/maps/syria-governorates.js'));
    preg_match('/SYRIA_GOVERNORATE_PATHS = (\{.*?\});\n/s', $source, $matches);
    $paths = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    $rifRings = array_values(array_filter(explode('M', $paths['rif_dimashq'])));

    $signedArea = function (string $path): float {
        preg_match_all('/(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $path, $matches, PREG_SET_ORDER);
        $points = array_map(fn (array $match): array => [(float) $match[1], (float) $match[2]], $matches);
        $area = 0.0;

        foreach ($points as $index => [$x, $y]) {
            [$nextX, $nextY] = $points[($index + 1) % count($points)];
            $area += $x * $nextY - $nextX * $y;
        }

        return $area / 2;
    };

    expect($rifRings)->toHaveCount(2)
        ->and($signedArea($paths['damascus']))->toBeLessThan(0)
        ->and($signedArea($rifRings[1]))->toBeGreaterThan(0)
        ->and(abs($signedArea($paths['damascus']) + $signedArea($rifRings[1])))->toBeLessThan(0.1);

    $contains = function (string $path, float $x, float $y): bool {
        $inside = false;

        foreach (array_filter(explode('M', $path)) as $ring) {
            preg_match_all('/(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $ring, $matches, PREG_SET_ORDER);
            $points = array_map(fn (array $match): array => [(float) $match[1], (float) $match[2]], $matches);
            $ringInside = false;

            foreach ($points as $index => [$currentX, $currentY]) {
                [$nextX, $nextY] = $points[($index + 1) % count($points)];
                if (($currentY > $y) !== ($nextY > $y) && $x < ($nextX - $currentX) * ($y - $currentY) / ($nextY - $currentY) + $currentX) {
                    $ringInside = ! $ringInside;
                }
            }

            $inside = $inside !== $ringInside;
        }

        return $inside;
    };

    expect($contains($paths['damascus'], 60, 389))->toBeTrue()
        ->and($contains($paths['rif_dimashq'], 60, 389))->toBeFalse()
        ->and($contains($paths['damascus'], 70, 389))->toBeFalse()
        ->and($contains($paths['rif_dimashq'], 70, 389))->toBeTrue();
});

test('vendor map shows one selection panel below the svg that hover can never drive', function () {
    $source = file_get_contents(resource_path('js/Components/maps/DashboardVendorMap.jsx'));

    // Hover and selection are separate state; the panel is derived from the selection alone.
    expect($source)
        ->toContain('const [hoveredKey, setHoveredKey] = useState(null);')
        ->toContain('const [selectedKey, setSelectedKey] = useState(null);')
        ->toContain('const selected = selectedKey ? regionsByKey.get(selectedKey) : null;')
        ->toContain('onClick: () => { setSelectedKey(key); clearHover(); }')
        ->toContain('viewBox={SYRIA_VIEWBOX}')
        ->toContain("GOVERNORATES.filter(({ key }) => key !== 'damascus').map(({ key, path }) => renderRegion(key, path))")
        ->toContain("renderRegion('damascus', SYRIA_GOVERNORATE_PATHS.damascus)")
        ->toContain('data-label="damascus" role="button" tabIndex="0"')
        ->not->toContain('zoomed')
        ->toContain('role="tooltip"')
        ->toContain('data-map-tooltip')
        ->not->toContain('tooltipPoint')
        ->not->toContain('data-map-inset')
        ->not->toContain('<circle cx={DAMASCUS.anchor.x}')
        ->not->toContain('hoveredKey || selectedKey')
        ->not->toContain('selectedKey || hoveredKey');

    // Exactly one panel element, rendered after the svg rather than floating over it.
    expect(substr_count($source, 'data-map-panel='))->toBe(1)
        ->and(strpos($source, 'data-map-panel='))->toBeGreaterThan(strpos($source, '</svg>'))
        ->and(strpos($source, 'data-map-tooltip'))->toBeLessThan(strpos($source, '<svg viewBox='))
        ->and($source)->not->toContain('absolute inset-x-2 bottom-2');

    // Governorates are keyboard buttons exposing their selected state; clicking never navigates.
    expect($source)
        ->toContain('role="button" aria-pressed={isSelected}')
        ->toContain("['Enter', ' '].includes(event.key)")
        ->not->toContain('window.location.assign');

    // Counts come from the API payload only; no black canvas.
    expect($source)
        ->toContain('window.axios.get(endpoint')
        ->not->toContain('governorateData')
        ->not->toContain('bg-black')
        ->not->toContain('#000');
});

test('homepage partner presentation is logos only and banners preserve their image ratio', function () {
    $source = file_get_contents(resource_path('js/Pages/Home.jsx'));
    $styles = file_get_contents(resource_path('css/app.css'));
    $partners = substr($source, strpos($source, '<section className="storefront-section home-partners-section'));

    expect($partners)
        ->toContain('<PartnerLogoMarquee')
        ->not->toContain('home.partners_kicker')
        ->not->toContain('home.partners_subtitle')
        ->and($source)->toContain('[false, true].map')
        ->toContain('aria-hidden={isDuplicate || undefined}')
        ->and($source)->toContain('aspect-[16/7]')
        ->and($source)->toContain('object-contain')
        ->and($styles)->toContain('width: 200%;')
        ->toContain('.logo-marquee-group')
        ->toContain('@media (prefers-reduced-motion: reduce)');
});

test('homepage product type cards keep their imagery in a responsive circular layout', function () {
    $homepage = file_get_contents(resource_path('js/Pages/Home.jsx'));
    $source = file_get_contents(resource_path('css/app.css'));

    expect($source)
        ->toContain('grid-cols-2 place-items-center')
        ->toContain('aspect-square w-full max-w-72')
        ->toContain('rounded-full')
        ->toContain("url('../images/home-type-cards/agriculture.webp')")
        ->toContain("url('../images/home-type-cards/veterinary.webp')")
        ->toContain('.homepage-section')
        ->toContain('.home-categories-section')
        ->toContain('.home-partners-section')
        ->and($homepage)->toContain('home-categories-section')
        ->toContain('aria-label={home.type_selector_title}')
        ->not->toContain('<h2 id="home-type-heading"')
        ->toContain('className="logo-marquee"')
        ->not->toContain('className="logo-marquee mt-10"');
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

test('dashboard charts mirror their axes for right-to-left pages', function () {
    $hook = file_get_contents(resource_path('js/hooks/use-i18n.js'));
    expect($hook)->toContain('export function useIsRtl()')->toContain("props.direction === 'rtl'");

    foreach (['HorizontalRankingChart', 'CategoryCoverage'] as $chart) {
        expect(file_get_contents(resource_path("js/Components/shared/dashboard/{$chart}.jsx")))
            ->toContain('const isRtl = useIsRtl();')
            ->toContain('reversed={isRtl}')
            ->toContain("orientation={isRtl ? 'right' : 'left'}")
            ->toContain("textAnchor={isRtl ? 'start' : 'end'}")
            ->not->toContain('orientation="left"');
    }

    foreach ([
        'js/Components/shared/dashboard/GrowthChart.jsx',
        'js/Components/shared/dashboard/OrderTrendChart.jsx',
        'js/Components/vendor360/Vendor360.jsx',
        'js/Pages/Shipments/Index.jsx',
        'js/Pages/Vendor/Dashboard.jsx',
    ] as $path) {
        expect(file_get_contents(resource_path($path)))->toContain('const isRtl = useIsRtl();')->toContain('reversed={isRtl}');
    }
});

test('admin growth range labels are translated in both locales', function () {
    expect(file_get_contents(resource_path('js/Pages/Admin/Dashboard.jsx')))
        ->toContain('{admin[`chart_period_${months}`]}')
        ->not->toContain('{months}mo');

    foreach (['en', 'ar'] as $locale) {
        app()->setLocale($locale);
        foreach ([3, 6, 12] as $months) {
            expect(__("admin.chart_period_{$months}"))->not->toBe("admin.chart_period_{$months}")->toContain((string) $months);
        }
    }
});

test('dashboard status links and ministry header avoid accent side borders and narrow-width overflow', function () {
    $employee = file_get_contents(resource_path('js/Pages/Employee/Dashboard.jsx'));
    $ministryHeader = file_get_contents(resource_path('js/Components/ministry/MinistryHeader.jsx'));

    expect($employee)
        ->not->toContain('border-s-2')
        ->not->toContain('employee.open_tab')
        ->toContain('<nav aria-label={employee.status_summary}')
        ->toContain('rtl:rotate-180')
        ->and($ministryHeader)
        ->toContain('<SectorSwitcher className="hidden xl:flex" />')
        ->toContain('xl:hidden');
});
