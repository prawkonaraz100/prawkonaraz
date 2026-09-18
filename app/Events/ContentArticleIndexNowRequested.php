<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class ContentArticleIndexNowRequested implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $articleId,
        public readonly string $path,
        public readonly string $eventType,
        public readonly string $source,
    ) {}
}
