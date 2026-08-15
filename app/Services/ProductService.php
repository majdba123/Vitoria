<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\SharedProductDetail;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public static function applyPhotoDisplayOrder(\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\HasMany $query): void
    {
        $query
            ->orderByRaw(
                'CASE WHEN image_type = ? THEN 0 WHEN is_primary = 1 THEN 1 ELSE 2 END',
                [ProductPhoto::TYPE_PRIMARY]
            )
            ->orderBy('sort_order');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(?Vendor $vendor = null, int $perPage = 15, array $filters = [], bool $onlyVisible = false): LengthAwarePaginator
    {
        $query = $vendor
            ? $vendor->products()
            : Product::query();

        $query->with([
            'photos' => function ($q) {
                self::applyPhotoDisplayOrder($q);
                $q->limit(3);
            },
            'category:id,name,type,commission',
            'subcategory:id,category_id,name_ar,name_en',
            'sharedDetail.agriculturalDetail',
            'sharedDetail.veterinaryDetail',
        ]);

        $this->applyListFilters($query, $filters, ! $vendor);

        if ($onlyVisible) {
            $query->where('is_active', true)
                ->where('status', Product::STATUS_APPROVED)
                ->whereHas('vendor', fn ($vendorQuery) => $vendorQuery->where('is_active', true));
        }

        if (! $vendor) {
            $query->with('vendor:id,store_name,user_id', 'vendor.user:id,name');
        }

        return $query->latest('products.created_at')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listPublic(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $vendorId = ! empty($filters['vendor_id']) ? (int) $filters['vendor_id'] : null;
        $categoryId = ! empty($filters['category_id']) ? (int) $filters['category_id'] : null;
        $subcategoryId = ! empty($filters['subcategory_id']) ? (int) $filters['subcategory_id'] : null;
        $categoryType = ! empty($filters['category_type']) ? (string) $filters['category_type'] : null;
        $productType = ! empty($filters['product_type']) ? (string) $filters['product_type'] : null;
        $search = ! empty($filters['search']) ? trim((string) $filters['search']) : null;
        $hasDiscount = isset($filters['has_discount']) && $filters['has_discount'] !== ''
            ? filter_var($filters['has_discount'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;
        $sort = isset($filters['sort']) && in_array($filters['sort'], ['top_rated', 'best_selling', 'most_favorited'], true)
            ? $filters['sort'] : 'latest';
        $page = (int) request()->get('page', 1);

        // Locale is part of the cache key: listed products render a localized
        // name (see ProductResource::localizedName()), so a locale-blind key
        // would let the first viewer's language get served to every other
        // locale for the rest of the cache lifetime.
        $cacheKey = 'pub_products:'.sha1(json_encode([
            'locale' => app()->getLocale(),
            'vendor_id' => $vendorId,
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'category_type' => $categoryType,
            'product_type' => $productType,
            'search' => $search,
            'has_discount' => $hasDiscount,
            'sort' => $sort,
            'per_page' => $perPage,
            'page' => $page,
        ]));

        return $this->cachedOrFetch(['products'], $cacheKey, 900, function () use ($perPage, $vendorId, $categoryId, $subcategoryId, $categoryType, $productType, $search, $hasDiscount, $sort) {
            return $this->fetchPublicProducts($perPage, $vendorId, $categoryId, $subcategoryId, $categoryType, $productType, $search, $hasDiscount, $sort);
        });
    }

    /**
     * @param  'latest'|'top_rated'|'best_selling'|'most_favorited'  $sort
     */
    protected function fetchPublicProducts(
        int $perPage,
        ?int $vendorId = null,
        ?int $categoryId = null,
        ?int $subcategoryId = null,
        ?string $categoryType = null,
        ?string $productType = null,
        ?string $search = null,
        ?bool $hasDiscount = null,
        string $sort = 'latest'
    ): LengthAwarePaginator {
        $query = Product::query()
            ->visible()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->with([
                'photos' => function ($q) {
                    self::applyPhotoDisplayOrder($q);
                    $q->limit(1);
                },
                'category:id,name,type,logo,icon',
                'subcategory:id,category_id,name_ar,name_en',
                'vendor:id,store_name,user_id,logo,is_active,status',
                'sharedDetail',
            ]);

        $this->applyListFilters($query, [
            'vendor_id' => $vendorId,
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'category_type' => $categoryType,
            'product_type' => $productType,
            'search' => $search,
            'has_discount' => $hasDiscount,
            'in_stock' => true,
        ], true);

        match ($sort) {
            'top_rated' => $query->having('reviews_count', '>=', 1)->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'),
            'best_selling' => $this->applyBestSellingOrder($query),
            'most_favorited' => $this->applyMostFavoritedOrder($query),
            default => $query->latest('products.created_at'),
        };

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyListFilters(\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\HasMany $query, array $filters, bool $allowVendorFilter): void
    {
        if ($allowVendorFilter && ! empty($filters['vendor_id'])) {
            $query->where('vendor_id', (int) $filters['vendor_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['subcategory_id'])) {
            $query->where('subcategory_id', (int) $filters['subcategory_id']);
        }

        if (! empty($filters['category_type'])) {
            $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('type', $filters['category_type']));
        }

        if (! empty($filters['product_type'])) {
            $productType = (string) $filters['product_type'];

            if ($productType === 'veterinary_medicine') {
                $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('type', Category::TYPE_VETERINARY));
            } elseif ($productType === Category::TYPE_AGRICULTURE) {
                $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('type', Category::TYPE_AGRICULTURE));
            } else {
                $query->whereHas('sharedDetail.agriculturalDetail', fn ($detailQuery) => $detailQuery->where('agricultural_product_type', $productType));
            }
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            $query->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('name_ar', 'like', '%'.$search.'%')
                    ->orWhere('name_en', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhereHas('sharedDetail', function ($detailQuery) use ($search) {
                        $detailQuery
                            ->where('commercial_name', 'like', '%'.$search.'%')
                            ->orWhere('barcodes', 'like', '%'.$search.'%')
                            ->orWhere('manufacturer_name_ar', 'like', '%'.$search.'%')
                            ->orWhere('manufacturer_name_en', 'like', '%'.$search.'%')
                            ->orWhere('brand_name_ar', 'like', '%'.$search.'%')
                            ->orWhere('brand_name_en', 'like', '%'.$search.'%');
                    });
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['in_stock']) && $filters['in_stock'] !== '') {
            $inStock = filter_var($filters['in_stock'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($inStock === true) {
                $query->where('quantity', '>', 0);
            } elseif ($inStock === false) {
                $query->where('quantity', '<=', 0);
            }
        }

        if (isset($filters['has_discount']) && $filters['has_discount'] !== '') {
            $wantDiscount = filter_var($filters['has_discount'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $query->where(function ($builder) use ($wantDiscount) {
                $activeDiscountQuery = fn ($discountQuery) => $discountQuery
                    ->where('discount_is_active', true)
                    ->where('discount_percentage', '>', 0)
                    ->where('discount_status', Product::DISCOUNT_STATUS_ACTIVE);

                if ($wantDiscount) {
                    $activeDiscountQuery($builder);
                } else {
                    $builder->where(function ($negativeQuery) {
                        $negativeQuery->where('discount_is_active', false)
                            ->orWhereNull('discount_percentage')
                            ->orWhere('discount_percentage', '<=', 0)
                            ->orWhere('discount_status', '!=', Product::DISCOUNT_STATUS_ACTIVE);
                    });
                }
            });
        }
    }

    protected function applyBestSellingOrder(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->withSum(
            [
                'orderItems as sold_count' => function ($q) {
                    $q->whereHas('order', fn ($o) => $o->whereIn('status', [\App\Models\Order::STATUS_CONFIRMED, \App\Models\Order::STATUS_COMPLETED]));
                },
            ],
            'quantity'
        )->orderByDesc('sold_count');
    }

    protected function applyMostFavoritedOrder(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->withCount('favouritedBy')->orderByDesc('favourited_by_count');
    }

    public function create(?Vendor $vendor, array $data): Product
    {
        $detailPayload = $this->extractDetailPayload($data);
        $data['name'] = $data['name_ar'] ?? $data['name_en'] ?? $data['name'] ?? null;

        if (! isset($data['status'])) {
            $data['status'] = Product::STATUS_PENDING;
        }

        $product = $vendor
            ? $vendor->products()->create($data)
            : Product::query()->create($data);

        $this->syncProductDetails($product, $detailPayload);

        $this->flushProductCache();

        return $product->load($vendor ? ['photos', 'category', 'subcategory', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail'] : ['vendor.user', 'photos', 'category', 'subcategory', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail']);
    }

    public function update(Product $product, array $data): Product
    {
        $detailPayload = $this->extractDetailPayload($data);
        $data['name'] = $data['name_ar'] ?? $data['name_en'] ?? $data['name'] ?? $product->getRawOriginal('name');

        $product->update($data);
        $this->syncProductDetails($product, $detailPayload, true);
        $this->flushProductCache();

        return $product->fresh($product->vendor ? ['vendor.user', 'photos', 'category', 'subcategory', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail'] : ['photos', 'category', 'subcategory', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail']);
    }

    public function delete(Product $product): void
    {
        foreach ($product->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }

        $product->delete();
        $this->flushProductCache();
    }

    public function toggleActive(Product $product): Product
    {
        $product->update(['is_active' => ! $product->is_active]);
        $this->flushProductCache();

        return $product->fresh(['vendor.user', 'photos', 'category', 'subcategory', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail']);
    }

    public function updateStatus(Product $product, string $status): Product
    {
        $product->update(['status' => $status]);
        $this->flushProductCache();

        return $product->fresh(['vendor.user', 'photos', 'category', 'subcategory', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail']);
    }

    public function setPrimaryPhoto(Product $product, ProductPhoto $photo): Product
    {
        if ($photo->product_id !== $product->id) {
            throw new \InvalidArgumentException('Photo does not belong to this product.');
        }

        $product->photos()->where('is_primary', true)->update(['is_primary' => false]);
        $photo->update([
            'is_primary' => true,
            'image_type' => $photo->image_type === ProductPhoto::TYPE_PRIMARY ? ProductPhoto::TYPE_PRIMARY : $photo->image_type,
        ]);
        $this->flushProductCache();

        return $product->fresh(['vendor.user', 'photos', 'category', 'subcategory', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail']);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @param  array<int, array{image_type?: string, sort_order?: int}>  $metadata
     * @return array<int, ProductPhoto>
     */
    public function addPhotos(Product $product, array $files, array $metadata = []): array
    {
        $maxOrder = $product->photos()->max('sort_order') ?? 0;
        $hasPrimary = $product->photos()->where('is_primary', true)->exists();
        $photos = [];

        foreach ($files as $index => $file) {
            $path = $this->storeOptimizedPhoto($file, 'products/'.$product->id);
            $itemMetadata = $metadata[$index] ?? [];
            $imageType = $itemMetadata['image_type'] ?? ProductPhoto::TYPE_FRONT;
            $sortOrder = isset($itemMetadata['sort_order']) && $itemMetadata['sort_order'] > 0
                ? (int) $itemMetadata['sort_order']
                : ++$maxOrder;
            $shouldBecomePrimary = $imageType === ProductPhoto::TYPE_PRIMARY;

            if ($shouldBecomePrimary) {
                $product->photos()->where('is_primary', true)->update(['is_primary' => false]);
                $hasPrimary = false;
            }

            $photos[] = $product->photos()->create([
                'path' => $path,
                'image_type' => $imageType,
                'sort_order' => $sortOrder,
                'is_primary' => $shouldBecomePrimary || (! $hasPrimary && $index === 0),
            ]);

            if ($shouldBecomePrimary || (! $hasPrimary && $index === 0)) {
                $hasPrimary = true;
            }

            $maxOrder = max($maxOrder, $sortOrder);
        }

        $this->flushProductCache();

        return $photos;
    }

    /**
     * Store an uploaded product photo, downscaled and re-encoded by
     * ImageOptimizationService to keep bandwidth and page-weight down on
     * high-traffic product grids. Storage path/disk/filename convention
     * matches what UploadedFile::store() would have produced.
     */
    protected function storeOptimizedPhoto(UploadedFile $file, string $directory): string
    {
        return app(ImageOptimizationService::class)->storeOptimized($file, $directory);
    }

    /**
     * @param  array<int, array{id: int, image_type?: string, sort_order?: int}>  $updates
     */
    public function updatePhotoMetadata(Product $product, array $updates): void
    {
        foreach ($updates as $update) {
            $photoId = (int) ($update['id'] ?? 0);
            if ($photoId <= 0) {
                continue;
            }

            $photo = $product->photos()->whereKey($photoId)->first();
            if (! $photo instanceof ProductPhoto) {
                continue;
            }

            $attributes = [];

            if (isset($update['image_type'])) {
                $attributes['image_type'] = $update['image_type'];
            }

            if (isset($update['sort_order']) && (int) $update['sort_order'] > 0) {
                $attributes['sort_order'] = (int) $update['sort_order'];
            }

            if ($attributes !== []) {
                $photo->update($attributes);

                if (($attributes['image_type'] ?? null) === ProductPhoto::TYPE_PRIMARY) {
                    $product->photos()
                        ->whereKeyNot($photo->id)
                        ->where('is_primary', true)
                        ->update(['is_primary' => false]);

                    $photo->update(['is_primary' => true]);
                }
            }
        }

        $this->flushProductCache();
    }

    public function removePhoto(ProductPhoto $photo): void
    {
        Storage::disk('public')->delete($photo->path);
        $photo->delete();
        $this->flushProductCache();
    }

    /**
     * @param  array<int, int>  $photoIds
     */
    public function removePhotos(Product $product, array $photoIds): int
    {
        $photos = $product->photos()->whereIn('id', $photoIds)->get();
        $deletedPrimary = $photos->where('is_primary', true)->isNotEmpty();

        foreach ($photos as $photo) {
            Storage::disk('public')->delete($photo->path);
            $photo->delete();
        }

        if ($deletedPrimary) {
            $product->refresh();
            $remainingPhotos = $product->photos()->get();
            $firstRemaining = $remainingPhotos->firstWhere('image_type', ProductPhoto::TYPE_PRIMARY)
                ?? $remainingPhotos->sortBy('sort_order')->first();
            if ($firstRemaining instanceof ProductPhoto) {
                $firstRemaining->update(['is_primary' => true]);
            }
        }

        $this->flushProductCache();

        return $photos->count();
    }

    /**
     * Flush all product-related caches using Redis tags.
     */
    protected function flushProductCache(): void
    {
        Cache::forget(\App\Services\ApplicationCacheService::DASHBOARD_ADMIN_STATS);
        Cache::forget(\App\Services\ApplicationCacheService::ADMIN_DASHBOARD_LEGACY);

        try {
            Cache::tags(['products'])->flush();
        } catch (\Exception $e) {
            // Silently fail if cache driver doesn't support tags
        }
    }

    /**
     * Cache helper using tags with fallback.
     *
     * @param  array<int, string>  $tags
     */
    protected function cachedOrFetch(array $tags, string $key, int $ttl, \Closure $callback): mixed
    {
        try {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            return $callback();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     shared_detail: array<string, mixed>,
     *     agricultural_detail: array<string, mixed>,
     *     veterinary_detail: array<string, mixed>,
     *     shared_detail_present: bool,
     *     agricultural_detail_present: bool,
     *     veterinary_detail_present: bool
     * }
     */
    protected function extractDetailPayload(array &$data): array
    {
        $payload = [
            'shared_detail' => (array) ($data['shared_detail'] ?? []),
            'agricultural_detail' => (array) ($data['agricultural_detail'] ?? []),
            'veterinary_detail' => (array) ($data['veterinary_detail'] ?? []),
            'shared_detail_present' => array_key_exists('shared_detail', $data),
            'agricultural_detail_present' => array_key_exists('agricultural_detail', $data),
            'veterinary_detail_present' => array_key_exists('veterinary_detail', $data),
        ];

        unset($data['shared_detail'], $data['agricultural_detail'], $data['veterinary_detail']);

        return $payload;
    }

    /**
     * @param  array{
     *     shared_detail: array<string, mixed>,
     *     agricultural_detail: array<string, mixed>,
     *     veterinary_detail: array<string, mixed>,
     *     shared_detail_present: bool,
     *     agricultural_detail_present: bool,
     *     veterinary_detail_present: bool
     * }  $detailPayload
     */
    protected function syncProductDetails(Product $product, array $detailPayload, bool $allowNullUpdate = false): void
    {
        $sharedAttributes = $allowNullUpdate
            ? $this->prepareNullableAttributes(new SharedProductDetail, $detailPayload['shared_detail'], ['product_id'])
            : $this->filterNullValues($detailPayload['shared_detail']);
        $agriculturalAttributes = $allowNullUpdate
            ? $this->prepareNullableAttributes(new \App\Models\AgriculturalProductDetail, $detailPayload['agricultural_detail'], ['shared_product_detail_id'])
            : $this->filterNullValues($detailPayload['agricultural_detail']);
        $veterinaryAttributes = $allowNullUpdate
            ? $this->prepareNullableAttributes(new \App\Models\VeterinaryProductDetail, $detailPayload['veterinary_detail'], ['shared_product_detail_id'])
            : $this->filterNullValues($detailPayload['veterinary_detail']);

        if (isset($sharedAttributes['barcodes']) && is_array($sharedAttributes['barcodes'])) {
            $sharedAttributes['barcodes'] = collect($sharedAttributes['barcodes'])
                ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                ->map(fn (string $value): string => trim($value))
                ->values()
                ->all();
        }

        if (
            ! $allowNullUpdate
            && $sharedAttributes === []
            && $agriculturalAttributes === []
            && $veterinaryAttributes === []
        ) {
            return;
        }

        $shouldSyncShared = $allowNullUpdate
            ? ($detailPayload['shared_detail_present'] || $detailPayload['agricultural_detail_present'] || $detailPayload['veterinary_detail_present'])
            : ($sharedAttributes !== [] || $agriculturalAttributes !== [] || $veterinaryAttributes !== []);

        if (! $shouldSyncShared) {
            return;
        }

        /** @var SharedProductDetail $sharedDetail */
        $sharedDetail = $product->sharedDetail()->updateOrCreate(['product_id' => $product->id], $sharedAttributes);

        $categoryType = $product->category?->type
            ?? Category::query()->whereKey($product->category_id)->value('type');

        if ($categoryType === Category::TYPE_AGRICULTURE) {
            if ($allowNullUpdate ? $detailPayload['agricultural_detail_present'] : $agriculturalAttributes !== []) {
                $sharedDetail->agriculturalDetail()->updateOrCreate(
                    ['shared_product_detail_id' => $sharedDetail->id],
                    $agriculturalAttributes
                );
            }

            if ($sharedDetail->veterinaryDetail) {
                $sharedDetail->veterinaryDetail()->delete();
            }
        }

        if ($categoryType === Category::TYPE_VETERINARY) {
            if ($allowNullUpdate ? $detailPayload['veterinary_detail_present'] : $veterinaryAttributes !== []) {
                $sharedDetail->veterinaryDetail()->updateOrCreate(
                    ['shared_product_detail_id' => $sharedDetail->id],
                    $veterinaryAttributes
                );
            }

            if ($sharedDetail->agriculturalDetail) {
                $sharedDetail->agriculturalDetail()->delete();
            }
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function filterNullValues(array $attributes): array
    {
        return array_filter($attributes, function (mixed $value): bool {
            if ($value === null) {
                return false;
            }

            if (is_string($value) && trim($value) === '') {
                return false;
            }

            if (is_array($value) && $value === []) {
                return false;
            }

            return true;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $ignoredFillable
     * @return array<string, mixed>
     */
    protected function prepareNullableAttributes(Model $model, array $attributes, array $ignoredFillable = []): array
    {
        $normalized = [];

        foreach ($model->getFillable() as $field) {
            if (in_array($field, $ignoredFillable, true)) {
                continue;
            }

            if (! array_key_exists($field, $attributes)) {
                continue;
            }

            $value = $attributes[$field];

            if (is_string($value)) {
                $value = trim($value) === '' ? null : trim($value);
            }

            if (is_array($value) && $value === []) {
                $value = null;
            }

            $normalized[$field] = $value;
        }

        return $normalized;
    }
}
