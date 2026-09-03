<?php

namespace App\Support;

class SocialProviderUser
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $email,
        public readonly ?string $name = null,
        public readonly ?string $avatarUrl = null,
        public readonly ?bool $emailVerified = null,
    ) {
    }

    public function normalizedEmail(): ?string
    {
        return filled($this->email) ? strtolower(trim((string) $this->email)) : null;
    }
}
