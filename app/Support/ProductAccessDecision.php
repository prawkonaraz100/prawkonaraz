<?php

namespace App\Support;

use App\Models\ProductAccessGrant;

final readonly class ProductAccessDecision
{
    public function __construct(
        public bool $allowed,
        public ?string $source = null,
        public ?ProductAccessGrant $grant = null,
        public ?string $reason = null,
    ) {}

    public static function allow(string $source, ?ProductAccessGrant $grant = null): self
    {
        return new self(true, $source, $grant);
    }

    public static function deny(string $reason): self
    {
        return new self(false, reason: $reason);
    }
}
