<?php

namespace App\Support;

use App\Models\FriendInvitation;

final readonly class FriendInvitationIssueResult
{
    public function __construct(
        public FriendInvitation $invitation,
        public string $token,
        public string $code,
    ) {}
}
