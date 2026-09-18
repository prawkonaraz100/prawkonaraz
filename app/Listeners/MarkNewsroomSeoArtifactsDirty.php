<?php

namespace App\Listeners;

use App\Events\ContentArticlePublicReadChanged;
use App\Events\ContentArticleWorkflowTransitioned;
use App\Events\ContentHomePlacementChanged;
use App\Support\NewsroomSeoArtifactRefreshCoordinator;

final class MarkNewsroomSeoArtifactsDirty
{
    public function __construct(
        private readonly NewsroomSeoArtifactRefreshCoordinator $coordinator,
    ) {}

    public function handle(
        ContentArticlePublicReadChanged|ContentArticleWorkflowTransitioned|ContentHomePlacementChanged $event,
    ): void {
        $this->coordinator->markDirty();
    }
}
