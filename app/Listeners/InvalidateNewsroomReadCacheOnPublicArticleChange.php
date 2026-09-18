<?php

namespace App\Listeners;

use App\Events\ContentArticlePublicReadChanged;
use App\Support\NewsroomPublicReadCache;

final class InvalidateNewsroomReadCacheOnPublicArticleChange
{
    public function __construct(
        private readonly NewsroomPublicReadCache $cache,
    ) {}

    public function handle(ContentArticlePublicReadChanged $event): void
    {
        $this->cache->invalidateAll();
    }
}
