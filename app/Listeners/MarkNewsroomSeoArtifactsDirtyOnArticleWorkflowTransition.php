<?php

namespace App\Listeners;

use App\Events\ContentArticleWorkflowTransitioned;
use App\Support\NewsroomSeoArtifactRefreshCoordinator;

final class MarkNewsroomSeoArtifactsDirtyOnArticleWorkflowTransition
{
    public function __construct(
        private readonly NewsroomSeoArtifactRefreshCoordinator $coordinator,
    ) {}

    public function handle(ContentArticleWorkflowTransitioned $event): void
    {
        $this->coordinator->markDirty();
    }
}
