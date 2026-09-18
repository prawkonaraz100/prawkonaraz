<?php

namespace App\Observers;

use App\Models\ContentCategory;
use App\Support\NewsroomPublicReadCache;
use App\Support\NewsroomSeoArtifactRefreshCoordinator;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final class ContentCategoryObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly NewsroomPublicReadCache $cache,
        private readonly NewsroomSeoArtifactRefreshCoordinator $seoArtifacts,
    ) {}

    public function saved(ContentCategory $category): void
    {
        $this->cache->invalidateAll();
        $this->seoArtifacts->markDirty();
    }

    public function deleted(ContentCategory $category): void
    {
        $this->cache->invalidateAll();
        $this->seoArtifacts->markDirty();
    }
}
