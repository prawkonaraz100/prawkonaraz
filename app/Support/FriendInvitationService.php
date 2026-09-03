<?php

namespace App\Support;

use App\Models\FriendInvitation;
use App\Models\ProductAccessGrant;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Notifications\FriendInvitationGuestAccessShortenedAfterRefund;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FriendInvitationService
{
    private const CODE_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public function __construct(
        protected FriendInvitationEligibilityService $eligibility,
        protected AuditLogService $auditLogService,
    ) {}

    public function issue(User $owner, ?CarbonInterface $now = null): FriendInvitationIssueResult
    {
        $now ??= now();

        return DB::transaction(function () use ($owner, $now): FriendInvitationIssueResult {
            $lockedOwner = User::query()
                ->whereKey($owner->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->expireStaleForOwner($lockedOwner, $now);

            $ownerGrant = $this->eligibility->assertCanCreateInvitation($lockedOwner, $now);
            $token = $this->uniqueToken();
            $code = $this->uniqueCode();

            $invitation = FriendInvitation::query()->create([
                'public_id' => (string) Str::ulid(),
                'owner_user_id' => $lockedOwner->getKey(),
                'owner_product_access_grant_id' => $ownerGrant->getKey(),
                'owner_purchase_order_id' => $ownerGrant->purchase_order_id,
                'product_plan_id' => $ownerGrant->purchaseOrder?->product_plan_id,
                'status' => FriendInvitation::STATUS_PENDING,
                'token_hash' => $this->hashToken($token),
                'code_hash' => $this->hashCode($code),
                'display_code_last4' => substr($code, -4),
                'expires_at' => $now->copy()->addDays(14),
                'metadata' => [
                    'owner_access_expires_at' => $ownerGrant->expires_at,
                    'plan_code' => $ownerGrant->purchaseOrder?->productPlan?->code,
                ],
            ]);

            $this->auditLogService->record(
                'friend_invitation.created',
                'friend_invitation',
                (string) $invitation->getKey(),
                $lockedOwner,
                [
                    'public_id' => $invitation->public_id,
                    'owner_product_access_grant_id' => $ownerGrant->getKey(),
                    'expires_at' => $invitation->expires_at,
                ],
            );

            return new FriendInvitationIssueResult($invitation, $token, $code);
        });
    }

    public function accept(FriendInvitation $invitation, User $guest, ?CarbonInterface $now = null): FriendInvitation
    {
        $now ??= now();

        return DB::transaction(function () use ($invitation, $guest, $now): FriendInvitation {
            $lockedInvitation = FriendInvitation::query()
                ->whereKey($invitation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $owner = User::query()
                ->whereKey($lockedInvitation->owner_user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedGuest = User::query()
                ->whereKey($guest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->expireStaleForGuest($lockedGuest, $now, lock: true);

            if ($lockedInvitation->status !== FriendInvitation::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'invitation' => 'Niestety, to zaproszenie jest już nieaktywne. Slot został zajęty przez innego znajomego.',
                ]);
            }

            if ($lockedInvitation->expires_at->lessThanOrEqualTo($now)) {
                $this->markExpired($lockedInvitation, $now);

                throw ValidationException::withMessages([
                    'invitation' => 'To zaproszenie wygasło. Poproś znajomego o wygenerowanie nowego linku lub kodu.',
                ]);
            }

            $ownerGrant = ProductAccessGrant::query()
                ->with('purchaseOrder.productPlan')
                ->whereKey($lockedInvitation->owner_product_access_grant_id)
                ->lockForUpdate()
                ->first();

            if ($ownerGrant === null || ! $this->ownerGrantCanStillInvite($ownerGrant, $now)) {
                $this->revokeOwnerPendingInvitations(
                    $owner,
                    FriendInvitation::REVOKED_REASON_OWNER_ACCESS_INACTIVE,
                    $now,
                );

                throw ValidationException::withMessages([
                    'invitation' => 'To zaproszenie nie jest już aktywne.',
                ]);
            }

            if ($this->eligibility->activeAcceptedInvitationForOwner($owner, $now, lock: true) !== null) {
                $this->markRevoked($lockedInvitation, FriendInvitation::REVOKED_REASON_SLOT_TAKEN, $now);

                throw ValidationException::withMessages([
                    'invitation' => 'Niestety, to zaproszenie jest już nieaktywne. Slot został zajęty przez innego znajomego.',
                ]);
            }

            $this->eligibility->assertGuestCanAccept($lockedGuest, $owner, $now);

            if ($ownerGrant->expires_at === null || $ownerGrant->expires_at->lessThanOrEqualTo($now)) {
                $this->markRevoked($lockedInvitation, FriendInvitation::REVOKED_REASON_OWNER_ACCESS_INACTIVE, $now);

                throw ValidationException::withMessages([
                    'invitation' => 'To zaproszenie nie jest już aktywne.',
                ]);
            }

            $guestGrant = ProductAccessGrant::query()->create([
                'user_id' => $lockedGuest->getKey(),
                'source' => ProductAccessGrant::SOURCE_INVITATION_GUEST,
                'status' => ProductAccessGrant::STATUS_ACTIVE,
                'starts_at' => $now,
                'expires_at' => $ownerGrant->expires_at,
                'granted_by_user_id' => $owner->getKey(),
                'notes' => 'Dostęp aktywowany przez zaproszenie znajomego.',
            ]);

            $lockedInvitation->forceFill([
                'status' => FriendInvitation::STATUS_ACCEPTED,
                'accepted_at' => $now,
                'accepted_by_user_id' => $lockedGuest->getKey(),
                'guest_product_access_grant_id' => $guestGrant->getKey(),
                'guest_access_starts_at' => $now,
                'guest_access_expires_at' => $ownerGrant->expires_at,
            ])->save();

            $this->revokeOtherPendingInvitations($lockedInvitation, $now);

            $this->auditLogService->record(
                'friend_invitation.accepted',
                'friend_invitation',
                (string) $lockedInvitation->getKey(),
                $lockedGuest,
                [
                    'public_id' => $lockedInvitation->public_id,
                    'owner_user_id' => $owner->getKey(),
                    'guest_product_access_grant_id' => $guestGrant->getKey(),
                    'guest_access_expires_at' => $ownerGrant->expires_at,
                ],
            );

            return $lockedInvitation->fresh([
                'owner',
                'acceptedBy',
                'guestProductAccessGrant',
            ]) ?? $lockedInvitation;
        });
    }

    public function convertGuestToPurchase(
        User $guest,
        ProductAccessGrant $purchaseGrant,
        ?User $actor = null,
        ?CarbonInterface $now = null,
    ): int {
        $now ??= now();

        return DB::transaction(function () use ($guest, $purchaseGrant, $actor, $now): int {
            $lockedGuest = User::query()
                ->whereKey($guest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $invitations = $lockedGuest->acceptedFriendInvitations()
                ->where('status', FriendInvitation::STATUS_ACCEPTED)
                ->where('guest_access_expires_at', '>', $now)
                ->lockForUpdate()
                ->get();

            foreach ($invitations as $invitation) {
                $guestGrant = $invitation->guestProductAccessGrant()
                    ->lockForUpdate()
                    ->first();

                if ($guestGrant instanceof ProductAccessGrant && $guestGrant->isCurrentlyActive($now)) {
                    $guestGrant->forceFill([
                        'status' => ProductAccessGrant::STATUS_REVOKED,
                        'revoked_at' => $now,
                        'notes' => trim(((string) $guestGrant->notes)."\nDostęp gościa zakończony po zakupie własnego planu."),
                    ])->save();
                }

                $invitation->forceFill([
                    'status' => FriendInvitation::STATUS_CONVERTED,
                    'converted_at' => $now,
                    'converted_purchase_order_id' => $purchaseGrant->purchase_order_id,
                ])->save();

                $this->auditLogService->record(
                    'friend_invitation.converted_to_purchase',
                    'friend_invitation',
                    (string) $invitation->getKey(),
                    $actor,
                    [
                        'guest_user_id' => $lockedGuest->getKey(),
                        'purchase_order_id' => $purchaseGrant->purchase_order_id,
                        'purchase_grant_id' => $purchaseGrant->getKey(),
                    ],
                );
            }

            return $invitations->count();
        });
    }

    /**
     * @return array{pending_revoked: int, guest_access_shortened: int}
     */
    public function applyOwnerRefundEffects(
        PurchaseOrder $order,
        ?User $actor = null,
        ?CarbonInterface $now = null,
    ): array {
        $now ??= now();

        return DB::transaction(function () use ($order, $actor, $now): array {
            $lockedOrder = PurchaseOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $pendingRevoked = 0;
            $guestAccessShortened = 0;

            FriendInvitation::query()
                ->where('owner_purchase_order_id', $lockedOrder->getKey())
                ->where('status', FriendInvitation::STATUS_PENDING)
                ->lockForUpdate()
                ->get()
                ->each(function (FriendInvitation $invitation) use ($actor, $now, &$pendingRevoked): void {
                    $this->markRevoked($invitation, FriendInvitation::REVOKED_REASON_REFUND, $now);
                    $pendingRevoked++;

                    $this->auditLogService->record(
                        'friend_invitation.revoked_after_refund',
                        'friend_invitation',
                        (string) $invitation->getKey(),
                        $actor,
                        [
                            'public_id' => $invitation->public_id,
                            'reason' => FriendInvitation::REVOKED_REASON_REFUND,
                        ],
                    );
                });

            FriendInvitation::query()
                ->with(['acceptedBy', 'guestProductAccessGrant'])
                ->where('owner_purchase_order_id', $lockedOrder->getKey())
                ->where('status', FriendInvitation::STATUS_ACCEPTED)
                ->lockForUpdate()
                ->get()
                ->each(function (FriendInvitation $invitation) use ($actor, $now, &$guestAccessShortened): void {
                    if ($invitation->guest_access_expires_at?->lessThanOrEqualTo($now)) {
                        $this->expireAcceptedInvitation($invitation, $now);

                        return;
                    }

                    $bufferExpiresAt = $now->copy()->addDays(7);
                    $newGuestAccessExpiresAt = $invitation->guest_access_expires_at?->lessThan($bufferExpiresAt)
                        ? $invitation->guest_access_expires_at
                        : $bufferExpiresAt;

                    $guestGrant = $invitation->guestProductAccessGrant;

                    if ($guestGrant instanceof ProductAccessGrant
                        && $guestGrant->status === ProductAccessGrant::STATUS_ACTIVE
                        && $guestGrant->revoked_at === null) {
                        $guestGrant->forceFill([
                            'expires_at' => $newGuestAccessExpiresAt,
                            'notes' => trim(((string) $guestGrant->notes)."\nDostęp gościa skrócony po refundzie planu właściciela."),
                        ])->save();
                    }

                    $invitation->forceFill([
                        'refund_processed_at' => $now,
                        'refund_buffer_expires_at' => $newGuestAccessExpiresAt,
                        'guest_access_expires_at' => $newGuestAccessExpiresAt,
                    ])->save();

                    $invitation->acceptedBy?->notify(
                        new FriendInvitationGuestAccessShortenedAfterRefund($newGuestAccessExpiresAt),
                    );

                    $guestAccessShortened++;

                    $this->auditLogService->record(
                        'friend_invitation.guest_access_shortened_after_refund',
                        'friend_invitation',
                        (string) $invitation->getKey(),
                        $actor,
                        [
                            'public_id' => $invitation->public_id,
                            'guest_access_expires_at' => $newGuestAccessExpiresAt,
                        ],
                    );
                });

            return [
                'pending_revoked' => $pendingRevoked,
                'guest_access_shortened' => $guestAccessShortened,
            ];
        });
    }

    public function revokePending(FriendInvitation $invitation, User $owner, ?CarbonInterface $now = null): FriendInvitation
    {
        $now ??= now();

        return DB::transaction(function () use ($invitation, $owner, $now): FriendInvitation {
            $lockedInvitation = FriendInvitation::query()
                ->whereKey($invitation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedInvitation->owner_user_id !== (int) $owner->getKey()) {
                abort(403);
            }

            if ($lockedInvitation->status !== FriendInvitation::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'invitation' => 'To zaproszenie nie jest już oczekujące.',
                ]);
            }

            $this->markRevoked($lockedInvitation, FriendInvitation::REVOKED_REASON_OWNER, $now);

            $this->auditLogService->record(
                'friend_invitation.revoked',
                'friend_invitation',
                (string) $lockedInvitation->getKey(),
                $owner,
                [
                    'public_id' => $lockedInvitation->public_id,
                    'reason' => FriendInvitation::REVOKED_REASON_OWNER,
                ],
            );

            return $lockedInvitation->refresh();
        });
    }

    public function findPendingByToken(string $token, ?CarbonInterface $now = null): ?FriendInvitation
    {
        return $this->findPendingByHash('token_hash', $this->hashToken($token), $now);
    }

    public function findPendingByCode(string $code, ?CarbonInterface $now = null): ?FriendInvitation
    {
        return $this->findPendingByHash('code_hash', $this->hashCode($code), $now);
    }

    public function expireStaleForOwner(User $owner, ?CarbonInterface $now = null): void
    {
        $now ??= now();

        $owner->ownedFriendInvitations()
            ->where('status', FriendInvitation::STATUS_PENDING)
            ->where('expires_at', '<=', $now)
            ->update([
                'status' => FriendInvitation::STATUS_EXPIRED,
                'updated_at' => $now,
            ]);

        $expiredAccepted = $owner->ownedFriendInvitations()
            ->with('guestProductAccessGrant')
            ->where('status', FriendInvitation::STATUS_ACCEPTED)
            ->where('guest_access_expires_at', '<=', $now)
            ->get();

        foreach ($expiredAccepted as $invitation) {
            $this->expireAcceptedInvitation($invitation, $now);
        }
    }

    /**
     * @return array{pending_expired: int, guest_access_expired: int}
     */
    public function expireStale(?CarbonInterface $now = null): array
    {
        $now ??= now();

        if (! Schema::hasTable('friend_invitations')) {
            return [
                'pending_expired' => 0,
                'guest_access_expired' => 0,
            ];
        }

        $pendingExpired = FriendInvitation::query()
            ->where('status', FriendInvitation::STATUS_PENDING)
            ->where('expires_at', '<=', $now)
            ->update([
                'status' => FriendInvitation::STATUS_EXPIRED,
                'updated_at' => $now,
            ]);

        $guestAccessExpired = 0;

        FriendInvitation::query()
            ->with('guestProductAccessGrant')
            ->where('status', FriendInvitation::STATUS_ACCEPTED)
            ->where('guest_access_expires_at', '<=', $now)
            ->get()
            ->each(function (FriendInvitation $invitation) use ($now, &$guestAccessExpired): void {
                $this->expireAcceptedInvitation($invitation, $now);
                $guestAccessExpired++;
            });

        return [
            'pending_expired' => (int) $pendingExpired,
            'guest_access_expired' => $guestAccessExpired,
        ];
    }

    public function expireStaleForGuest(User $guest, ?CarbonInterface $now = null, bool $lock = false): void
    {
        $now ??= now();

        $query = $guest->acceptedFriendInvitations()
            ->with('guestProductAccessGrant')
            ->where('status', FriendInvitation::STATUS_ACCEPTED)
            ->where('guest_access_expires_at', '<=', $now);

        if ($lock) {
            $query->lockForUpdate();
        }

        $query->get()->each(
            fn (FriendInvitation $invitation) => $this->expireAcceptedInvitation($invitation, $now),
        );
    }

    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function hashCode(string $code): string
    {
        return hash('sha256', $this->normalizeCode($code));
    }

    public function normalizeCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $code) ?? '');
    }

    protected function findPendingByHash(string $column, string $hash, ?CarbonInterface $now = null): ?FriendInvitation
    {
        $now ??= now();

        $invitation = FriendInvitation::query()
            ->where($column, $hash)
            ->first();

        if (! $invitation instanceof FriendInvitation) {
            return null;
        }

        if ($invitation->status === FriendInvitation::STATUS_PENDING
            && $invitation->expires_at->lessThanOrEqualTo($now)) {
            $this->markExpired($invitation, $now);

            return null;
        }

        return $invitation->status === FriendInvitation::STATUS_PENDING ? $invitation : null;
    }

    protected function ownerGrantCanStillInvite(ProductAccessGrant $grant, CarbonInterface $now): bool
    {
        $planCode = $grant->purchaseOrder?->productPlan?->code;

        return $grant->source === ProductAccessGrant::SOURCE_PURCHASE
            && $grant->status === ProductAccessGrant::STATUS_ACTIVE
            && $grant->revoked_at === null
            && ($grant->starts_at === null || $grant->starts_at->lessThanOrEqualTo($now))
            && $grant->expires_at !== null
            && $grant->expires_at->greaterThan($now)
            && in_array($planCode, FriendInvitationEligibilityService::ELIGIBLE_PLAN_CODES, true);
    }

    protected function revokeOtherPendingInvitations(FriendInvitation $acceptedInvitation, CarbonInterface $now): void
    {
        FriendInvitation::query()
            ->where('owner_user_id', $acceptedInvitation->owner_user_id)
            ->where('status', FriendInvitation::STATUS_PENDING)
            ->whereKeyNot($acceptedInvitation->getKey())
            ->update([
                'status' => FriendInvitation::STATUS_REVOKED,
                'revoked_at' => $now,
                'revoked_reason' => FriendInvitation::REVOKED_REASON_SLOT_TAKEN,
                'updated_at' => $now,
            ]);
    }

    protected function revokeOwnerPendingInvitations(User $owner, string $reason, CarbonInterface $now): void
    {
        $owner->ownedFriendInvitations()
            ->where('status', FriendInvitation::STATUS_PENDING)
            ->update([
                'status' => FriendInvitation::STATUS_REVOKED,
                'revoked_at' => $now,
                'revoked_reason' => $reason,
                'updated_at' => $now,
            ]);
    }

    protected function markExpired(FriendInvitation $invitation, CarbonInterface $now): void
    {
        $invitation->forceFill([
            'status' => FriendInvitation::STATUS_EXPIRED,
        ])->save();

        $this->auditLogService->record(
            'friend_invitation.expired',
            'friend_invitation',
            (string) $invitation->getKey(),
            null,
            [
                'public_id' => $invitation->public_id,
                'expired_at' => $now,
            ],
        );
    }

    protected function expireAcceptedInvitation(FriendInvitation $invitation, CarbonInterface $now): void
    {
        $guestGrant = $invitation->guestProductAccessGrant;

        if ($guestGrant instanceof ProductAccessGrant
            && $guestGrant->status === ProductAccessGrant::STATUS_ACTIVE
            && $guestGrant->revoked_at === null) {
            $guestGrant->forceFill([
                'status' => ProductAccessGrant::STATUS_REVOKED,
                'revoked_at' => $now,
                'notes' => trim(((string) $guestGrant->notes)."\nDostęp gościa wygasł."),
            ])->save();
        }

        $invitation->forceFill([
            'status' => FriendInvitation::STATUS_EXPIRED,
        ])->save();
    }

    protected function markRevoked(FriendInvitation $invitation, string $reason, CarbonInterface $now): void
    {
        $invitation->forceFill([
            'status' => FriendInvitation::STATUS_REVOKED,
            'revoked_at' => $now,
            'revoked_reason' => $reason,
        ])->save();
    }

    protected function uniqueToken(): string
    {
        do {
            $token = Str::random(48);
        } while (FriendInvitation::query()->where('token_hash', $this->hashToken($token))->exists());

        return $token;
    }

    protected function uniqueCode(): string
    {
        do {
            $code = $this->randomCode();
        } while (FriendInvitation::query()->where('code_hash', $this->hashCode($code))->exists());

        return $code;
    }

    protected function randomCode(): string
    {
        $code = '';
        $max = strlen(self::CODE_ALPHABET) - 1;

        for ($index = 0; $index < 8; $index++) {
            $code .= self::CODE_ALPHABET[random_int(0, $max)];
        }

        return $code;
    }
}
