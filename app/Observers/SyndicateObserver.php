<?php

namespace App\Observers;

use App\Models\Syndicate;
use App\Services\ApplicationCacheService;

class SyndicateObserver
{
    public function __construct(protected ApplicationCacheService $cacheService) {}

    public function created(Syndicate $syndicate): void
    {
        $this->cacheService->flushSyndicates();
    }

    public function updated(Syndicate $syndicate): void
    {
        $this->cacheService->flushSyndicates();
    }

    public function deleted(Syndicate $syndicate): void
    {
        $this->cacheService->flushSyndicates();
    }
}
