<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest as AdminStoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest as AdminUpdateProductRequest;
use App\Http\Requests\Employee\UpdateProductRequest as EmployeeUpdateProductRequest;
use App\Http\Requests\Vendor\StoreProductRequest as VendorStoreProductRequest;
use App\Http\Requests\Vendor\UpdateProductRequest as VendorUpdateProductRequest;
use App\Http\Resources\ExternalProductResource;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Import\CsvImportException;
use App\Services\Import\Importers\ProductImporter;
use App\Services\NotificationService;
use App\Services\ProductService;
use App\Services\SelectedProductTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    protected const STORE_PROFILE_BASIC = 'basic';

    protected const STORE_PROFILE_AGRICULTURE = 'agriculture';

    protected const STORE_PROFILE_VETERINARY = 'veterinary';

    public function __construct(
        public NotificationService $notificationService,
        public ProductService $productService,
        public SelectedProductTypeService $selectedProductTypeService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $vendor = null;

        if ($user && $user->type === User::TYPE_VENDOR) {
            $vendor = $user->managedVendor();
            if (! $vendor) {
                abort(403, __('Vendor profile not found.'));
            }

            $filters = $request->only(['category_id', 'subcategory_id', 'category_type', 'product_type', 'status', 'is_active', 'has_discount', 'in_stock', 'search']);
        } else {
            $filters = $request->only(['vendor_id', 'category_id', 'subcategory_id', 'category_type', 'product_type', 'status', 'is_active', 'has_discount', 'in_stock', 'search']);
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
        $filters = $request->only(['category_id', 'subcategory_id', 'category_type', 'product_type', 'has_discount', 'per_page', 'sort', 'search']);
        $perPage = min((int) ($filters['per_page'] ?? 15), 50);
        $filters['per_page'] = $perPage;
        $filters['category_type'] = $request->has('category_type')
            ? trim((string) $request->input('category_type')) ?: null
            : $this->preferredCategoryType($request);

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

    public function publicShow(Request $request, Product $product): JsonResponse
    {
        if (! $product->is_active || $product->status !== Product::STATUS_APPROVED || $product->quantity <= 0) {
            abort(404, __('Product not found.'));
        }

        if (! $product->vendor || ! $product->vendor->is_active) {
            abort(404, __('Product not found.'));
        }

        $product->loadMissing('category');
        $this->selectedProductTypeService->abortIfTypeMismatch($request, $product->category?->type);

        $cacheKey = "pub_product:{$product->id}";
        try {
            $productData = Cache::tags(['products'])->remember($cacheKey, 1800, function () use ($product) {
                $product->load(['photos', 'category', 'subcategory', 'sharedDetail']);
                $product->loadCount('reviews')->loadAvg('reviews', 'rating');

                return new ProductResource($product);
            });
        } catch (\Exception $e) {
            $product->load(['photos', 'category', 'subcategory', 'sharedDetail']);
            $product->loadCount('reviews')->loadAvg('reviews', 'rating');
            $productData = new ProductResource($product);
        }

        return response()->json([
            'message' => __('Product retrieved successfully.'),
            'data' => $productData,
        ]);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->type === User::TYPE_VENDOR) {
            $vendor = $user->managedVendor();
            if (! $vendor) {
                abort(403, __('Vendor profile not found.'));
            }
            if ($product->vendor_id !== $vendor->id) {
                abort(403, __('You do not own this product.'));
            }
            $product->load(['photos', 'category', 'subcategory', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail']);
        } else {
            $product->load(['vendor.user', 'photos', 'category', 'subcategory', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail']);
        }

        if ($product->photos->isNotEmpty() && ! $product->photos->where('is_primary', true)->first()) {
            $firstPhoto = $product->photos->firstWhere('image_type', ProductPhoto::TYPE_PRIMARY) ?? $product->photos->first();
            $firstPhoto->update(['is_primary' => true]);
            $product->refresh();
            $product->load(['photos', 'category', 'subcategory', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail']);
        }

        return response()->json([
            'message' => __('Product retrieved successfully.'),
            'data' => new ProductResource($product),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->storeForProfile($request, self::STORE_PROFILE_BASIC);
    }

    public function storeBasic(Request $request): JsonResponse
    {
        return $this->storeForProfile($request, self::STORE_PROFILE_BASIC);
    }

    public function storeAgriculture(Request $request): JsonResponse
    {
        return $this->storeForProfile($request, self::STORE_PROFILE_AGRICULTURE);
    }

    public function storeVeterinary(Request $request): JsonResponse
    {
        return $this->storeForProfile($request, self::STORE_PROFILE_VETERINARY);
    }

    protected function storeForProfile(Request $request, string $profile): JsonResponse
    {
        $this->normalizeDetailPayload($request);

        $user = $request->user();
        $vendor = null;
        $targetVendor = null;

        if ($user && $user->type === User::TYPE_VENDOR) {
            $vendor = $user->managedVendor();
            if (! $vendor) {
                abort(403, __('Vendor profile not found.'));
            }
            if (! $user->hasVendorPermission($vendor, 'products.manage')) {
                abort(403, __('You are not allowed to manage this vendor\'s products.'));
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
        $this->validateStoreProfile($validated, $profile);

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
        try {
            $product = DB::transaction(function () use ($photos, $validated, $vendor) {
                unset($validated['photos']);
                $product = $this->productService->create($vendor, $validated);

                if (! empty($photos)) {
                    $this->productService->addPhotos($product, $photos, $this->buildPhotoMetadata($validated));
                    $product->load('photos');
                }

                return $product;
            });
        } catch (\Throwable $exception) {
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

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->normalizeDetailPayload($request);

        $user = $request->user();
        $targetVendor = $product->vendor;

        if ($user && $user->type === User::TYPE_VENDOR) {
            $vendor = $user->managedVendor();
            if (! $vendor) {
                abort(403, __('Vendor profile not found.'));
            }
            if ($product->vendor_id !== $vendor->id) {
                abort(403, __('You do not own this product.'));
            }
            if (! $user->hasVendorPermission($vendor, 'products.manage')) {
                abort(403, __('You are not allowed to manage this vendor\'s products.'));
            }
            $validated = $request->validate((new VendorUpdateProductRequest)->rules());
            $targetVendor = $vendor;
        } elseif ($user && $user->type === User::TYPE_EMPLOYEE) {
            $validated = $request->validate((new EmployeeUpdateProductRequest)->rules());
        } else {
            $validated = $request->validate((new AdminUpdateProductRequest)->rules());
        }

        if (array_key_exists('category_id', $validated)) {
            $this->validateCategoryBelongsToVendor($targetVendor, (int) $validated['category_id']);
        }

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
        $validated['discount_status'] = Product::resolveDiscountStatus(
            $validated['discount_is_active'],
            $effectiveDiscountPercentage,
            $validated['discount_starts_at'] ?? optional($product->discount_starts_at)->toDateTimeString(),
            $validated['discount_ends_at'] ?? optional($product->discount_ends_at)->toDateTimeString(),
        );

        if (isset($validated['status']) && (! $user || ! in_array($user->type, [User::TYPE_ADMIN, User::TYPE_EMPLOYEE], true))) {
            unset($validated['status']);
        }

        if (array_key_exists('status', $validated)) {
            $validated['rejection_reason'] = $validated['status'] === Product::STATUS_REJECTED
                ? ($validated['rejection_reason'] ?? $product->rejection_reason ?? null)
                : null;
        }

        $hadActiveDiscount = $product->discount_status === Product::DISCOUNT_STATUS_ACTIVE;
        $oldDiscountPct = $product->discount_percentage;
        $oldStarts = $product->discount_starts_at?->toDateTimeString();
        $oldEnds = $product->discount_ends_at?->toDateTimeString();
        $product = DB::transaction(fn () => $this->productService->update($product, $validated));

        $product->load($user && $user->type === User::TYPE_VENDOR
            ? ['photos', 'category', 'subcategory', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail']
            : ['vendor.user', 'photos', 'category', 'subcategory', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail']);

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

    public function updateStatus(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:pending,approved,rejected'],
        ]);

        $product = $this->productService->updateStatus($product, $request->input('status'));

        if ($product->status === Product::STATUS_APPROVED) {
            $this->notificationService->notifyNewProductApproved($product);
        }

        return response()->json([
            'message' => __('Product status updated successfully.'),
            'data' => new ProductResource($product),
        ]);
    }

    public function setPrimaryPhoto(Request $request, Product $product, ProductPhoto $photo): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->type === User::TYPE_VENDOR) {
            $vendor = $user->managedVendor();
            if (! $vendor) {
                abort(403, __('Vendor profile not found.'));
            }
            if ($product->vendor_id !== $vendor->id) {
                abort(403, __('You do not own this product.'));
            }
            if (! $user->hasVendorPermission($vendor, 'products.manage')) {
                abort(403, __('You are not allowed to manage this vendor\'s products.'));
            }
        }

        $this->productService->setPrimaryPhoto($product, $photo);

        return response()->json([
            'message' => __('Primary photo updated successfully.'),
            'data' => new ProductResource($product->fresh(['vendor.user', 'photos', 'category', 'subcategory', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail'])),
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->type === User::TYPE_VENDOR) {
            $vendor = $user->managedVendor();
            if (! $vendor) {
                abort(403, __('Vendor profile not found.'));
            }
            if ($product->vendor_id !== $vendor->id) {
                abort(403, __('You do not own this product.'));
            }
            if (! $user->hasVendorPermission($vendor, 'products.manage')) {
                abort(403, __('You are not allowed to manage this vendor\'s products.'));
            }
        }

        $this->productService->delete($product);

        return response()->json([
            'message' => __('Product deleted successfully.'),
        ]);
    }

    public function importTemplate(Request $request): StreamedResponse
    {
        $forVendor = $this->isVendorRequest($request);

        return (new ProductImporter($this->productService, $forVendor))->template();
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $context = [];

        if ($this->isVendorRequest($request)) {
            $vendor = $request->user()->managedVendor();
            if (! $vendor) {
                abort(403, __('Vendor profile not found.'));
            }
            if (! $request->user()->hasVendorPermission($vendor, 'products.manage')) {
                abort(403, __('You are not allowed to manage this vendor\'s products.'));
            }
            $context['vendor'] = $vendor;
        }

        try {
            $result = (new ProductImporter($this->productService, $this->isVendorRequest($request)))->import($request->file('file'), $context);
        } catch (CsvImportException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __(':created of :total products imported successfully.', [
                'created' => $result['created'],
                'total' => $result['total_rows'],
            ]),
            'data' => $result,
        ]);
    }

    protected function isVendorRequest(Request $request): bool
    {
        $user = $request->user();

        return $user !== null && $user->type === User::TYPE_VENDOR;
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

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function validateStoreProfile(array $validated, string $profile): void
    {
        if ($profile === self::STORE_PROFILE_BASIC) {
            return;
        }

        $categoryType = Category::query()->whereKey((int) $validated['category_id'])->value('type');

        if ($profile === self::STORE_PROFILE_AGRICULTURE) {
            if ($categoryType !== Category::TYPE_AGRICULTURE) {
                throw ValidationException::withMessages([
                    'category_id' => __('Selected category must belong to agriculture for this endpoint.'),
                ]);
            }

            if (empty($validated['agricultural_detail']) || ! is_array($validated['agricultural_detail'])) {
                throw ValidationException::withMessages([
                    'agricultural_detail' => __('Agricultural detail is required for this endpoint.'),
                ]);
            }

            return;
        }

        if ($profile === self::STORE_PROFILE_VETERINARY) {
            if ($categoryType !== Category::TYPE_VETERINARY) {
                throw ValidationException::withMessages([
                    'category_id' => __('Selected category must belong to veterinary for this endpoint.'),
                ]);
            }

            if (empty($validated['veterinary_detail']) || ! is_array($validated['veterinary_detail'])) {
                throw ValidationException::withMessages([
                    'veterinary_detail' => __('Veterinary detail is required for this endpoint.'),
                ]);
            }
        }
    }

    protected function preferredCategoryType(Request $request): ?string
    {
        return $this->selectedProductTypeService->resolve($request);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, array{image_type?: string, sort_order?: int}>
     */
    protected function buildPhotoMetadata(array $validated): array
    {
        $types = array_values((array) ($validated['photo_types'] ?? []));
        $sortOrders = array_values((array) ($validated['photo_sort_orders'] ?? []));
        $count = max(count($types), count($sortOrders));
        $metadata = [];

        for ($index = 0; $index < $count; $index++) {
            $metadata[$index] = [
                'image_type' => $types[$index] ?? \App\Models\ProductPhoto::TYPE_FRONT,
                'sort_order' => isset($sortOrders[$index]) ? (int) $sortOrders[$index] : $index + 1,
            ];
        }

        return $metadata;
    }

    protected function normalizeDetailPayload(Request $request): void
    {
        $fields = [
            'shared_detail.aliases',
            'shared_detail.barcodes',
            'shared_detail.keywords',
            'agricultural_detail.active_ingredients',
            'agricultural_detail.target_crops',
            'agricultural_detail.approved_uses',
            'agricultural_detail.application_methods',
            'agricultural_detail.application_rates',
            'agricultural_detail.storage_conditions',
            'agricultural_detail.warnings',
            'agricultural_detail.ppe_requirements',
            'agricultural_detail.first_aid',
            'agricultural_detail.compatibility',
            'agricultural_detail.target_pests',
            'agricultural_detail.pre_harvest_intervals',
            'agricultural_detail.environmental_hazards',
            'agricultural_detail.micronutrients',
            'agricultural_detail.growth_stages',
            'agricultural_detail.fertilization_methods',
            'agricultural_detail.seed_treatment',
            'agricultural_detail.disease_resistance',
            'agricultural_detail.planting_windows',
            'agricultural_detail.seeding_rate',
            'agricultural_detail.planting_depth',
            'agricultural_detail.plant_spacing',
            'agricultural_detail.expected_yield',
            'veterinary_detail.active_ingredients',
            'veterinary_detail.routes_of_administration',
            'veterinary_detail.target_species',
            'veterinary_detail.indications',
            'veterinary_detail.dosage_instructions',
            'veterinary_detail.contraindications',
            'veterinary_detail.warnings',
            'veterinary_detail.adverse_reactions',
            'veterinary_detail.drug_interactions',
            'veterinary_detail.storage_conditions',
        ];

        $payload = $request->all();

        if (! empty($payload['name']) && empty($payload['name_ar']) && empty($payload['name_en'])) {
            $payload['name_ar'] = $payload['name'];
            $payload['name_en'] = $payload['name'];
        }

        foreach ($fields as $field) {
            $value = data_get($payload, $field);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                data_set($payload, $field, $decoded);
            }
        }

        $request->replace($payload);
    }

    public function externalIndex(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $filters = $request->only(['category_id', 'subcategory_id', 'category_type', 'product_type', 'search']);
        $products = $this->productService->list(null, $perPage, $filters, onlyVisible: true);

        $products->getCollection()->load([
            'category',
            'subcategory',
            'sharedDetail.agriculturalDetail',
            'sharedDetail.veterinaryDetail',
        ]);

        return response()->json([
            'message' => __('Products retrieved successfully.'),
            'data' => ExternalProductResource::collection($products->getCollection()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function externalShow(Product $product): JsonResponse
    {
        if (! $product->is_active || $product->status !== Product::STATUS_APPROVED) {
            abort(404, __('Product not found.'));
        }

        if (! $product->vendor || ! $product->vendor->is_active) {
            abort(404, __('Product not found.'));
        }

        $product->load([
            'category',
            'subcategory',
            'sharedDetail.agriculturalDetail',
            'sharedDetail.veterinaryDetail',
        ]);

        return response()->json([
            'message' => __('Product retrieved successfully.'),
            'data' => new ExternalProductResource($product),
        ]);
    }
}
