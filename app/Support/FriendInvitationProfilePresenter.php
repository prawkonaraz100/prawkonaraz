<?php

namespace App\Support;

use App\Models\FriendInvitation;
use App\Models\User;

class FriendInvitationProfilePresenter
{
    public function __construct(
        protected FriendInvitationEligibilityService $eligibility,
        protected FriendInvitationService $friendInvitationService,
    ) {}

    /**
     * @param  array<string, mixed>|null  $generated
     * @return array<string, mixed>
     */
    public function forOwner(User $owner, ?array $generated = null): array
    {
        $now = now();
        $enabled = $this->eligibility->isAvailable();

        $this->friendInvitationService->expireStaleForOwner($owner, $now);

        $ownerGrant = $this->eligibility->eligibleOwnerGrant($owner, $now);
        $activeGuest = $this->eligibility->activeAcceptedInvitationForOwner($owner, $now);
        $pending = $owner->ownedFriendInvitations()
            ->where('status', FriendInvitation::STATUS_PENDING)
            ->where('expires_at', '>', $now)
            ->latest('created_at')
            ->get();

        $reason = null;

        if (! $enabled) {
            $reason = 'access_open';
        } elseif ($ownerGrant === null) {
            $reason = 'plan_not_eligible';
        } elseif ($activeGuest !== null) {
            $reason = 'slot_taken';
        } elseif ($pending->count() >= FriendInvitationEligibilityService::PENDING_LIMIT) {
            $reason = 'pending_limit_reached';
        }

        return [
            'enabled' => $enabled,
            'eligible' => $ownerGrant !== null,
            'can_issue' => $enabled && $reason === null,
            'reason' => $reason,
            'reason_label' => $this->reasonLabel($reason),
            'pending_limit' => FriendInvitationEligibilityService::PENDING_LIMIT,
            'pending_count' => $pending->count(),
            'owner_access_expires_at' => $ownerGrant?->expires_at?->toIso8601String(),
            'plan' => [
                'code' => $ownerGrant?->purchaseOrder?->productPlan?->code,
                'name' => $ownerGrant?->purchaseOrder?->productPlan?->name,
            ],
            'active_guest' => $activeGuest ? [
                'public_id' => $activeGuest->public_id,
                'name' => $activeGuest->acceptedBy?->name,
                'email' => $activeGuest->acceptedBy?->email,
                'access_expires_at' => $activeGuest->guest_access_expires_at?->toIso8601String(),
                'accepted_at' => $activeGuest->accepted_at?->toIso8601String(),
            ] : null,
            'pending' => $pending
                ->map(fn (FriendInvitation $invitation): array => [
                    'public_id' => $invitation->public_id,
                    'created_at' => $invitation->created_at?->toIso8601String(),
                    'expires_at' => $invitation->expires_at?->toIso8601String(),
                    'display_code_last4' => $invitation->display_code_last4,
                ])
                ->values()
                ->all(),
            'generated' => $generated,
        ];
    }

    protected function reasonLabel(?string $reason): ?string
    {
        return match ($reason) {
            'access_open' => 'Dostęp do platformy jest obecnie otwarty. Zaproszenie nie jest potrzebne.',
            'plan_not_eligible' => 'Zaproszenia są dostępne tylko w planach 3 miesiące i rok.',
            'slot_taken' => 'Masz już aktywnego gościa. Slot zwolni się po końcu jego dostępu albo po zakupie własnego planu przez gościa.',
            'pending_limit_reached' => 'Osiągnąłeś limit oczekujących zaproszeń.',
            default => null,
        };
    }
}
