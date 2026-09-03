<?php

namespace App\Support;

final readonly class PjmFreeAccessDecision
{
    public function __construct(
        public bool $allowed,
        public ?string $source = null,
        public ?string $reason = null,
    ) {}

    public static function allow(string $source): self
    {
        return new self(true, source: $source);
    }

    public static function deny(string $reason): self
    {
        return new self(false, reason: $reason);
    }
}
