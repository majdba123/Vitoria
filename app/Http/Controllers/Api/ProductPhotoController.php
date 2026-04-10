<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductPhotoResource;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductPhotoController extends Controller
{
    public function __construct(public ProductService $productService) {}

    /**
     * List photos for a product.
     * Admin: can view any product's photos.
     * Vendor: can only view their own product's photos.
     */
    public function index(Request $request, Product $product): JsonResponse
    {
        $this->authorizeAccess($request, $product);

        $product->load('photos');

        return response()->json([
            'message' => __('Photos retrieved successfully.'),
            'data' => ProductPhotoResource::collection($product->photos),
        ]);
    }

    /**
     * Upload photos to a product.
     * Admin: can upload to any product.
     * Vendor: can only upload to their own products.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $this->authorizeAccess($request, $product);

        $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
        ]);

        $photos = $this->productService->addPhotos($product, $request->file('photos'));

        return response()->json([
            'message' => __(':count photo(s) uploaded successfully.', ['count' => count($photos)]),
            'data' => ProductPhotoResource::collection($photos),
        ], 201);
    }

    /**
     * Remove a single photo.
     * Admin: can remove from any product.
     * Vendor: can only remove from their own products.
     */
    public function destroy(Request $request, Product $product, ProductPhoto $photo): JsonResponse
    {
        $this->authorizeAccess($request, $product);

        if ($photo->product_id !== $product->id) {
            abort(404);
        }

        $this->productService->removePhoto($photo);

        return response()->json([
            'message' => __('Photo deleted successfully.'),
        ]);
    }

    /**
     * Bulk-remove photos by IDs.
     * Admin: can remove from any product.
     * Vendor: can only remove from their own products.
     */
    public function bulkDestroy(Request $request, Product $product): JsonResponse
    {
        $this->authorizeAccess($request, $product);

        $request->validate([
            'photo_ids' => ['required', 'array', 'min:1'],
            'photo_ids.*' => ['required', 'integer', 'exists:product_photos,id'],
        ]);

        $count = $this->productService->removePhotos($product, $request->input('photo_ids'));

        return response()->json([
            'message' => __(':count photo(s) deleted successfully.', ['count' => $count]),
        ]);
    }

    /**
     * Update photos: remove, upload new, and set primary in one request.
     * Admin: can update any product's photos.
     * Vendor: can only update their own product's photos.
     */
    public function updatePhotos(Request $request, Product $product): JsonResponse
    {
        $this->authorizeAccess($request, $product);

        // Build validation rules dynamically based on what's present
        $rules = [];

        if ($request->has('photo_ids_to_remove')) {
            $rules['photo_ids_to_remove'] = ['array'];
            $rules['photo_ids_to_remove.*'] = ['integer', 'exists:product_photos,id'];
        }

        if ($request->hasFile('photos')) {
            $rules['photos'] = ['array', 'max:10'];
            $rules['photos.*'] = ['image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'];
        }

        if ($request->has('primary_photo_id')) {
            $rules['primary_photo_id'] = ['nullable', 'integer', 'exists:product_photos,id'];
        }

        if (! empty($rules)) {
            $request->validate($rules);
        }

        // Step 1: Remove marked photos first
        if ($request->has('photo_ids_to_remove')) {
            $photoIdsToRemove = $request->input('photo_ids_to_remove', []);
            if (is_array($photoIdsToRemove) && ! empty($photoIdsToRemove)) {
                $removedPhotoIds = array_values(array_filter(array_map('intval', $photoIdsToRemove)));
                if (! empty($removedPhotoIds)) {
                    $this->productService->removePhotos($product, $removedPhotoIds);
                    // Refresh product after deletion
                    $product->refresh();
                }
            }
        }

        // Step 2: Upload new photos
        if ($request->hasFile('photos')) {
            $files = $request->file('photos');
            if (is_array($files)) {
                $validFiles = array_filter($files, function ($file) {
                    return $file && $file->isValid();
                });
                if (! empty($validFiles)) {
                    $this->productService->addPhotos($product, array_values($validFiles));
                    // Refresh product after upload
                    $product->refresh();
                }
            }
        }

        // Step 3: Set primary photo (must be done after uploads in case new photo is set as primary)
        if ($request->has('primary_photo_id')) {
            $primaryPhotoId = $request->input('primary_photo_id');
            if ($primaryPhotoId !== null && $primaryPhotoId !== '') {
                $primaryPhotoId = (int) $primaryPhotoId;
                // Refresh to get latest photos
                $product->refresh();
                $photo = $product->photos()->where('id', $primaryPhotoId)->first();
                if ($photo) {
                    $this->productService->setPrimaryPhoto($product, $photo);
                }
            }
        }

        // Final reload of product with fresh photos
        $product->refresh();
        $user = $request->user();
        $product->load($user && $user->type === User::TYPE_VENDOR ? 'photos' : ['vendor.user', 'photos']);

        return response()->json([
            'message' => __('Photos updated successfully.'),
            'data' => new \App\Http\Resources\ProductResource($product),
        ]);
    }

    /**
     * Authorize access to product photos.
     * Admin: can access any product.
     * Vendor: can only access their own products.
     */
    private function authorizeAccess(Request $request, Product $product): void
    {
        $user = $request->user();

        // Vendor can only access their own products
        if ($user && $user->type === User::TYPE_VENDOR) {
            $vendor = $user->vendor;
            if (! $vendor) {
                abort(403, __('Vendor profile not found.'));
            }
            if ($product->vendor_id !== $vendor->id) {
                abort(403, __('You do not own this product.'));
            }
        }
        // Admin has access to all products, no check needed
    }
}
