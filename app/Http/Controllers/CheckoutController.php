<?php

namespace App\Http\Controllers;

use App\Models\ProductAccessGrant;
use App\Models\ProductPlan;
use App\Models\PurchaseOrder;
use App\Support\FriendInvitationEligibilityService;
use App\Support\PaymentRequirementService;
use App\Support\ProductAccessResolver;
use App\Support\ProductCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function store(
        Request $request,
        ProductPlan $plan,
        ProductCheckoutService $checkoutService,
        ProductAccessResolver $productAccessResolver,
        PaymentRequirementService $paymentRequirementService,
    ): RedirectResponse {
        if (! $paymentRequirementService->requiresPayment()) {
            return to_route('session.index');
        }

        $decision = $productAccessResolver->forUser($request->user());

        if ($decision->allowed && $decision->source !== ProductAccessGrant::SOURCE_INVITATION_GUEST) {
            return to_route('session.index');
        }

        $order = $checkoutService->createOrder($request->user(), $plan);

        return to_route('checkout.show', $order);
    }

    public function show(
        Request $request,
        PurchaseOrder $order,
        PaymentRequirementService $paymentRequirementService,
    ): Response|RedirectResponse
    {
        $this->ensureOwner($request, $order);

        if ($order->isPaid()) {
            return to_route('checkout.success', $order);
        }

        if (! $paymentRequirementService->requiresPayment()) {
            return to_route('session.index');
        }

        return Inertia::render('Checkout/Show', [
            'order' => $this->orderPayload($order->load('productPlan')),
            'sandboxEnabled' => (bool) config('payments.sandbox_enabled'),
            'pricingUrl' => route('public.pricing', absolute: false),
        ]);
    }

    public function completeSandbox(
        Request $request,
        PurchaseOrder $order,
        ProductCheckoutService $checkoutService,
        PaymentRequirementService $paymentRequirementService,
    ): RedirectResponse {
        abort_unless((bool) config('payments.sandbox_enabled'), 404);

        $this->ensureOwner($request, $order);

        if (! $paymentRequirementService->requiresPayment()) {
            return to_route('session.index')
                ->with('status', 'Dostęp jest obecnie otwarty. Nie musisz potwierdzać płatności.');
        }

        $checkoutService->markPaid(
            $order,
            $request->user(),
            'sandbox-'.$order->public_id,
        );

        return to_route('checkout.success', $order)
            ->with('status', 'Dostęp został aktywowany.');
    }

    public function cancel(
        Request $request,
        PurchaseOrder $order,
        ProductCheckoutService $checkoutService,
    ): RedirectResponse {
        $this->ensureOwner($request, $order);

        $checkoutService->cancel($order, $request->user());

        return to_route('access.activate')
            ->with('status', 'Zamówienie zostało anulowane.');
    }

    public function success(Request $request, PurchaseOrder $order): Response|RedirectResponse
    {
        $this->ensureOwner($request, $order);

        $order->load('productPlan', 'productAccessGrant');

        if (! $order->isPaid()) {
            return to_route('checkout.show', $order);
        }

        return Inertia::render('Checkout/Result', [
            'order' => $this->orderPayload($order),
            'status' => 'paid',
            'accessExpiresAt' => $order->productAccessGrant?->expires_at?->toIso8601String(),
            'sessionUrl' => route('session.index', absolute: false),
            'friendInvitationCta' => [
                'available' => in_array(
                    $order->productPlan?->code,
                    FriendInvitationEligibilityService::ELIGIBLE_PLAN_CODES,
                    true,
                ),
                'profile_url' => route('profile.edit', absolute: false).'#zapros-znajomego',
            ],
        ]);
    }

    protected function ensureOwner(Request $request, PurchaseOrder $order): void
    {
        abort_unless((int) $order->user_id === (int) $request->user()->getKey(), 403);
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderPayload(PurchaseOrder $order): array
    {
        return [
            'public_id' => $order->public_id,
            'status' => $order->status,
            'provider' => $order->provider,
            'amount_gross_cents' => $order->amount_gross_cents,
            'formatted_amount' => $order->formattedAmount(),
            'currency' => $order->currency,
            'access_days' => $order->access_days,
            'paid_at' => $order->paid_at?->toIso8601String(),
            'plan' => [
                'code' => $order->productPlan?->code,
                'name' => $order->productPlan?->name,
                'description' => $order->productPlan?->description,
            ],
        ];
    }
}
