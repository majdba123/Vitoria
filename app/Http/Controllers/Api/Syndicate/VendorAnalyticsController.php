<?php

namespace App\Http\Controllers\Api\Syndicate;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyndicateVendorReportRequest;
use App\Http\Requests\VendorAnalyticsRequest;
use App\Models\Vendor;
use App\Services\AuditLogService;
use App\Services\Syndicate\SyndicateDashboardService;
use App\Services\Vendor\SyndicateVendorPdfService;
use App\Services\Vendor\VendorAnalyticsService;
use App\Support\DomainScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorAnalyticsController extends Controller
{
    public function __construct(private readonly VendorAnalyticsService $analytics, private readonly SyndicateDashboardService $dashboard, private readonly SyndicateVendorPdfService $pdf) {}

    public function overview(VendorAnalyticsRequest $request, Vendor $vendor): JsonResponse
    {
        return response()->json(['data' => $this->analytics->overview($vendor, $request->period(), $this->domain($request, $vendor))]);
    }

    public function products(VendorAnalyticsRequest $request, Vendor $vendor): JsonResponse
    {
        return $this->paginated($this->analytics->products($vendor, $request->period(), $request->validated(), $request->perPage(), $this->domain($request, $vendor)));
    }

    public function orders(VendorAnalyticsRequest $request, Vendor $vendor): JsonResponse
    {
        return $this->paginated($this->analytics->orders($vendor, $request->period(), $request->validated(), $request->perPage(), $this->domain($request, $vendor)));
    }

    public function returns(VendorAnalyticsRequest $request, Vendor $vendor): JsonResponse
    {
        return $this->paginated($this->analytics->returns($vendor, $request->period(), $request->perPage(), $this->domain($request, $vendor)));
    }

    public function report(SyndicateVendorReportRequest $request, Vendor $vendor): Response
    {
        $domain = $this->domain($request, $vendor);
        $report = $this->pdf->render($vendor, $request->user()->syndicate, $request->period(), $request->validated('locale'));
        app(AuditLogService::class)->record($request->user(), 'export.vendor_analytics_report', 'Vendor', $vendor->id, null, [
            'range' => $request->period()['key'],
            'locale' => $request->validated('locale'),
            'domain' => $domain,
            'format' => 'pdf',
        ]);

        return response($report['bytes'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$report['filename'].'"',
            'Content-Length' => (string) strlen($report['bytes']),
            'X-Content-Type-Options' => 'nosniff',
            'X-Vetora-Report-Domain' => implode(',', (array) $domain),
        ]);
    }

    /**
     * The domain(s) the vendor's analytics are restricted to. Never null: a
     * null domain means the unscoped admin view, including the full ledger.
     *
     * @return string|list<string>
     */
    private function domain(VendorAnalyticsRequest $request, Vendor $vendor): string|array
    {
        $scope = $this->scope($request);
        abort_unless($this->dashboard->vendorQuery($scope->types)->whereKey($vendor->id)->exists(), 404);

        return $scope->isCombined() ? $scope->types : $scope->types[0];
    }

    protected function scope(Request $request): DomainScope
    {
        $syndicate = $request->user()->syndicate;
        abort_unless($syndicate !== null, 404);

        return DomainScope::forSyndicate($syndicate);
    }

    private function paginated($paginator): JsonResponse
    {
        return response()->json(['data' => $paginator->items(), 'meta' => [
            'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(), 'total' => $paginator->total(),
        ]]);
    }
}
