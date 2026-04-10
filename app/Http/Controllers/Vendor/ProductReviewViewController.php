<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductReviewViewController extends Controller
{
    /**
     * Display paginated reviews for the vendor's product. Data loaded server-side.
     */
    public function __invoke(Request $request, string $id): View
    {
        $vendor = $request->user()->vendor;
        if (! $vendor) {
            abort(403, __('Vendor profile not found.'));
        }

        $product = Product::where('id', $id)->where('vendor_id', $vendor->id)->first();
        if (! $product) {
            abort(404, __('Product not found.'));
        }

        $perPage = min((int) $request->get('per_page', 15), 30);
        $reviews = $product->reviews()
            ->with('user:id,name')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('vendor.products.reviews', [
            'product' => $product,
            'reviews' => $reviews,
        ]);
    }

    public function destroy(Request $request, string $productId, string $reviewId): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        if (! $vendor) {
            abort(403, __('Vendor profile not found.'));
        }

        $product = Product::where('id', $productId)->where('vendor_id', $vendor->id)->firstOrFail();
        $review = ProductReview::where('id', $reviewId)->where('product_id', $product->id)->firstOrFail();
        $this->authorize('delete', $review);
        $review->delete();
        \Illuminate\Support\Facades\Cache::tags(['products'])->flush();

        return redirect()->route('vendor.products.reviews', $product->id)
            ->with('message', __('Review deleted successfully.'));
    }
}
