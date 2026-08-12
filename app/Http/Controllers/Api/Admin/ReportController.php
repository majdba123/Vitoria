<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function sales(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'group_by' => ['nullable', 'in:day,month'],
        ]);

        [$from, $to] = $this->resolveRange($validated);

        return response()->json([
            'message' => __('Sales report retrieved successfully.'),
            'data' => $this->reportService->sales($from, $to, $validated['group_by'] ?? 'day'),
        ]);
    }

    public function vendors(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        [$from, $to] = $this->resolveRange($validated);

        return response()->json([
            'message' => __('Vendor performance report retrieved successfully.'),
            'data' => $this->reportService->vendorPerformance($from, $to),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        [$from, $to] = $this->resolveRange($validated);

        return response()->json([
            'message' => __('Product performance report retrieved successfully.'),
            'data' => $this->reportService->productPerformance($from, $to, (int) ($validated['limit'] ?? 20)),
        ]);
    }

    /**
     * @param  array{from?: string, to?: string}  $validated
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(array $validated): array
    {
        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : now()->endOfDay();
        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : $to->copy()->subDays(29)->startOfDay();

        return [$from, $to];
    }
}
