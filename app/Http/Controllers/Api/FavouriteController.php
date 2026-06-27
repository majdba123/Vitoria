<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
                'photos' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->limit(1),
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

        $exists = $user->favouriteProducts()
            ->where('favourites.product_id', $product)
            ->exists();

        if ($exists) {
            $user->favouriteProducts()->detach($product);

            return response()->json([
                'message' => 'Removed from favourites.',
                'favourited' => false,
            ]);
        }

        $user->favouriteProducts()->attach($product);

        return response()->json([
            'message' => 'Added to favourites.',
            'favourited' => true,
        ]);
    }

    /**
     * Remove a product from favourites.
     */
    public function destroy(Request $request, int $product): JsonResponse
    {
        $request->user()->favouriteProducts()->detach($product);

        return response()->json(['message' => 'Removed from favourites.']);
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
