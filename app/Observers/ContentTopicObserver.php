<?php

namespace App\Observers;

use App\Models\ContentTopic;
use App\Support\NewsroomSeoArtifactRefreshCoordinator;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final class ContentTopicObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly NewsroomSeoArtifactRefreshCoordinator $coordinator,
    ) {}

    public function saved(ContentTopic $topic): void
    {
        if ($topic->published_at === null && ! $topic->wasChanged(['status', 'published_at'])) {
            return;
        }

        $this->coordinator->markDirty();
    }

    public function deleted(ContentTopic $topic): void
    {
        if ($topic->published_at === null) {
            return;
        }

        $this->coordinator->markDirty();
    }
}
