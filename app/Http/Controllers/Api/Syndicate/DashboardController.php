<?php

namespace App\Http\Controllers\Api\Syndicate;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendorMapRequest;
use App\Models\Syndicate;
use App\Services\Syndicate\SyndicateDashboardService;
use App\Services\Vendor\VendorMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(public SyndicateDashboardService $dashboardService) {}

    public function overview(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('Syndicate overview retrieved successfully.'),
            'data' => $this->dashboardService->overview($this->syndicate($request)),
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        $categories = $this->dashboardService->categories($this->syndicate($request), $this->perPage($request));

        return response()->json([
            'message' => __('Syndicate categories retrieved successfully.'),
            'data' => $categories->items(),
            'meta' => $this->meta($categories),
        ]);
    }

    public function vendors(Request $request): JsonResponse
    {
        $vendors = $this->dashboardService->vendors($this->syndicate($request), $this->perPage($request));

        return response()->json([
            'message' => __('Syndicate merchants retrieved successfully.'),
            'data' => $vendors->items(),
            'meta' => $this->meta($vendors),
        ]);
    }

    /**
     * The Table/Map payload for the Vendors section, restricted to the
     * canonical syndicate vendor scope and stripped of every admin-only URL.
     */
    public function vendorsMap(VendorMapRequest $request, VendorMapService $mapService): JsonResponse
    {
        $type = $this->syndicate($request)->type;

        return response()->json([
            'message' => __('Vendor map retrieved successfully.'),
            'data' => $mapService->payload(
                fn () => $this->dashboardService->vendorQuery($type),
                $request->filters(),
                withAdminActions: false,
            ),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $products = $this->dashboardService->products($this->syndicate($request), $this->perPage($request));

        return response()->json([
            'message' => __('Syndicate products retrieved successfully.'),
            'data' => $products->items(),
            'meta' => $this->meta($products),
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $orders = $this->dashboardService->orders($this->syndicate($request), $this->perPage($request));

        return response()->json([
            'message' => __('Syndicate orders retrieved successfully.'),
            'data' => $orders->items(),
            'meta' => $this->meta($orders),
        ]);
    }

    public function podcasts(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('Syndicate podcasts retrieved successfully.'),
            'data' => $this->dashboardService->podcasts($this->syndicate($request)),
        ]);
    }

    public function reports(Request $request): JsonResponse
    {
        return response()->json([
            'message' => __('Syndicate reports retrieved successfully.'),
            'data' => $this->dashboardService->reports($this->syndicate($request)),
        ]);
    }

    protected function syndicate(Request $request): Syndicate
    {
        return $request->user()->syndicate;
    }

    protected function perPage(Request $request): int
    {
        return min(max((int) $request->input('per_page', 15), 1), 50);
    }

    /**
     * @return array<string, int>
     */
    protected function meta(\Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
