<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductReviewViewController extends Controller
{
    /**
     * Display paginated reviews for a product. Data loaded server-side so the page always works.
     */
    public function __invoke(Request $request, string $id): Response
    {
        $product = Product::find($id);
        if (! $product) {
            abort(404, __('Product not found.'));
        }

        $perPage = min((int) $request->get('per_page', 15), 30);
        $reviews = $product->reviews()
            ->with('user:id,name')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Products/Reviews', [
            'product' => ['id' => $product->id, 'name' => $product->name],
            'reviews' => $reviews,
        ]);
    }

    public function destroy(Request $request, AuditLogService $auditLog, string $id, string $review): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $reviewModel = ProductReview::where('id', $review)->where('product_id', $product->id)->firstOrFail();
        $this->authorize('delete', $reviewModel);
        DB::transaction(function () use ($request, $auditLog, $reviewModel): void {
            $auditLog->record($request->user(), 'product_review.deleted', 'ProductReview', $reviewModel->id, $reviewModel->only(['product_id', 'user_id', 'rating', 'body']), null);
            $reviewModel->delete();
        });
        Cache::tags(['products'])->flush();

        return redirect()->route('admin.products.reviews', $product->id)
            ->with('message', __('Review deleted successfully.'));
    }
}
