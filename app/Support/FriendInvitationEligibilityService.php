<?php

namespace App\Support;

use App\Models\FriendInvitation;
use App\Models\ProductAccessGrant;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class FriendInvitationEligibilityService
{
    public const PENDING_LIMIT = 10;

    /**
     * @var list<string>
     */
    public const ELIGIBLE_PLAN_CODES = ['start-90', 'start-365'];

    public function __construct(
        protected ProductAccessResolver $productAccessResolver,
    ) {}

    public function eligibleOwnerGrant(User $owner, ?CarbonInterface $now = null, bool $lock = false): ?ProductAccessGrant
    {
        $now ??= now();

        $query = $owner->productAccessGrants()
            ->with('purchaseOrder.productPlan')
            ->where('source', ProductAccessGrant::SOURCE_PURCHASE)
            ->where('status', ProductAccessGrant::STATUS_ACTIVE)
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            })
            ->whereHas('purchaseOrder.productPlan', function (Builder $query): void {
                $query->whereIn('code', self::ELIGIBLE_PLAN_CODES);
            })
            ->latest('expires_at')
            ->latest('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function activeAcceptedInvitationForOwner(
        User $owner,
        ?CarbonInterface $now = null,
        bool $lock = false,
    ): ?FriendInvitation {
        $now ??= now();

        $query = $owner->ownedFriendInvitations()
            ->where('status', FriendInvitation::STATUS_ACCEPTED)
            ->where('guest_access_expires_at', '>', $now)
            ->latest('accepted_at')
            ->latest('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function activeGuestInvitationForUser(
        User $guest,
        ?CarbonInterface $now = null,
        bool $lock = false,
    ): ?FriendInvitation {
        $now ??= now();

        $query = $guest->acceptedFriendInvitations()
            ->where('status', FriendInvitation::STATUS_ACCEPTED)
            ->where('guest_access_expires_at', '>', $now)
            ->latest('accepted_at')
            ->latest('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function pendingCount(User $owner, ?CarbonInterface $now = null): int
    {
        $now ??= now();

        return (int) $owner->ownedFriendInvitations()
            ->where('status', FriendInvitation::STATUS_PENDING)
            ->where('expires_at', '>', $now)
            ->count();
    }

    public function assertCanCreateInvitation(User $owner, ?CarbonInterface $now = null): ProductAccessGrant
    {
        $now ??= now();

        $ownerGrant = $this->eligibleOwnerGrant($owner, $now, lock: true);

        if ($ownerGrant === null) {
            throw ValidationException::withMessages([
                'invitation' => 'Ten plan nie daje możliwości zaproszenia znajomego.',
            ]);
        }

        if ($this->activeAcceptedInvitationForOwner($owner, $now, lock: true) !== null) {
            throw ValidationException::withMessages([
                'invitation' => 'Masz już aktywnego gościa. Kolejne zaproszenie będzie możliwe po zwolnieniu slotu.',
            ]);
        }

        if ($this->pendingCount($owner, $now) >= self::PENDING_LIMIT) {
            throw ValidationException::withMessages([
                'invitation' => 'Osiągnąłeś limit oczekujących zaproszeń (maksymalnie 10). Poczekaj, aż znajomy zaakceptuje jedno z nich, lub unieważnij stare zaproszenie w panelu, aby móc wygenerować nowe.',
            ]);
        }

        return $ownerGrant;
    }

    public function assertGuestCanAccept(User $guest, User $owner, ?CarbonInterface $now = null): void
    {
        $now ??= now();

        if ((int) $guest->getKey() === (int) $owner->getKey()) {
            throw ValidationException::withMessages([
                'invitation' => 'Nie możesz przyjąć własnego zaproszenia.',
            ]);
        }

        if ($guest->isBanned()) {
            throw ValidationException::withMessages([
                'invitation' => 'To konto zostało zablokowane.',
            ]);
        }

        if ($guest->isUnclaimedTemporaryAccount()) {
            throw ValidationException::withMessages([
                'invitation' => 'Najpierw przejmij konto tymczasowe.',
            ]);
        }

        if ($guest->mustChangePassword()) {
            throw ValidationException::withMessages([
                'invitation' => 'Najpierw ustaw nowe hasło do konta.',
            ]);
        }

        if (! $guest->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'invitation' => 'Najpierw potwierdź adres e-mail.',
            ]);
        }

        $decision = $this->productAccessResolver->forUser($guest, $now);

        if ($decision->allowed) {
            throw ValidationException::withMessages([
                'invitation' => 'Posiadasz już aktywny dostęp Premium. Nie możesz przyjąć tego zaproszenia.',
            ]);
        }
    }
}
