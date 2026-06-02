<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPhoto;
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
            'subcategory:id,name,category_id',
            'subcategory.category:id,name,type,commission',
        ]);

        if (! $vendor && ! empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->whereHas('subcategory', fn ($q) => $q->where('category_id', $filters['category_id']));
        }

        if (! empty($filters['category_type'])) {
            $query->whereHas('subcategory.category', fn ($q) => $q->where('type', $filters['category_type']));
        }

        if (! empty($filters['subcategory_id'])) {
            $query->where('subcategory_id', $filters['subcategory_id']);
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
        $subcategoryId = ! empty($filters['subcategory_id']) ? (int) $filters['subcategory_id'] : null;
        $hasDiscount = isset($filters['has_discount']) && $filters['has_discount'] !== ''
            ? filter_var($filters['has_discount'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;
        $sort = isset($filters['sort']) && in_array($filters['sort'], ['top_rated', 'best_selling', 'most_favorited'], true)
            ? $filters['sort'] : 'latest';
        $page = (int) request()->get('page', 1);

        $cacheKey = "pub_products:c{$categoryId}:ct{$categoryType}:s{$subcategoryId}:d{$hasDiscount}:srt{$sort}:pp{$perPage}:p{$page}";

        return $this->cachedOrFetch(['products'], $cacheKey, 900, function () use ($perPage, $categoryId, $categoryType, $subcategoryId, $hasDiscount, $sort) {
            return $this->fetchPublicProducts($perPage, $categoryId, $categoryType, $subcategoryId, $hasDiscount, $sort);
        });
    }

    /**
     * @param  'latest'|'top_rated'|'best_selling'|'most_favorited'  $sort
     */
    protected function fetchPublicProducts(
        int $perPage,
        ?int $categoryId = null,
        ?string $categoryType = null,
        ?int $subcategoryId = null,
        ?bool $hasDiscount = null,
        string $sort = 'latest'
    ): LengthAwarePaginator {
        $query = Product::query()
            ->where('is_active', true)
            ->where('status', Product::STATUS_APPROVED)
            ->where('quantity', '>', 0)
            ->whereHas('vendor', fn ($q) => $q->where('is_active', true))
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->with([
                'photos' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->limit(1),
            ]);

        if ($subcategoryId) {
            $query->where('subcategory_id', $subcategoryId);
        } elseif ($categoryId) {
            $query->whereHas('subcategory', fn ($q) => $q->where('category_id', $categoryId));
        }

        if ($categoryType) {
            $query->whereHas('subcategory.category', fn ($q) => $q->where('type', $categoryType));
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
        if (! isset($data['status'])) {
            $data['status'] = Product::STATUS_PENDING;
        }

        $product = $vendor
            ? $vendor->products()->create($data)
            : Product::query()->create($data);

        $this->flushProductCache();

        return $product->load($vendor ? ['photos', 'subcategory.category'] : ['vendor.user', 'photos', 'subcategory.category']);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        $this->flushProductCache();

        return $product->fresh($product->vendor ? ['vendor.user', 'photos', 'subcategory.category'] : ['photos', 'subcategory.category']);
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

        return $product->fresh(['vendor.user', 'photos', 'subcategory.category']);
    }

    public function updateStatus(Product $product, string $status): Product
    {
        $product->update(['status' => $status]);
        $this->flushProductCache();

        return $product->fresh(['vendor.user', 'photos', 'subcategory.category']);
    }

    public function setPrimaryPhoto(Product $product, ProductPhoto $photo): Product
    {
        if ($photo->product_id !== $product->id) {
            throw new \InvalidArgumentException('Photo does not belong to this product.');
        }

        $product->photos()->where('is_primary', true)->update(['is_primary' => false]);
        $photo->update(['is_primary' => true]);
        $this->flushProductCache();

        return $product->fresh(['vendor.user', 'photos', 'subcategory.category']);
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
}
