<?php

namespace App\Observers;

use App\Models\ContentAuthor;
use App\Support\NewsroomSeoArtifactRefreshCoordinator;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final class ContentAuthorObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly NewsroomSeoArtifactRefreshCoordinator $coordinator,
    ) {}

    public function saved(ContentAuthor $author): void
    {
        if (! $author->isPubliclyVisible() && ! $author->wasChanged(['is_published', 'published_at'])) {
            return;
        }

        $this->coordinator->markDirty();
    }

    public function deleted(ContentAuthor $author): void
    {
        if (! $author->isPubliclyVisible()) {
            return;
        }

        $this->coordinator->markDirty();
    }
}
