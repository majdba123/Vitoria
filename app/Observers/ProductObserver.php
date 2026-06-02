<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\ApplicationCacheService;

class ProductObserver
{
    public function __construct(protected ApplicationCacheService $cacheService) {}

    public function created(Product $product): void
    {
        $this->cacheService->flushProducts();
    }

    public function updated(Product $product): void
    {
        $this->cacheService->flushProducts();
    }

    public function deleted(Product $product): void
    {
        $this->cacheService->flushProducts();
    }

    public function restored(Product $product): void
    {
        $this->cacheService->flushProducts();
    }

    public function forceDeleted(Product $product): void
    {
        $this->cacheService->flushProducts();
    }
}
