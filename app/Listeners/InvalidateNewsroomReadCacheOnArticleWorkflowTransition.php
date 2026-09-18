<?php

namespace App\Listeners;

use App\Enums\ContentArticleWorkflowStatus;
use App\Events\ContentArticleWorkflowTransitioned;
use App\Support\NewsroomPublicReadCache;

final class InvalidateNewsroomReadCacheOnArticleWorkflowTransition
{
    public function __construct(
        private readonly NewsroomPublicReadCache $cache,
    ) {}

    public function handle(ContentArticleWorkflowTransitioned $event): void
    {
        $published = ContentArticleWorkflowStatus::Published->value;

        if ($event->fromStatus !== $published && $event->toStatus !== $published) {
            return;
        }

        $this->cache->invalidateAll();
    }
}
