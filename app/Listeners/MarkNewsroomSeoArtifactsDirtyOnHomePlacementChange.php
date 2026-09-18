<?php

namespace App\Listeners;

use App\Events\ContentHomePlacementChanged;
use App\Support\NewsroomSeoArtifactRefreshCoordinator;

final class MarkNewsroomSeoArtifactsDirtyOnHomePlacementChange
{
    public function __construct(
        private readonly NewsroomSeoArtifactRefreshCoordinator $coordinator,
    ) {}

    public function handle(ContentHomePlacementChanged $event): void
    {
        $this->coordinator->markDirty();
    }
}
