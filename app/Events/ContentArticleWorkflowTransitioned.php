<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class ContentArticleWorkflowTransitioned implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $articleId,
        public readonly string $action,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly string $trigger,
    ) {}
}
