<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\SharedProductDetail;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(?Vendor $vendor = null, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $vendor
            ? $vendor->products()
            : Product::query();

        $query->with([
            'photos' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->limit(3),
            'category:id,name,type,commission',
            'sharedDetail.agriculturalDetail',
            'sharedDetail.veterinaryDetail',
        ]);

        if (! $vendor && ! empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['category_type'])) {
            $query->whereHas('category', fn ($q) => $q->where('type', $filters['category_type']));
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['has_discount']) && $filters['has_discount'] !== '') {
            $wantDiscount = filter_var($filters['has_discount'], FILTER_VALIDATE_BOOLEAN);
            $query->where(function ($builder) use ($wantDiscount) {
                $activeDiscountQuery = fn ($q) => $q
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
        $categoryId = ! empty($filters['category_id']) ? (int) $filters['category_id'] : null;
        $categoryType = ! empty($filters['category_type']) ? (string) $filters['category_type'] : null;
        $hasDiscount = isset($filters['has_discount']) && $filters['has_discount'] !== ''
            ? filter_var($filters['has_discount'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;
        $sort = isset($filters['sort']) && in_array($filters['sort'], ['top_rated', 'best_selling', 'most_favorited'], true)
            ? $filters['sort'] : 'latest';
        $page = (int) request()->get('page', 1);

        $cacheKey = "pub_products:c{$categoryId}:ct{$categoryType}:d{$hasDiscount}:srt{$sort}:pp{$perPage}:p{$page}";

        return $this->cachedOrFetch(['products'], $cacheKey, 900, function () use ($perPage, $categoryId, $categoryType, $hasDiscount, $sort) {
            return $this->fetchPublicProducts($perPage, $categoryId, $categoryType, $hasDiscount, $sort);
        });
    }

    /**
     * @param  'latest'|'top_rated'|'best_selling'|'most_favorited'  $sort
     */
    protected function fetchPublicProducts(
        int $perPage,
        ?int $categoryId = null,
        ?string $categoryType = null,
        ?bool $hasDiscount = null,
        string $sort = 'latest'
    ): LengthAwarePaginator {
        $query = Product::query()
            ->visible()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->with([
                'photos' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->limit(1),
                'category:id,name,type,logo,icon',
                'vendor:id,store_name,user_id,logo,is_active,status',
                'sharedDetail.agriculturalDetail',
                'sharedDetail.veterinaryDetail',
            ]);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($categoryType) {
            $query->forCategoryType($categoryType);
        }

        if ($hasDiscount !== null) {
            if ($hasDiscount) {
                $query->where('discount_is_active', true)
                    ->where('discount_percentage', '>', 0)
                    ->where('discount_status', Product::DISCOUNT_STATUS_ACTIVE);
            } else {
                $query->where(function ($negativeQuery) {
                    $negativeQuery->where('discount_is_active', false)
                        ->orWhereNull('discount_percentage')
                        ->orWhere('discount_percentage', '<=', 0)
                        ->orWhere('discount_status', '!=', Product::DISCOUNT_STATUS_ACTIVE);
                });
            }
        }

        match ($sort) {
            'top_rated' => $query->having('reviews_count', '>=', 1)->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'),
            'best_selling' => $this->applyBestSellingOrder($query),
            'most_favorited' => $this->applyMostFavoritedOrder($query),
            default => $query->latest('products.created_at'),
        };

        return $query->paginate($perPage);
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

        return $product->load($vendor ? ['photos', 'category', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail'] : ['vendor.user', 'photos', 'category', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail']);
    }

    public function update(Product $product, array $data): Product
    {
        $detailPayload = $this->extractDetailPayload($data);
        $data['name'] = $data['name_ar'] ?? $data['name_en'] ?? $data['name'] ?? $product->getRawOriginal('name');

        $product->update($data);
        $this->syncProductDetails($product, $detailPayload);
        $this->flushProductCache();

        return $product->fresh($product->vendor ? ['vendor.user', 'photos', 'category', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail'] : ['photos', 'category', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail']);
    }

    public function delete(Product $product): void
    {
        $this->deleteDisplayAssets($product);

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

        return $product->fresh(['vendor.user', 'photos', 'category', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail']);
    }

    public function updateStatus(Product $product, string $status): Product
    {
        $product->update(['status' => $status]);
        $this->flushProductCache();

        return $product->fresh(['vendor.user', 'photos', 'category', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail']);
    }

    public function setPrimaryPhoto(Product $product, ProductPhoto $photo): Product
    {
        if ($photo->product_id !== $product->id) {
            throw new \InvalidArgumentException('Photo does not belong to this product.');
        }

        $product->photos()->where('is_primary', true)->update(['is_primary' => false]);
        $photo->update(['is_primary' => true]);
        $this->flushProductCache();

        return $product->fresh(['vendor.user', 'photos', 'category', 'sharedDetail.agriculturalDetail', 'sharedDetail.veterinaryDetail']);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, ProductPhoto>
     */
    public function addPhotos(Product $product, array $files): array
    {
        $maxOrder = $product->photos()->max('sort_order') ?? 0;
        $hasPrimary = $product->photos()->where('is_primary', true)->exists();
        $photos = [];

        foreach ($files as $index => $file) {
            $path = $file->store('products/'.$product->id, 'public');
            $photos[] = $product->photos()->create([
                'path' => $path,
                'sort_order' => ++$maxOrder,
                'is_primary' => ! $hasPrimary && $index === 0,
            ]);

            if (! $hasPrimary && $index === 0) {
                $hasPrimary = true;
            }
        }

        $this->flushProductCache();

        return $photos;
    }

    /**
     * @param  array<string, UploadedFile|null>  $files
     * @return array<string, string>
     */
    public function storeDisplayAssets(array $files): array
    {
        $paths = [];

        foreach (['icon', 'image'] as $field) {
            if (($files[$field] ?? null) instanceof UploadedFile) {
                $paths[$field] = $files[$field]->store('products/'.$field, 'public');
            }
        }

        return $paths;
    }

    public function replaceDisplayAssets(Product $product, array $files): array
    {
        return $this->storeDisplayAssets($files);
    }

    protected function deleteDisplayAssets(Product $product): void
    {
        foreach (['icon', 'image'] as $field) {
            if ($product->{$field}) {
                Storage::disk('public')->delete($product->{$field});
            }
        }
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
            $firstRemaining = $product->photos()->orderBy('sort_order')->first();
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
        Cache::forget('admin_dashboard_overview');

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
     * @return array{shared_detail: array<string, mixed>, agricultural_detail: array<string, mixed>, veterinary_detail: array<string, mixed>}
     */
    protected function extractDetailPayload(array &$data): array
    {
        $payload = [
            'shared_detail' => (array) ($data['shared_detail'] ?? []),
            'agricultural_detail' => (array) ($data['agricultural_detail'] ?? []),
            'veterinary_detail' => (array) ($data['veterinary_detail'] ?? []),
        ];

        unset($data['shared_detail'], $data['agricultural_detail'], $data['veterinary_detail']);

        return $payload;
    }

    /**
     * @param  array{shared_detail: array<string, mixed>, agricultural_detail: array<string, mixed>, veterinary_detail: array<string, mixed>}  $detailPayload
     */
    protected function syncProductDetails(Product $product, array $detailPayload): void
    {
        $sharedAttributes = $this->filterNullValues($detailPayload['shared_detail']);
        $agriculturalAttributes = $this->filterNullValues($detailPayload['agricultural_detail']);
        $veterinaryAttributes = $this->filterNullValues($detailPayload['veterinary_detail']);

        if ($sharedAttributes === [] && $agriculturalAttributes === [] && $veterinaryAttributes === []) {
            return;
        }

        /** @var SharedProductDetail $sharedDetail */
        $sharedDetail = $product->sharedDetail()->updateOrCreate(
            ['product_id' => $product->id],
            $sharedAttributes
        );

        $categoryType = $product->category?->type
            ?? Category::query()->whereKey($product->category_id)->value('type');

        if ($categoryType === Category::TYPE_AGRICULTURE) {
            $sharedDetail->agriculturalDetail()->updateOrCreate(
                ['shared_product_detail_id' => $sharedDetail->id],
                $agriculturalAttributes
            );

            if ($sharedDetail->veterinaryDetail) {
                $sharedDetail->veterinaryDetail()->delete();
            }
        }

        if ($categoryType === Category::TYPE_VETERINARY) {
            $sharedDetail->veterinaryDetail()->updateOrCreate(
                ['shared_product_detail_id' => $sharedDetail->id],
                $veterinaryAttributes
            );

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
}
