<?php

namespace App\Http\Controllers\Api\Syndicate;

use App\Http\Controllers\Controller;
use App\Models\Syndicate;
use App\Services\Syndicate\SyndicateDashboardService;
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
        $categories = $this->dashboardService->categories($this->syndicate($request), 15);

        return response()->json([
            'message' => __('Syndicate categories retrieved successfully.'),
            'data' => $categories->items(),
            'meta' => $this->meta($categories),
        ]);
    }

    public function vendors(Request $request): JsonResponse
    {
        $vendors = $this->dashboardService->vendors($this->syndicate($request), 15);

        return response()->json([
            'message' => __('Syndicate merchants retrieved successfully.'),
            'data' => $vendors->items(),
            'meta' => $this->meta($vendors),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $products = $this->dashboardService->products($this->syndicate($request), 15);

        return response()->json([
            'message' => __('Syndicate products retrieved successfully.'),
            'data' => $products->items(),
            'meta' => $this->meta($products),
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $orders = $this->dashboardService->orders($this->syndicate($request), 15);

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
