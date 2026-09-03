<?php

namespace App\Support;

use App\Models\ProductAccessGrant;
use App\Models\User;
use Carbon\CarbonInterface;

class ProductAccessResolver
{
    public const SOURCE_GLOBAL_FREE_MODE = 'global_free_mode';

    public function __construct(
        protected PaymentRequirementService $paymentRequirementService,
    ) {}

    public function forUser(User $user, ?CarbonInterface $now = null): ProductAccessDecision
    {
        $now ??= now();

        if ($user->isBanned()) {
            return ProductAccessDecision::deny('banned');
        }

        if ($user->isUnclaimedTemporaryAccount()) {
            return ProductAccessDecision::deny('temporary_account_unclaimed');
        }

        if ($user->mustChangePassword()) {
            return ProductAccessDecision::deny('password_change_required');
        }

        if ($user->hasSystemProductAccess()) {
            return ProductAccessDecision::allow(ProductAccessGrant::SOURCE_SYSTEM);
        }

        if (! $this->paymentRequirementService->requiresPayment() && $user->hasVerifiedEmail()) {
            return ProductAccessDecision::allow(self::SOURCE_GLOBAL_FREE_MODE);
        }

        $purchaseGrant = $this->activeGrantForSource($user, ProductAccessGrant::SOURCE_PURCHASE, $now);

        if ($purchaseGrant !== null) {
            return ProductAccessDecision::allow(ProductAccessGrant::SOURCE_PURCHASE, $purchaseGrant);
        }

        $moderatorGrant = $this->activeGrantForSource($user, ProductAccessGrant::SOURCE_MODERATOR_GRANT, $now);

        if ($moderatorGrant !== null) {
            return ProductAccessDecision::allow(ProductAccessGrant::SOURCE_MODERATOR_GRANT, $moderatorGrant);
        }

        $invitationGuestGrant = $this->activeGrantForSource($user, ProductAccessGrant::SOURCE_INVITATION_GUEST, $now);

        if ($invitationGuestGrant !== null) {
            return ProductAccessDecision::allow(ProductAccessGrant::SOURCE_INVITATION_GUEST, $invitationGuestGrant);
        }

        return ProductAccessDecision::deny('missing_access');
    }

    protected function activeGrantForSource(User $user, string $source, CarbonInterface $now): ?ProductAccessGrant
    {
        return $user->productAccessGrants()
            ->where('source', $source)
            ->where('status', ProductAccessGrant::STATUS_ACTIVE)
            ->whereNull('revoked_at')
            ->where(function ($query) use ($now): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            })
            ->latest('created_at')
            ->first();
    }
}
