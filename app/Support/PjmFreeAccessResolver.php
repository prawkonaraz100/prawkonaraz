<?php

namespace App\Support;

use App\Models\User;

class PjmFreeAccessResolver
{
    public const SOURCE_SYSTEM = 'system';

    public function forUser(User $user): PjmFreeAccessDecision
    {
        if ($user->isBanned()) {
            return PjmFreeAccessDecision::deny('banned');
        }

        if ($user->isUnclaimedTemporaryAccount()) {
            return PjmFreeAccessDecision::deny('temporary_account_unclaimed');
        }

        if ($user->mustChangePassword()) {
            return PjmFreeAccessDecision::deny('password_change_required');
        }

        if ($user->hasSystemProductAccess()) {
            return PjmFreeAccessDecision::allow(self::SOURCE_SYSTEM);
        }

        return PjmFreeAccessDecision::deny('pjm_module_suspended');
    }
}
