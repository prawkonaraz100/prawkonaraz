<?php

namespace App\Listeners;

use App\Events\ContentHomePlacementChanged;
use App\Support\NewsroomPublicReadCache;

final class InvalidateNewsroomHomeCacheOnPlacementChange
{
    public function __construct(
        private readonly NewsroomPublicReadCache $cache,
    ) {}

    public function handle(ContentHomePlacementChanged $event): void
    {
        $this->cache->invalidateHome();
    }
}
