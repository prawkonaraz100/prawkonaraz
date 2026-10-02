<?php

namespace App\Exceptions;

use App\Models\User;
use RuntimeException;

class VerificationEmailDeliveryFailed extends RuntimeException
{
    public function __construct(public readonly User $user)
    {
        parent::__construct('Verification email delivery failed after creating the account.');
    }
}
