<?php

use App\Models\ProductAccessGrant;
use App\Models\ProductPlan;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Support\PaymentRequirementService;
use App\Support\ProductAccessResolver;
use App\Support\ProductCheckoutService;
use Inertia\Testing\AssertableInertia as Assert;

afterEach(function (): void {
    app(PaymentRequirementService::class)->forgetCachedRequirement();
});

test('public pricing page renders seo ready checkout plans', function () {
    $this->get(route('public.pricing'))
        ->assertOk()
        ->assertViewIs('pricing.index')
        ->assertSee('Wybierz plan nauki')
        ->assertSee('Pełny dostęp do nauki i powtórek w każdym planie.')
        ->assertSee('Start')
        ->assertSee('Spokojna nauka')
        ->assertSee('Premium')
        ->assertSee('Dostęp na 1 miesiąc')
        ->assertSee('Dostęp na 3 miesiące')
        ->assertSee('Dostęp na rok')
        ->assertSee('39,00 PLN')
        ->assertSee('69,00 PLN')
        ->assertSee('149,00 PLN')
        ->assertSee('meta name="description"', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee('og:title', false)
        ->assertSee('application/ld+json', false);
});

test('verified user sees checkout form on pricing page', function () {
    $user = User::factory()->create();
    $plan = ProductPlan::query()->where('is_active', true)->firstOrFail();

    $this->actingAs($user)
        ->get(route('public.pricing'))
        ->assertOk()
        ->assertSee('Wybieram ten plan')
        ->assertSee('action="'.route('checkout.store', ['plan' => $plan->code], false).'"', false);
});

test('verified user without access can create pending purchase order', function () {
    $user = User::factory()->create();
    $plan = ProductPlan::query()->where('is_active', true)->firstOrFail();

    $this->actingAs($user)
        ->post(route('checkout.store', ['plan' => $plan->code]))
        ->assertRedirect();

    $order = PurchaseOrder::query()->where('user_id', $user->getKey())->firstOrFail();

    expect($order->status)->toBe(PurchaseOrder::STATUS_PENDING)
        ->and($order->product_plan_id)->toBe($plan->getKey())
        ->and($order->amount_gross_cents)->toBe($plan->price_gross_cents)
        ->and($order->access_days)->toBe($plan->access_days)
        ->and($user->productAccessGrants()->where('source', ProductAccessGrant::SOURCE_PURCHASE)->exists())->toBeFalse();
});

test('pending checkout page does not grant access without server confirmation', function () {
    $user = User::factory()->create();
    $plan = ProductPlan::query()->where('is_active', true)->firstOrFail();
    $order = app(ProductCheckoutService::class)->createOrder($user, $plan);

    $this->actingAs($user)
        ->get(route('checkout.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Show')
            ->where('order.public_id', $order->public_id)
            ->where('order.status', PurchaseOrder::STATUS_PENDING)
        );

    expect(app(ProductAccessResolver::class)->forUser($user)->allowed)->toBeFalse();
});

test('sandbox payment confirmation activates purchase access', function () {
    config()->set('payments.sandbox_enabled', true);

    $user = User::factory()->create();
    $plan = ProductPlan::query()->where('is_active', true)->firstOrFail();
    $order = app(ProductCheckoutService::class)->createOrder($user, $plan);

    $this->actingAs($user)
        ->post(route('checkout.sandbox.complete', $order))
        ->assertRedirect(route('checkout.success', $order));

    $order->refresh();
    $grant = ProductAccessGrant::query()
        ->where('purchase_order_id', $order->getKey())
        ->where('source', ProductAccessGrant::SOURCE_PURCHASE)
        ->first();

    expect($order->status)->toBe(PurchaseOrder::STATUS_PAID)
        ->and($order->paid_at)->not->toBeNull()
        ->and($grant)->not->toBeNull()
        ->and($grant?->expires_at?->between(now()->addDays($plan->access_days - 1), now()->addDays($plan->access_days + 1)))->toBeTrue()
        ->and(app(ProductAccessResolver::class)->forUser($user->refresh())->allowed)->toBeTrue();
});

test('checkout success exposes invite friend cta for eligible plans', function () {
    $user = User::factory()->create();
    $plan = ProductPlan::query()->where('code', 'start-90')->firstOrFail();
    $order = app(ProductCheckoutService::class)->createOrder($user, $plan);

    app(ProductCheckoutService::class)->markPaid($order, $user, 'sandbox-eligible-plan');

    $this->actingAs($user)
        ->get(route('checkout.success', $order->refresh()))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Result')
            ->where('friendInvitationCta.available', true)
            ->where('friendInvitationCta.profile_url', route('profile.edit', absolute: false).'#zapros-znajomego')
        );
});

test('checkout success hides invite friend cta while access is open', function () {
    $user = User::factory()->create();
    $plan = ProductPlan::query()->where('code', 'start-90')->firstOrFail();
    $order = app(ProductCheckoutService::class)->createOrder($user, $plan);

    app(ProductCheckoutService::class)->markPaid($order, $user, 'sandbox-open-access-plan');
    app(PaymentRequirementService::class)->setRequiresPayment(false);

    $this->actingAs($user)
        ->get(route('checkout.success', $order->refresh()))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Result')
            ->where('friendInvitationCta.available', false)
        );
});

test('marking the same order paid is idempotent', function () {
    $user = User::factory()->create();
    $plan = ProductPlan::query()->where('is_active', true)->firstOrFail();
    $order = app(ProductCheckoutService::class)->createOrder($user, $plan);

    app(ProductCheckoutService::class)->markPaid($order, $user, 'sandbox-first');
    app(ProductCheckoutService::class)->markPaid($order->refresh(), $user, 'sandbox-second');

    expect(ProductAccessGrant::query()->where('purchase_order_id', $order->getKey())->count())->toBe(1);
});

test('inactive plans cannot start checkout', function () {
    $user = User::factory()->create();
    $plan = ProductPlan::factory()->create([
        'code' => 'inactive-plan',
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->post(route('checkout.store', ['plan' => $plan->code]))
        ->assertSessionHasErrors('plan');

    expect(PurchaseOrder::query()->where('product_plan_id', $plan->getKey())->exists())->toBeFalse();
});

test('user cannot access another users checkout order', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $plan = ProductPlan::query()->where('is_active', true)->firstOrFail();
    $order = app(ProductCheckoutService::class)->createOrder($owner, $plan);

    $this->actingAs($otherUser)
        ->get(route('checkout.show', $order))
        ->assertForbidden();

    $this->actingAs($otherUser)
        ->post(route('checkout.sandbox.complete', $order))
        ->assertForbidden();
});

test('active users skip checkout and go to product', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $plan = ProductPlan::query()->where('is_active', true)->firstOrFail();

    $this->actingAs($user)
        ->post(route('checkout.store', ['plan' => $plan->code]))
        ->assertRedirect(route('session.index'));

    expect(PurchaseOrder::query()->where('user_id', $user->getKey())->exists())->toBeFalse();
});
