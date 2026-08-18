<?php

namespace App\Http\Controllers\Api\Syndicate;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendorAnalyticsRequest;
use App\Models\Vendor;
use App\Services\Syndicate\SyndicateDashboardService;
use App\Services\Vendor\VendorAnalyticsService;
use Illuminate\Http\JsonResponse;

class VendorAnalyticsController extends Controller
{
    public function __construct(private readonly VendorAnalyticsService $analytics, private readonly SyndicateDashboardService $dashboard) {}

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

    private function domain(VendorAnalyticsRequest $request, Vendor $vendor): string
    {
        $syndicate = $request->user()->syndicate;
        abort_unless($syndicate && $this->dashboard->vendorQuery($syndicate->type)->whereKey($vendor->id)->exists(), 404);

        return $syndicate->type;
    }

    private function paginated($paginator): JsonResponse
    {
        return response()->json(['data' => $paginator->items(), 'meta' => [
            'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(), 'total' => $paginator->total(),
        ]]);
    }
}
