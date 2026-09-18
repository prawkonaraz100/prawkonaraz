<?php

namespace App\Listeners;

use App\Events\ContentArticlePublicReadChanged;
use App\Support\NewsroomSeoArtifactRefreshCoordinator;

final class MarkNewsroomSeoArtifactsDirtyOnPublicArticleChange
{
    public function __construct(
        private readonly NewsroomSeoArtifactRefreshCoordinator $coordinator,
    ) {}

    public function handle(ContentArticlePublicReadChanged $event): void
    {
        $this->coordinator->markDirty();
    }
}
