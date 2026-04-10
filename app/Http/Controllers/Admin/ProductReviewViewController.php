<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductReviewViewController extends Controller
{
    /**
     * Display paginated reviews for a product. Data loaded server-side so the page always works.
     */
    public function __invoke(Request $request, string $id): View
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

        return view('admin.products.reviews', [
            'product' => $product,
            'reviews' => $reviews,
        ]);
    }

    public function destroy(string $id, string $review): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $reviewModel = ProductReview::where('id', $review)->where('product_id', $product->id)->firstOrFail();
        $this->authorize('delete', $reviewModel);
        $reviewModel->delete();
        \Illuminate\Support\Facades\Cache::tags(['products'])->flush();

        return redirect()->route('admin.products.reviews', $product->id)
            ->with('message', __('Review deleted successfully.'));
    }
}
