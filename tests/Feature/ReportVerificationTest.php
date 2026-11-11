<?php

use App\Models\Category;
use App\Models\Syndicate;
use App\Services\Syndicate\SyndicateReportService;
use App\Support\DomainScope;
use Illuminate\Support\Facades\View;

/**
 * Renders a report through the real service and returns the data handed to
 * the PDF template (verify URL, organisation, rendered HTML).
 *
 * @return array{verifyUrl: string, html: string, reportReference: string}
 */
function renderReportCapturingTemplate(Syndicate|DomainScope $scope, string $locale): array
{
    $captured = [];
    View::composer('reports.syndicate-general', function ($view) use (&$captured): void {
        $captured = $view->getData();
    });

    app(SyndicateReportService::class)->render($scope, ['key' => '30_days', 'from' => now()->subDays(29)->startOfDay()->toImmutable(), 'to' => now()->endOfDay()->toImmutable()], $locale);

    return [
        'verifyUrl' => $captured['verifyUrl'],
        'reportReference' => $captured['reportReference'],
        'html' => view('reports.syndicate-general', $captured)->render(),
    ];
}

/** Path + query of an absolute URL, so the test client requests it as-is. */
function relativeUrl(string $url): string
{
    $parts = parse_url($url);

    return $parts['path'].'?'.$parts['query'];
}

it('verifies an authentic Ministry report and shows the signed headline figures', function () {
    $report = renderReportCapturingTemplate(DomainScope::forSector(DomainScope::COMBINED), 'ar');

    $this->get(relativeUrl($report['verifyUrl']))
        ->assertOk()
        ->assertSee('تقرير موثّق')
        ->assertSee('وزارة الزراعة')
        ->assertSee($report['reportReference'])
        ->assertSee('0.00 ل.س')
        ->assertDontSee('تعذّر التحقق');
});

it('verifies an English Ministry report with the English ministry name', function () {
    $report = renderReportCapturingTemplate(DomainScope::forSector(Category::TYPE_AGRICULTURE), 'en');

    $this->get(relativeUrl($report['verifyUrl']))
        ->assertOk()
        ->assertSee('Verified report')
        ->assertSee('Ministry of Agriculture')
        ->assertDontSee('Veterinary Affairs');
});

it('rejects a verification link whose figures or scope were tampered with', function (string $parameter, string $forgedValue) {
    $report = renderReportCapturingTemplate(DomainScope::forSector(DomainScope::COMBINED), 'ar');
    $url = relativeUrl($report['verifyUrl']);
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    $query[$parameter] = $forgedValue;

    $this->get('/reports/verify?'.http_build_query($query))
        ->assertForbidden()
        ->assertSee('تعذّر التحقق')
        ->assertDontSee('تقرير موثّق')
        ->assertDontSee($report['reportReference']);
})->with([
    'inflated sales' => ['sales', '999999999.00'],
    'edited order count' => ['orders', '9999'],
    'widened scope' => ['scope', Category::TYPE_VETERINARY],
    'shifted period' => ['from', '2020-01-01'],
    'forged signature' => ['signature', str_repeat('a', 64)],
]);

it('rejects a verification link without a signature', function () {
    $this->get('/reports/verify?scope=combined&from=2026-01-01&to=2026-01-31&ref=ABCDEF1234&sales=100.00')
        ->assertForbidden()
        ->assertSee('تعذّر التحقق')
        ->assertDontSee('ABCDEF1234');
});

it('rejects a validly signed syndicate link once the syndicate no longer exists', function () {
    $syndicate = Syndicate::factory()->create();
    $report = renderReportCapturingTemplate($syndicate, 'en');
    $syndicate->delete();

    $this->get(relativeUrl($report['verifyUrl']))->assertForbidden()->assertSee('Not verified');
});

it('discloses no vendor, product or city rows on the verification page', function () {
    $report = renderReportCapturingTemplate(DomainScope::forSector(DomainScope::COMBINED), 'en');

    $html = $this->get(relativeUrl($report['verifyUrl']))->assertOk()->getContent();

    expect($html)->not->toContain('Top products')
        ->and($html)->not->toContain('Vendor performance')
        ->and($html)->toContain('noindex');
});

it('prints the client-approved Ministry wording on the PDF report', function () {
    $arabic = renderReportCapturingTemplate(DomainScope::forSector(DomainScope::COMBINED), 'ar')['html'];
    $english = renderReportCapturingTemplate(DomainScope::forSector(DomainScope::COMBINED), 'en')['html'];

    expect($arabic)->toContain('تقرير الوزارة')
        ->and($arabic)->toContain('وزارة الزراعة —')
        ->and($arabic)->not->toContain('قطاعات')
        ->and($arabic)->not->toContain('والشؤون البيطرية')
        ->and($arabic)->toContain('<barcode code="')
        ->and($english)->toContain('Ministry report')
        ->and($english)->toContain('Ministry of Agriculture —')
        ->and($english)->not->toContain('Veterinary Affairs')
        ->and($english)->not->toContain('sector report');
});

it('centres report table figures under their own column headers', function () {
    $template = file_get_contents(resource_path('views/reports/syndicate-general.blade.php'));

    expect($template)->toContain('.table th.num { text-align:center; }')
        ->and($template)->toMatch('/\.number \{[^}]*text-align:center/')
        ->and($template)->toMatch('/\.money \{[^}]*text-align:center/');
});

it('no longer labels the Ministry workspace as read-only', function () {
    $arabic = require lang_path('ar/ministry.php');
    $english = require lang_path('en/ministry.php');
    $header = file_get_contents(resource_path('js/Components/ministry/MinistryHeader.jsx'));

    expect(json_encode($arabic, JSON_UNESCAPED_UNICODE))->not->toContain('للاطلاع فقط')
        ->and(json_encode($english))->not->toContain('Read-only')
        ->and($header)->not->toContain('read_only');
});

it('keeps shipment field values aligned under their titles', function () {
    $show = file_get_contents(resource_path('js/Pages/Shipments/Show.jsx'));

    expect($show)->toContain('<dd className="mt-1 break-words text-start text-sm text-foreground tabular-nums"><bdi>')
        ->and($show)->not->toContain('text-foreground" dir="auto">{children');
});

it('uses the English date picker for every report date range', function (string $file) {
    $source = file_get_contents(resource_path($file));

    expect($source)->toContain('<EnglishDatePicker')
        ->and($source)->not->toContain('<Input type="date" lang="en"')
        ->and($source)->not->toContain('toISOString().slice(0, 10), date_to');
})->with([
    'syndicate & ministry report dialogs' => 'js/Pages/Syndicate/Dashboard.jsx',
    'vendor 360 report filters' => 'js/Components/vendor360/Vendor360.jsx',
]);

it('formats the English date picker with en-US calendar labels independent of page locale', function () {
    $picker = file_get_contents(resource_path('js/Components/shared/EnglishDatePicker.jsx'));

    expect($picker)->toContain("new Intl.DateTimeFormat('en-US'")
        ->and($picker)->toContain("timeZone: 'UTC'")
        ->and($picker)->toContain('lang="en"')
        ->and($picker)->toContain('dir="ltr"')
        ->and($picker)->toContain('labels.previous_month')
        ->and($picker)->not->toContain('toISOString');
});
