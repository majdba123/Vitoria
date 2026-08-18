<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendorAnalyticsRequest;
use App\Models\Vendor;
use App\Services\Import\CsvFile;
use App\Services\Vendor\VendorAnalyticsService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorAnalyticsController extends Controller
{
    public function __construct(private readonly VendorAnalyticsService $analytics) {}

    public function overview(VendorAnalyticsRequest $request, Vendor $vendor): JsonResponse
    {
        return response()->json(['data' => $this->analytics->overview($vendor, $request->period())]);
    }

    public function products(VendorAnalyticsRequest $request, Vendor $vendor): JsonResponse
    {
        return $this->paginated($this->analytics->products($vendor, $request->period(), $request->validated(), $request->perPage()));
    }

    public function orders(VendorAnalyticsRequest $request, Vendor $vendor): JsonResponse
    {
        return $this->paginated($this->analytics->orders($vendor, $request->period(), $request->validated(), $request->perPage()));
    }

    public function returns(VendorAnalyticsRequest $request, Vendor $vendor): JsonResponse
    {
        return $this->paginated($this->analytics->returns($vendor, $request->period(), $request->perPage()));
    }

    public function activity(VendorAnalyticsRequest $request, Vendor $vendor): JsonResponse
    {
        return $this->paginated($this->analytics->activity($vendor, $request->perPage()));
    }

    public function exportSummary(VendorAnalyticsRequest $request, Vendor $vendor): StreamedResponse
    {
        $overview = $this->analytics->overview($vendor, $request->period());
        $rows = collect($overview['kpis'])->map(fn ($value, string $metric): array => [$metric, $value])->values()->all();

        return CsvFile::download('vendor_'.$vendor->id.'_sales_summary_'.now()->format('Y-m-d').'.csv', ['Metric', 'Value'], $rows);
    }

    private function paginated($paginator): JsonResponse
    {
        return response()->json(['data' => $paginator->items(), 'meta' => [
            'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(), 'total' => $paginator->total(),
        ]]);
    }
}
