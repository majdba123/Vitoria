<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Product\ProductComparisonException;
use App\Services\Product\ProductComparisonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public product comparison (spec §29). Stateless — see
 * ProductComparisonService's class doc for why no table backs this.
 */
class ProductComparisonController extends Controller
{
    public function __construct(
        private readonly ProductComparisonService $comparisonService,
    ) {}

    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'string'],
        ]);

        $productIds = array_filter(array_map('trim', explode(',', $validated['ids'])), fn ($id) => $id !== '');

        try {
            $result = $this->comparisonService->compare(array_map('intval', $productIds));
        } catch (ProductComparisonException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.products_compared'),
            'data' => $result,
        ]);
    }
}
