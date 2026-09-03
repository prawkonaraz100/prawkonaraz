<?php

namespace App\Support;

use Illuminate\Support\Collection;

class PublicQuestionSearchResult
{
    /**
     * @param  Collection<int, array<string, mixed>>  $items
     */
    public function __construct(
        public readonly string $query,
        public readonly string $normalizedQuery,
        public readonly Collection $items,
        public readonly ?string $redirectUrl = null,
    ) {}

    public function hasQuery(): bool
    {
        return $this->query !== '';
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }
}
