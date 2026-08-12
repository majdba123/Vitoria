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
            $rules['photo_types'] = ['sometimes', 'array'];
            $rules['photo_types.*'] = ['required', 'in:'.implode(',', ProductPhoto::allowedTypes())];
            $rules['photo_sort_orders'] = ['sometimes', 'array'];
            $rules['photo_sort_orders.*'] = ['nullable', 'integer', 'min:1'];
        }

        if ($request->has('primary_photo_id')) {
            $rules['primary_photo_id'] = ['nullable', 'integer', 'exists:product_photos,id'];
        }

        if ($request->has('photo_ids')) {
            $rules['photo_ids'] = ['array'];
            $rules['photo_ids.*'] = ['required', 'integer', 'exists:product_photos,id'];
            $rules['existing_photo_types'] = ['sometimes', 'array'];
            $rules['existing_photo_types.*'] = ['required', 'in:'.implode(',', ProductPhoto::allowedTypes())];
            $rules['existing_photo_sort_orders'] = ['sometimes', 'array'];
            $rules['existing_photo_sort_orders.*'] = ['nullable', 'integer', 'min:1'];
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
                    $product->refresh();
                }
            }
        }

        if ($request->has('photo_ids')) {
            $photoIds = array_values(array_map('intval', (array) $request->input('photo_ids', [])));
            $photoTypes = array_values((array) $request->input('existing_photo_types', []));
            $photoSortOrders = array_values((array) $request->input('existing_photo_sort_orders', []));
            $updates = [];

            foreach ($photoIds as $index => $photoId) {
                $updates[] = [
                    'id' => $photoId,
                    'image_type' => $photoTypes[$index] ?? ProductPhoto::TYPE_FRONT,
                    'sort_order' => isset($photoSortOrders[$index]) ? (int) $photoSortOrders[$index] : $index + 1,
                ];
            }

            if ($updates !== []) {
                $this->productService->updatePhotoMetadata($product, $updates);
                $product->refresh();
            }
        }

        if ($request->hasFile('photos')) {
            $files = $request->file('photos');
            if (is_array($files)) {
                $validFiles = array_filter($files, function ($file) {
                    return $file && $file->isValid();
                });
                if (! empty($validFiles)) {
                    $metadata = $this->buildPhotoMetadata($request);
                    $this->productService->addPhotos($product, array_values($validFiles), $metadata);
                    $product->refresh();
                }
            }
        }

        if ($request->has('primary_photo_id')) {
            $primaryPhotoId = $request->input('primary_photo_id');
            if ($primaryPhotoId !== null && $primaryPhotoId !== '') {
                $primaryPhotoId = (int) $primaryPhotoId;
                $product->refresh();
                $photo = $product->photos()->where('id', $primaryPhotoId)->first();
                if ($photo) {
                    $this->productService->setPrimaryPhoto($product, $photo);
                }
            }
        }

        $product->refresh();
        $user = $request->user();
        $product->load($user && $user->type === User::TYPE_VENDOR ? ['photos', 'subcategory'] : ['vendor.user', 'photos', 'subcategory']);

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
            $vendor = $user->managedVendor();
            if (! $vendor) {
                abort(403, __('Vendor profile not found.'));
            }
            if ($product->vendor_id !== $vendor->id) {
                abort(403, __('You do not own this product.'));
            }
        }
        // Admin has access to all products, no check needed
    }

    /**
     * @return array<int, array{image_type?: string, sort_order?: int}>
     */
    private function buildPhotoMetadata(Request $request): array
    {
        $types = array_values((array) $request->input('photo_types', []));
        $sortOrders = array_values((array) $request->input('photo_sort_orders', []));
        $count = max(count($types), count($sortOrders));
        $metadata = [];

        for ($index = 0; $index < $count; $index++) {
            $metadata[$index] = [
                'image_type' => $types[$index] ?? ProductPhoto::TYPE_FRONT,
                'sort_order' => isset($sortOrders[$index]) ? (int) $sortOrders[$index] : $index + 1,
            ];
        }

        return $metadata;
    }
}
