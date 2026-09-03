<?php

namespace App\Support;

use App\Models\ProductAccessGrant;
use App\Models\PurchaseOrder;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductRefundService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected FriendInvitationService $friendInvitationService,
    ) {}

    public function refund(
        PurchaseOrder $order,
        ?User $actor = null,
        ?CarbonInterface $now = null,
        ?string $reason = null,
    ): PurchaseOrder {
        $now ??= now();

        return DB::transaction(function () use ($order, $actor, $now, $reason): PurchaseOrder {
            $lockedOrder = PurchaseOrder::query()
                ->with('productAccessGrant')
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status === PurchaseOrder::STATUS_REFUNDED) {
                return $lockedOrder;
            }

            if (! $lockedOrder->isPaid()) {
                throw ValidationException::withMessages([
                    'order' => 'Tylko opłacone zamówienie może zostać zwrócone.',
                ]);
            }

            $metadata = $lockedOrder->metadata ?? [];

            if (filled($reason)) {
                $metadata['refund_reason'] = $reason;
            }

            $lockedOrder->forceFill([
                'status' => PurchaseOrder::STATUS_REFUNDED,
                'refunded_at' => $now,
                'metadata' => $metadata,
            ])->save();

            $grant = $lockedOrder->productAccessGrant;

            if ($grant instanceof ProductAccessGrant
                && $grant->source === ProductAccessGrant::SOURCE_PURCHASE
                && $grant->status === ProductAccessGrant::STATUS_ACTIVE
                && $grant->revoked_at === null) {
                $grant->forceFill([
                    'status' => ProductAccessGrant::STATUS_REVOKED,
                    'revoked_at' => $now,
                    'notes' => trim(((string) $grant->notes)."\nDostęp zakończony po refundzie zamówienia."),
                ])->save();
            }

            $friendInvitationEffects = $this->friendInvitationService->applyOwnerRefundEffects($lockedOrder, $actor, $now);

            $this->auditLogService->record(
                'purchase.order_refunded',
                'purchase_order',
                (string) $lockedOrder->getKey(),
                $actor,
                [
                    'public_id' => $lockedOrder->public_id,
                    'provider' => $lockedOrder->provider,
                    'provider_reference' => $lockedOrder->provider_reference,
                    'reason' => $reason,
                    'friend_invitation_effects' => $friendInvitationEffects,
                ],
            );

            return $lockedOrder->refresh();
        });
    }
}
