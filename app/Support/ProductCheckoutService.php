<?php

namespace App\Support;

use App\Models\ProductAccessGrant;
use App\Models\ProductPlan;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductCheckoutService
{
    public function __construct(
        protected PaymentRequirementService $paymentRequirementService,
    ) {}

    /**
     * @return Collection<int, ProductPlan>
     */
    public function activePlans(): Collection
    {
        return ProductPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price_gross_cents')
            ->get();
    }

    public function createOrder(User $user, ProductPlan $plan): PurchaseOrder
    {
        if (! $this->paymentRequirementService->requiresPayment()) {
            throw ValidationException::withMessages([
                'checkout' => 'Płatność jest obecnie wyłączona. Możesz od razu rozpocząć naukę.',
            ]);
        }

        if (! $plan->is_active) {
            throw ValidationException::withMessages([
                'plan' => 'Ten plan nie jest już dostępny.',
            ]);
        }

        return DB::transaction(function () use ($user, $plan): PurchaseOrder {
            $order = PurchaseOrder::query()->create([
                'public_id' => (string) Str::ulid(),
                'user_id' => $user->getKey(),
                'product_plan_id' => $plan->getKey(),
                'provider' => config('payments.default_provider', 'sandbox'),
                'status' => PurchaseOrder::STATUS_PENDING,
                'amount_gross_cents' => $plan->price_gross_cents,
                'currency' => $plan->currency,
                'access_days' => $plan->access_days,
                'metadata' => [
                    'plan_code' => $plan->code,
                    'plan_name' => $plan->name,
                ],
            ]);

            app(AuditLogService::class)->record(
                'purchase.order_created',
                'purchase_order',
                (string) $order->getKey(),
                $user,
                [
                    'public_id' => $order->public_id,
                    'plan_code' => $plan->code,
                    'amount_gross_cents' => $order->amount_gross_cents,
                    'currency' => $order->currency,
                    'provider' => $order->provider,
                ],
            );

            return $order;
        });
    }

    public function markPaid(PurchaseOrder $order, ?User $actor = null, ?string $providerReference = null): ProductAccessGrant
    {
        return DB::transaction(function () use ($order, $actor, $providerReference): ProductAccessGrant {
            $lockedOrder = PurchaseOrder::query()
                ->with('productAccessGrant')
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->isPaid() && $lockedOrder->productAccessGrant !== null) {
                return $lockedOrder->productAccessGrant;
            }

            if (! $lockedOrder->isPending()) {
                throw ValidationException::withMessages([
                    'order' => 'Tego zamówienia nie można już opłacić.',
                ]);
            }

            $now = now();

            $lockedOrder->forceFill([
                'status' => PurchaseOrder::STATUS_PAID,
                'paid_at' => $now,
                'provider_reference' => $providerReference,
            ])->save();

            $grant = ProductAccessGrant::query()->create([
                'user_id' => $lockedOrder->user_id,
                'source' => ProductAccessGrant::SOURCE_PURCHASE,
                'status' => ProductAccessGrant::STATUS_ACTIVE,
                'starts_at' => $now,
                'expires_at' => $now->copy()->addDays($lockedOrder->access_days),
                'purchase_order_id' => $lockedOrder->getKey(),
                'notes' => 'Dostęp aktywowany po potwierdzeniu zakupu.',
            ]);

            app(AuditLogService::class)->record(
                'purchase.order_paid',
                'purchase_order',
                (string) $lockedOrder->getKey(),
                $actor,
                [
                    'public_id' => $lockedOrder->public_id,
                    'provider' => $lockedOrder->provider,
                    'provider_reference' => $providerReference,
                    'grant_id' => $grant->getKey(),
                    'access_expires_at' => $grant->expires_at,
                ],
            );

            $user = User::query()->find($lockedOrder->user_id);

            if ($user instanceof User) {
                app(FriendInvitationService::class)->convertGuestToPurchase($user, $grant, $actor, $now);
            }

            return $grant;
        });
    }

    public function cancel(PurchaseOrder $order, ?User $actor = null): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $actor): PurchaseOrder {
            $lockedOrder = PurchaseOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedOrder->isPending()) {
                return $lockedOrder;
            }

            $lockedOrder->forceFill([
                'status' => PurchaseOrder::STATUS_CANCELED,
                'canceled_at' => now(),
            ])->save();

            app(AuditLogService::class)->record(
                'purchase.order_canceled',
                'purchase_order',
                (string) $lockedOrder->getKey(),
                $actor,
                [
                    'public_id' => $lockedOrder->public_id,
                    'provider' => $lockedOrder->provider,
                ],
            );

            return $lockedOrder;
        });
    }
}
