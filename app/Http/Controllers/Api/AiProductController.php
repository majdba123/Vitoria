<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExternalProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->listByCategoryType($request);
    }

    public function agricultureIndex(Request $request): JsonResponse
    {
        return $this->listByCategoryType($request, Category::TYPE_AGRICULTURE);
    }

    public function veterinaryIndex(Request $request): JsonResponse
    {
        return $this->listByCategoryType($request, Category::TYPE_VETERINARY);
    }

    public function show(Product $product): JsonResponse
    {
        if (! $product->is_active || $product->status !== Product::STATUS_APPROVED) {
            abort(404, __('Product not found.'));
        }

        if (! $product->vendor || ! $product->vendor->is_active) {
            abort(404, __('Product not found.'));
        }

        $product->load([
            'vendor:id,store_name,user_id,logo,is_active,status',
            'category:id,name,type',
            'subcategory:id,category_id,name_ar,name_en',
            'photos',
            'sharedDetail.agriculturalDetail',
            'sharedDetail.veterinaryDetail',
        ]);

        return response()->json([
            'message' => __('AI product retrieved successfully.'),
            'data' => new ExternalProductResource($product),
        ]);
    }

    protected function listByCategoryType(Request $request, ?string $forcedCategoryType = null): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $filters = $request->only([
            'vendor_id',
            'category_id',
            'subcategory_id',
            'category_type',
            'product_type',
            'has_discount',
            'in_stock',
            'search',
        ]);

        if ($forcedCategoryType !== null) {
            $filters['category_type'] = $forcedCategoryType;
        }

        $products = $this->productService->list(null, $perPage, $filters, onlyVisible: true);

        return response()->json([
            'message' => __('AI products retrieved successfully.'),
            'data' => ExternalProductResource::collection($products->getCollection()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
            'filters' => array_filter($filters, fn (mixed $value): bool => $value !== null && $value !== ''),
        ]);
    }
}
