<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductReviewRequest;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductReviewController extends Controller
{
    /**
     * List reviews for a product (paginated). Public.
     */
    public function index(Request $request, Product $product): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 10), 30);
        $reviews = $product->reviews()
            ->with('user:id,name')
            ->latest()
            ->paginate($perPage);

        $items = $reviews->getCollection()->map(function (ProductReview $review) {
            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'body' => $review->body,
                'created_at' => $review->created_at?->toIso8601String(),
                'user' => $review->user ? [
                    'id' => $review->user->id,
                    'name' => $review->user->name,
                ] : null,
            ];
        });

        return response()->json([
            'message' => __('Reviews retrieved successfully.'),
            'data' => $items,
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * List reviews for a product (paginated). Admin: any product.
     */
    public function indexForAdmin(Request $request, Product $product): JsonResponse
    {
        return $this->paginatedReviewsResponse($request, $product);
    }

    /**
     * List reviews for a product (paginated). Vendor: own products only.
     */
    public function indexForVendor(Request $request, Product $product): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (! $vendor || $product->vendor_id !== $vendor->id) {
            abort(403, __('You can only view reviews for your own products.'));
        }

        return $this->paginatedReviewsResponse($request, $product);
    }

    /**
     * @return JsonResponse
     */
    private function paginatedReviewsResponse(Request $request, Product $product)
    {
        $perPage = min((int) $request->get('per_page', 15), 30);
        $reviews = $product->reviews()
            ->with('user:id,name')
            ->latest()
            ->paginate($perPage);

        $items = $reviews->getCollection()->map(function (ProductReview $review) {
            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'body' => $review->body,
                'created_at' => $review->created_at?->toIso8601String(),
                'user' => $review->user ? [
                    'id' => $review->user->id,
                    'name' => $review->user->name,
                ] : null,
            ];
        });

        return response()->json([
            'message' => __('Reviews retrieved successfully.'),
            'data' => $items,
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Store or update the authenticated user's review for the product.
     */
    public function store(StoreProductReviewRequest $request, Product $product): JsonResponse
    {
        if (! $product->is_active || $product->status !== Product::STATUS_APPROVED) {
            abort(404, __('Product not found.'));
        }

        $user = $request->user();

        try {
            $review = $product->reviews()->create([
                'user_id' => $user->id,
                'rating' => $request->input('rating'),
                'body' => $request->input('body'),
            ]);
        } catch (QueryException $e) {
            $sqlstate = $e->errorInfo[0] ?? null;
            if ($sqlstate === '23000' || str_contains((string) $e->getMessage(), 'Duplicate')) {
                return response()->json([
                    'message' => __('Database allows only one review per user per product. Run: php artisan migrate'),
                ], 422);
            }
            throw $e;
        }

        Cache::tags(['products'])->flush();

        $product->loadCount('reviews')->loadAvg('reviews', 'rating');

        return response()->json([
            'message' => __('Review saved successfully.'),
            'data' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'body' => $review->body,
                'created_at' => $review->created_at?->toIso8601String(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
            ],
            'product' => [
                'average_rating' => round((float) $product->reviews_avg_rating, 2),
                'review_count' => $product->reviews_count,
            ],
        ], 201);
    }

    /**
     * Delete a review. Allowed for admin, product vendor, or review author.
     */
    public function destroy(Product $product, ProductReview $review): JsonResponse
    {
        if ($review->product_id !== $product->id) {
            abort(404, __('Review not found.'));
        }

        $review->loadMissing('product');
        $this->authorize('delete', $review);

        $review->delete();
        Cache::tags(['products'])->flush();

        return response()->json(['message' => __('Review deleted successfully.')], 204);
    }
}
