<?php

namespace App\Observers;

use App\Models\ContentCategory;
use App\Support\NewsroomPublicReadCache;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final class ContentCategoryObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly NewsroomPublicReadCache $cache,
    ) {}

    public function saved(ContentCategory $category): void
    {
        $this->cache->invalidateAll();
    }

    public function deleted(ContentCategory $category): void
    {
        $this->cache->invalidateAll();
    }
}
