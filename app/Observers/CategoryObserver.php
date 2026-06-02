<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\ApplicationCacheService;

class CategoryObserver
{
    public function __construct(protected ApplicationCacheService $cacheService) {}

    public function created(Category $category): void
    {
        $this->flush();
    }

    public function updated(Category $category): void
    {
        $this->flush();
    }

    public function deleted(Category $category): void
    {
        $this->flush();
    }

    public function restored(Category $category): void
    {
        $this->flush();
    }

    public function forceDeleted(Category $category): void
    {
        $this->flush();
    }

    protected function flush(): void
    {
        $this->cacheService->flushCategories();
        $this->cacheService->flushProducts();
        $this->cacheService->flushVendors();
    }
}
