<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest as AdminStoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest as AdminUpdateProductRequest;
use App\Http\Requests\Vendor\StoreProductRequest as VendorStoreProductRequest;
use App\Http\Requests\Vendor\UpdateProductRequest as VendorUpdateProductRequest;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\Subcategory;
use App\Models\User;
use App\Models\Vendor;
use App\Services\NotificationService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function __construct(
        public NotificationService $notificationService,
        public ProductService $productService,
    ) {}

    /**
     * List products (slim response) with optional filters.
     * Admin: all products, can filter by vendor_id and is_active.
     * Vendor: only their products, can filter by is_active.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $vendor = null;

        // If vendor, scope to their products
        if ($user && $user->type === User::TYPE_VENDOR) {
            $vendor = $user->vendor;
            if (! $vendor) {
                abort(403, __('Vendor profile not found.'));
            }
            // Allow status and is_active filters for vendors
            $filters = $request->only(['category_id', 'category_type', 'subcategory_id', 'status', 'is_active', 'has_discount']);
        } else {
            // Admin: can filter by vendor_id, subcategory_id, status, and is_active
            $filters = $request->only(['vendor_id', 'category_id', 'category_type', 'subcategory_id', 'status', 'is_active', 'has_discount']);
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $products = $this->productService->list($vendor, $perPage, $filters);

        return response()->json([
            'message' => __('Products retrieved successfully.'),
            'data' => ProductListResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function publicIndex(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id', 'category_type', 'subcategory_id', 'has_discount', 'per_page', 'sort']);
        $perPage = min((int) ($filters['per_page'] ?? 15), 50);
        $filters['per_page'] = $perPage;

        $products = $this->productService->listPublic($perPage, $filters);

        return response()->json([
            'message' => __('Products retrieved successfully.'),
            'data' => ProductListResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Show a public product (for clients/users).
     */
    public function publicShow(Product $product): JsonResponse
    {
        if (! $product->is_active || $product->status !== Product::STATUS_APPROVED || $product->quantity <= 0) {
            abort(404, __('Product not found.'));
        }

        if (! $product->vendor || ! $product->vendor->is_active) {
            abort(404, __('Product not found.'));
        }

        $cacheKey = "pub_product:{$product->id}";
        try {
            $productData = Cache::tags(['products'])->remember($cacheKey, 1800, function () use ($product) {
                $product->load(['photos', 'subcategory.category']);
                $product->loadCount('reviews')->loadAvg('reviews', 'rating');

                return new ProductResource($product);
            });
        } catch (\Exception $e) {
            $product->load(['photos', 'subcategory.category']);
            $product->loadCount('reviews')->loadAvg('reviews', 'rating');
            $productData = new ProductResource($product);
        }

        return response()->json([
            'message' => __('Product retrieved successfully.'),
            'data' => $productData,
        ]);
    }

    /**
     * Show a single product.
     * Admin: can view any product.
     * Vendor: can only view their own products.
     */
    public function show(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        // Vendor can only view their own products
        if ($user && $user->type === User::TYPE_VENDOR) {
            $vendor = $user->vendor;
            if (! $vendor) {
                abort(403, __('Vendor profile not found.'));
            }
            if ($product->vendor_id !== $vendor->id) {
                abort(403, __('You do not own this product.'));
            }
            $product->load(['photos', 'subcategory.category']);
        } else {
            // Admin: load vendor relationship
            $product->load(['vendor.user', 'photos', 'subcategory.category']);
        }

        // Ensure primary photo is set if photos exist but no primary is set
        if ($product->photos->isNotEmpty() && ! $product->photos->where('is_primary', true)->first()) {
            $firstPhoto = $product->photos->first();
            $firstPhoto->update(['is_primary' => true]);
            $product->refresh();
            $product->load(['photos', 'subcategory.category']);
        }

        return response()->json([
            'message' => __('Product retrieved successfully.'),
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Create a product with optional photos.
     * Admin: must provide vendor_id.
     * Vendor: vendor_id is set automatically.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $vendor = null;
        $targetVendor = null;

        // Determine which request class to use and validate
        if ($user && $user->type === User::TYPE_VENDOR) {
            $vendor = $user->vendor;
            if (! $vendor) {
                abort(403, __('Vendor profile not found.'));
            }
            $validated = $request->validate((new VendorStoreProductRequest)->rules());
            $targetVendor = $vendor;
        } else {
            $validated = $request->validate((new AdminStoreProductRequest)->rules());
            $targetVendor = Vendor::query()->find((int) $validated['vendor_id']);
            if (! $targetVendor) {
                throw ValidationException::withMessages([
                    'vendor_id' => __('Selected vendor is invalid.'),
                ]);
            }
        }

        $this->validateCategoryBelongsToVendor($targetVendor, (int) $validated['category_id']);
        $this->validateSubcategoryBelongsToCategory(
            (int) $validated['category_id'],
            (int) $validated['subcategory_id'],
        );

        // Convert is_active to boolean if present
        if (isset($validated['is_active'])) {
            $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }
        if (isset($validated['discount_percentage']) && (float) $validated['discount_percentage'] <= 0) {
            $validated['discount_percentage'] = null;
        }
        $discountPercentage = isset($validated['discount_percentage']) ? (float) $validated['discount_percentage'] : null;
        $validated['discount_is_active'] = $discountPercentage !== null && $discountPercentage > 0;
        $validated['discount_status'] = Product::resolveDiscountStatus(
            $validated['discount_is_active'],
            $discountPercentage,
            $validated['discount_starts_at'] ?? null,
            $validated['discount_ends_at'] ?? null,
        );

        $photos = $request->file('photos', []);
        $storedPaths = [];
        $createdProductId = null;

        try {
            $product = DB::transaction(function () use (&$createdProductId, &$storedPaths, $photos, $request, $validated, $vendor) {
                $displayAssets = $this->productService->storeDisplayAssets([
                    'icon' => $request->file('icon'),
                    'image' => $request->file('image'),
                ]);
                $storedPaths = array_values($displayAssets);

                unset($validated['photos'], $validated['icon'], $validated['image']);
                $productData = array_merge($validated, $displayAssets);

                $product = $this->productService->create($vendor, $productData);
                $createdProductId = $product->id;

                if (! empty($photos)) {
                    $createdPhotos = $this->productService->addPhotos($product, $photos);
                    foreach ($createdPhotos as $photo) {
                        $storedPaths[] = $photo->path;
                    }
                    $product->load('photos');
                }

                return $product;
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            if ($createdProductId) {
                Storage::disk('public')->deleteDirectory('products/'.$createdProductId);
            }

            throw $exception;
        }

        if ($product->discount_status === Product::DISCOUNT_STATUS_ACTIVE) {
            $this->notificationService->notifyProductDiscountAdded($product);
        }

        return response()->json([
            'message' => __('Product created successfully.'),
            'data' => new ProductResource($product),
        ], 201);
    }

    /**
     * Update a product.
     * Admin: can update any product.
     * Vendor: can only update their own products.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();
        $targetVendor = $product->vendor;

        // Vendor can only update their own products
        if ($user && $user->type === User::TYPE_VENDOR) {
            $vendor = $user->vendor;
            if (! $vendor) {
                abort(403, __('Vendor profile not found.'));
            }
            if ($product->vendor_id !== $vendor->id) {
                abort(403, __('You do not own this product.'));
            }
            $validated = $request->validate((new VendorUpdateProductRequest)->rules());
            $targetVendor = $vendor;
        } else {
            $validated = $request->validate((new AdminUpdateProductRequest)->rules());
        }

        if (array_key_exists('category_id', $validated) || array_key_exists('subcategory_id', $validated)) {
            $effectiveCategoryId = array_key_exists('category_id', $validated)
                ? (int) $validated['category_id']
                : (int) ($product->subcategory?->category_id ?? 0);

            $effectiveSubcategoryId = array_key_exists('subcategory_id', $validated)
                ? (int) $validated['subcategory_id']
                : (int) ($product->subcategory_id ?? 0);

            if ($effectiveCategoryId > 0 && $effectiveSubcategoryId > 0) {
                $this->validateCategoryBelongsToVendor($targetVendor, $effectiveCategoryId);
                $this->validateSubcategoryBelongsToCategory($effectiveCategoryId, $effectiveSubcategoryId);
            }
        }

        // Convert is_active to boolean if present
        if (isset($validated['is_active'])) {
            $validated['is_active'] = filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }
        if (array_key_exists('discount_percentage', $validated) && (float) $validated['discount_percentage'] <= 0) {
            $validated['discount_percentage'] = null;
        }
        $effectiveDiscountPercentage = array_key_exists('discount_percentage', $validated)
            ? (float) ($validated['discount_percentage'] ?? 0)
            : (float) ($product->discount_percentage ?? 0);
        $validated['discount_is_active'] = $effectiveDiscountPercentage > 0;
        $autoDiscountStatus = Product::resolveDiscountStatus(
            $validated['discount_is_active'],
            $effectiveDiscountPercentage,
            $validated['discount_starts_at'] ?? optional($product->discount_starts_at)->toDateTimeString(),
            $validated['discount_ends_at'] ?? optional($product->discount_ends_at)->toDateTimeString(),
        );
        $validated['discount_status'] = $autoDiscountStatus;

        // Only admin can update status
        if (isset($validated['status'])) {
            if (! $user || $user->type !== User::TYPE_ADMIN) {
                unset($validated['status']); // Remove status from validated data for non-admins
            }
        }

        // Note: Photo updates (remove, upload, set primary) are handled separately via ProductPhotoController::updatePhotos

        $hadActiveDiscount = $product->discount_status === Product::DISCOUNT_STATUS_ACTIVE;
        $oldDiscountPct = $product->discount_percentage;
        $oldStarts = $product->discount_starts_at?->toDateTimeString();
        $oldEnds = $product->discount_ends_at?->toDateTimeString();

        $oldAssets = [
            'icon' => $product->icon,
            'image' => $product->image,
        ];
        $newAssets = [];

        try {
            $product = DB::transaction(function () use ($request, $product, $validated, &$newAssets) {
                $newAssets = $this->productService->replaceDisplayAssets($product, [
                    'icon' => $request->file('icon'),
                    'image' => $request->file('image'),
                ]);
                unset($validated['icon'], $validated['image']);

                return $this->productService->update($product, array_merge($validated, $newAssets));
            });
        } catch (\Throwable $exception) {
            foreach ($newAssets as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        foreach ($newAssets as $field => $path) {
            if ($path && ! empty($oldAssets[$field])) {
                Storage::disk('public')->delete($oldAssets[$field]);
            }
        }

        $product->load($user && $user->type === User::TYPE_VENDOR ? ['photos', 'subcategory.category'] : ['vendor.user', 'photos', 'subcategory.category']);

        if ($product->discount_status === Product::DISCOUNT_STATUS_ACTIVE) {
            if (! $hadActiveDiscount) {
                $this->notificationService->notifyProductDiscountAdded($product);
            } else {
                $pctChanged = (float) ($product->discount_percentage ?? 0) !== (float) ($oldDiscountPct ?? 0);
                $startsChanged = ($product->discount_starts_at?->toDateTimeString() ?? '') !== ($oldStarts ?? '');
                $endsChanged = ($product->discount_ends_at?->toDateTimeString() ?? '') !== ($oldEnds ?? '');
                if ($pctChanged || $startsChanged || $endsChanged) {
                    $this->notificationService->notifyProductDiscountUpdated($product);
                }
            }
        }

        return response()->json([
            'message' => __('Product updated successfully.'),
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Toggle product active status (admin only).
     */
    public function toggleActive(Product $product): JsonResponse
    {
        $product = $this->productService->toggleActive($product);

        return response()->json([
            'message' => $product->is_active
                ? __('Product activated successfully.')
                : __('Product deactivated successfully.'),
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Update product status (admin only).
     */
    public function updateStatus(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:pending,approved,rejected'],
        ]);

        $product = $this->productService->updateStatus($product, $request->input('status'));

        if ($product->status === 'approved') {
            $this->notificationService->notifyNewProductApproved($product);
        }

        return response()->json([
            'message' => __('Product status updated successfully.'),
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Set primary photo for a product.
     */
    public function setPrimaryPhoto(Request $request, Product $product, ProductPhoto $photo): JsonResponse
    {
        $this->productService->setPrimaryPhoto($product, $photo);

        return response()->json([
            'message' => __('Primary photo updated successfully.'),
            'data' => new ProductResource($product->fresh(['vendor.user', 'photos', 'subcategory.category'])),
        ]);
    }

    /**
     * Delete a product.
     * Admin: can delete any product.
     * Vendor: can only delete their own products.
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        // Vendor can only delete their own products
        if ($user && $user->type === User::TYPE_VENDOR) {
            $vendor = $user->vendor;
            if (! $vendor) {
                abort(403, __('Vendor profile not found.'));
            }
            if ($product->vendor_id !== $vendor->id) {
                abort(403, __('You do not own this product.'));
            }
        }

        $this->productService->delete($product);

        return response()->json([
            'message' => __('Product deleted successfully.'),
        ]);
    }

    protected function validateSubcategoryBelongsToCategory(int $categoryId, int $subcategoryId): void
    {
        $belongsToCategory = Subcategory::query()
            ->where('id', $subcategoryId)
            ->where('category_id', $categoryId)
            ->exists();

        if (! $belongsToCategory) {
            throw ValidationException::withMessages([
                'subcategory_id' => __('Selected subcategory does not belong to the selected category.'),
            ]);
        }
    }

    protected function validateCategoryBelongsToVendor(?Vendor $vendor, int $categoryId): void
    {
        if (! $vendor) {
            throw ValidationException::withMessages([
                'vendor_id' => __('Selected vendor is invalid.'),
            ]);
        }

        $allowed = $vendor->categories()->where('categories.id', $categoryId)->exists();
        if (! $allowed) {
            throw ValidationException::withMessages([
                'category_id' => __('Selected category is not assigned to this vendor.'),
            ]);
        }
    }
}
