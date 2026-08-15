<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPhoto;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavouriteController extends Controller
{
    /**
     * List the authenticated user's favourite products.
     */
    public function index(Request $request): JsonResponse
    {
        $products = $request->user()
            ->favouriteProducts()
            ->with([
                'photos' => function ($q) {
                    $q->orderByRaw(
                        'CASE WHEN image_type = ? THEN 0 WHEN is_primary = 1 THEN 1 ELSE 2 END',
                        [ProductPhoto::TYPE_PRIMARY]
                    )->orderBy('sort_order')->limit(1);
                },
                'category:id,name',
            ])
            ->select(['products.id', 'products.name', 'products.price', 'products.category_id', 'products.quantity'])
            ->latest('favourites.created_at')
            ->get();

        $mapped = $products->map(function ($p) {
            $photo = $p->photos->first();

            return [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price,
                'quantity' => $p->quantity,
                'first_photo_url' => $photo ? asset('storage/'.$photo->path) : null,
                'category' => $p->category ? ['id' => $p->category->id, 'name' => $p->category->name] : null,
            ];
        });

        return response()->json(['data' => $mapped]);
    }

    /**
     * Toggle a product in/out of the user's favourites.
     */
    public function toggle(Request $request, int $product): JsonResponse
    {
        $user = $request->user();
        Product::query()->findOrFail($product);

        // Atomic delete-if-present, else insert. Avoids the exists()-then-attach/detach
        // race where two concurrent toggles could both see "not favourited" and both
        // attempt to attach, which the unique (user_id, product_id) constraint would
        // otherwise turn into an unhandled 500 for the loser of the race.
        $removed = DB::table('favourites')
            ->where('user_id', $user->id)
            ->where('product_id', $product)
            ->delete();

        if ($removed > 0) {
            return response()->json([
                'message' => __('common.removed_from_favourites'),
                'favourited' => false,
            ]);
        }

        try {
            DB::table('favourites')->insert([
                'user_id' => $user->id,
                'product_id' => $product,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            // A concurrent request already inserted the same pair; the row exists
            // either way, so this is still a successful "favourited" outcome.
        }

        return response()->json([
            'message' => __('common.added_to_favourites'),
            'favourited' => true,
        ]);
    }

    /**
     * Remove a product from favourites.
     */
    public function destroy(Request $request, int $product): JsonResponse
    {
        Product::query()->findOrFail($product);
        $request->user()->favouriteProducts()->detach($product);

        return response()->json(['message' => __('common.removed_from_favourites')]);
    }

    /**
     * Get the IDs of all products the user has favourited.
     */
    public function ids(Request $request): JsonResponse
    {
        $ids = $request->user()
            ->favouriteProducts()
            ->pluck('products.id');

        return response()->json(['data' => $ids]);
    }
}
