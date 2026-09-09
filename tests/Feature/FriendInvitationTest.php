<?php

use App\Models\FriendInvitation;
use App\Models\ProductAccessGrant;
use App\Models\ProductPlan;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Notifications\FriendInvitationGuestAccessShortenedAfterRefund;
use App\Support\FriendInvitationEligibilityService;
use App\Support\FriendInvitationService;
use App\Support\PaymentRequirementService;
use App\Support\ProductAccessResolver;
use App\Support\ProductCheckoutService;
use App\Support\ProductRefundService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

afterEach(function (): void {
    app(PaymentRequirementService::class)->forgetCachedRequirement();
});

function inviteFriendCreatePurchaseGrant(
    User $user,
    string $planCode = 'start-90',
    ?CarbonInterface $expiresAt = null,
): ProductAccessGrant {
    $plan = ProductPlan::query()->where('code', $planCode)->firstOrFail();
    $now = now();
    $expiresAt ??= $now->copy()->addDays($plan->access_days);

    $order = PurchaseOrder::factory()
        ->paid()
        ->create([
            'user_id' => $user->getKey(),
            'product_plan_id' => $plan->getKey(),
            'amount_gross_cents' => $plan->price_gross_cents,
            'currency' => $plan->currency,
            'access_days' => $plan->access_days,
            'paid_at' => $now,
            'metadata' => [
                'plan_code' => $plan->code,
                'plan_name' => $plan->name,
            ],
        ]);

    return ProductAccessGrant::query()->create([
        'user_id' => $user->getKey(),
        'source' => ProductAccessGrant::SOURCE_PURCHASE,
        'status' => ProductAccessGrant::STATUS_ACTIVE,
        'starts_at' => $now->copy()->subMinute(),
        'expires_at' => $expiresAt,
        'purchase_order_id' => $order->getKey(),
        'notes' => 'Dostęp testowy aktywowany po zakupie.',
    ]);
}

test('monthly plan owner cannot issue friend invitation', function () {
    $owner = User::factory()->create();
    inviteFriendCreatePurchaseGrant($owner, 'start-30');

    expect(fn () => app(FriendInvitationService::class)->issue($owner))
        ->toThrow(ValidationException::class);
});

test('eligible owner can issue invitation with link token and manual code', function () {
    $owner = User::factory()->create();
    inviteFriendCreatePurchaseGrant($owner, 'start-90');

    $result = app(FriendInvitationService::class)->issue($owner);
    $invitation = $result->invitation->refresh();

    expect($invitation->status)->toBe(FriendInvitation::STATUS_PENDING)
        ->and($invitation->owner_user_id)->toBe($owner->getKey())
        ->and($result->token)->not->toBe('')
        ->and($result->code)->not->toBe('')
        ->and($invitation->token_hash)->not->toBe($result->token)
        ->and($invitation->code_hash)->not->toBe($result->code)
        ->and($invitation->expires_at?->between(now()->addDays(13), now()->addDays(15)))->toBeTrue();

    expect(app(FriendInvitationService::class)->findPendingByToken($result->token)?->is($invitation))->toBeTrue()
        ->and(app(FriendInvitationService::class)->findPendingByCode($result->code)?->is($invitation))->toBeTrue();
});

test('profile page exposes owner invitation panel data', function () {
    $owner = User::factory()->create();
    inviteFriendCreatePurchaseGrant($owner, 'start-90');

    $this
        ->actingAs($owner)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Edit')
            ->where('friendInvitations.enabled', true)
            ->where('friendInvitations.eligible', true)
            ->where('friendInvitations.can_issue', true)
            ->where('friendInvitations.pending_limit', FriendInvitationEligibilityService::PENDING_LIMIT)
            ->where('friendInvitations.pending_count', 0)
            ->where('friendInvitations.active_guest', null)
        );
});

test('open access mode hides invitation entry points and blocks new invitation operations', function () {
    $owner = User::factory()->create();
    $guest = User::factory()->create();
    inviteFriendCreatePurchaseGrant($owner, 'start-90');
    $issued = app(FriendInvitationService::class)->issue($owner);
    $existingInvitation = $issued->invitation->refresh();

    expect(app(PaymentRequirementService::class)->setRequiresPayment(false))->toBeTrue();

    $this
        ->actingAs($owner)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Edit')
            ->where('friendInvitations.enabled', false)
            ->where('friendInvitations.can_issue', false)
            ->where('friendInvitations.reason', 'access_open')
        );

    $this
        ->actingAs($owner)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Index')
            ->where('friend_invitation_cta.visible', false)
        );

    $this
        ->get(route('friend-invitations.code.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('FriendInvitations/Show')
            ->where('invitation.active', false)
            ->where('invitation.inactive_reason', 'access_open')
        );

    $this
        ->get(route('friend-invitations.show-token', ['token' => $issued->token]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('FriendInvitations/Show')
            ->where('invitation.inactive_reason', 'access_open')
        );

    $this
        ->actingAs($owner)
        ->from(route('profile.edit'))
        ->post(route('friend-invitations.store'))
        ->assertSessionHasErrors('invitation')
        ->assertRedirect(route('profile.edit'));

    expect($existingInvitation->refresh()->status)->toBe(FriendInvitation::STATUS_PENDING)
        ->and($owner->ownedFriendInvitations()->count())->toBe(1);

    expect(fn () => app(FriendInvitationService::class)->accept($existingInvitation, $guest))
        ->toThrow(ValidationException::class);

    expect($existingInvitation->refresh()->status)->toBe(FriendInvitation::STATUS_PENDING)
        ->and($guest->productAccessGrants()->exists())->toBeFalse()
        ->and(app(PaymentRequirementService::class)->setRequiresPayment(true))->toBeTrue();

    $accepted = app(FriendInvitationService::class)->accept($existingInvitation, $guest);

    expect($accepted->status)->toBe(FriendInvitation::STATUS_ACCEPTED)
        ->and($accepted->accepted_by_user_id)->toBe($guest->getKey());
});

test('eligible owner can generate invitation from profile route', function () {
    $owner = User::factory()->create();
    inviteFriendCreatePurchaseGrant($owner, 'start-90');

    $this
        ->actingAs($owner)
        ->from(route('profile.edit'))
        ->post(route('friend-invitations.store'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('friend_invitation_created')
        ->assertRedirect(route('profile.edit'));

    $invitation = $owner->ownedFriendInvitations()->firstOrFail();
    $generated = session('friend_invitation_created');

    expect($invitation->status)->toBe(FriendInvitation::STATUS_PENDING)
        ->and($generated)->toBeArray()
        ->and($generated['public_id'] ?? null)->toBe($invitation->public_id)
        ->and($generated['link'] ?? null)->toContain('/zaproszenie/')
        ->and($generated['code'] ?? null)->not->toBe('');
});

test('guest can claim invitation through manual code flow', function () {
    $owner = User::factory()->create();
    $ownerGrant = inviteFriendCreatePurchaseGrant($owner, 'start-365', now()->addDays(45));
    $guest = User::factory()->create();
    $issued = app(FriendInvitationService::class)->issue($owner);

    $this
        ->actingAs($guest)
        ->post(route('friend-invitations.code.store'), [
            'code' => strtolower(substr($issued->code, 0, 4).'-'.substr($issued->code, 4)),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('friend-invitations.pending.show'));

    $this
        ->actingAs($guest)
        ->get(route('friend-invitations.pending.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('FriendInvitations/Show')
            ->where('invitation.active', true)
            ->where('invitation.access_expires_at', $ownerGrant->expires_at?->toIso8601String())
        );

    $this
        ->actingAs($guest)
        ->post(route('friend-invitations.pending.accept'))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('session.index'));

    $accepted = $issued->invitation->refresh();

    expect($accepted->status)->toBe(FriendInvitation::STATUS_ACCEPTED)
        ->and($accepted->accepted_by_user_id)->toBe($guest->getKey())
        ->and($accepted->guestProductAccessGrant?->source)->toBe(ProductAccessGrant::SOURCE_INVITATION_GUEST)
        ->and($accepted->guest_access_expires_at?->equalTo($ownerGrant->expires_at))->toBeTrue();
});

test('pending invitation limit is enforced per owner', function () {
    $owner = User::factory()->create();
    inviteFriendCreatePurchaseGrant($owner, 'start-365');

    for ($index = 0; $index < FriendInvitationEligibilityService::PENDING_LIMIT; $index++) {
        app(FriendInvitationService::class)->issue($owner);
    }

    expect($owner->ownedFriendInvitations()->where('status', FriendInvitation::STATUS_PENDING)->count())
        ->toBe(FriendInvitationEligibilityService::PENDING_LIMIT);

    expect(fn () => app(FriendInvitationService::class)->issue($owner))
        ->toThrow(ValidationException::class);
});

test('accepting invitation creates guest access and revokes other pending invitations', function () {
    $owner = User::factory()->create();
    $ownerGrant = inviteFriendCreatePurchaseGrant($owner, 'start-365', now()->addDays(80));
    $guest = User::factory()->create();

    $first = app(FriendInvitationService::class)->issue($owner)->invitation;
    $second = app(FriendInvitationService::class)->issue($owner)->invitation;
    $third = app(FriendInvitationService::class)->issue($owner)->invitation;

    $accepted = app(FriendInvitationService::class)->accept($second, $guest);
    $guestGrant = $accepted->guestProductAccessGrant;

    expect($accepted->status)->toBe(FriendInvitation::STATUS_ACCEPTED)
        ->and($accepted->accepted_by_user_id)->toBe($guest->getKey())
        ->and($guestGrant)->not->toBeNull()
        ->and($guestGrant?->source)->toBe(ProductAccessGrant::SOURCE_INVITATION_GUEST)
        ->and($guestGrant?->status)->toBe(ProductAccessGrant::STATUS_ACTIVE)
        ->and($guestGrant?->expires_at?->equalTo($ownerGrant->expires_at))->toBeTrue();

    expect($first->refresh()->status)->toBe(FriendInvitation::STATUS_REVOKED)
        ->and($first->revoked_reason)->toBe(FriendInvitation::REVOKED_REASON_SLOT_TAKEN)
        ->and($third->refresh()->status)->toBe(FriendInvitation::STATUS_REVOKED);

    $decision = app(ProductAccessResolver::class)->forUser($guest->refresh());

    expect($decision->allowed)->toBeTrue()
        ->and($decision->source)->toBe(ProductAccessGrant::SOURCE_INVITATION_GUEST);
});

test('active premium user cannot accept invitation and invitation remains reusable', function () {
    $owner = User::factory()->create();
    inviteFriendCreatePurchaseGrant($owner, 'start-90');
    $premiumGuest = User::factory()->create();
    inviteFriendCreatePurchaseGrant($premiumGuest, 'start-30');
    $invitation = app(FriendInvitationService::class)->issue($owner)->invitation;

    expect(fn () => app(FriendInvitationService::class)->accept($invitation, $premiumGuest))
        ->toThrow(ValidationException::class);

    expect($invitation->refresh()->status)->toBe(FriendInvitation::STATUS_PENDING);
});

test('active guest cannot issue their own invitation', function () {
    $owner = User::factory()->create();
    inviteFriendCreatePurchaseGrant($owner, 'start-90');
    $guest = User::factory()->create();
    $invitation = app(FriendInvitationService::class)->issue($owner)->invitation;

    app(FriendInvitationService::class)->accept($invitation, $guest);

    expect(fn () => app(FriendInvitationService::class)->issue($guest->refresh()))
        ->toThrow(ValidationException::class);
});

test('guest can accept a new invitation after previous guest access expires', function () {
    $guest = User::factory()->create();
    $firstOwner = User::factory()->create();
    $secondOwner = User::factory()->create();
    $firstAcceptedAt = now();
    $secondAcceptedAt = $firstAcceptedAt->copy()->addDays(3);

    inviteFriendCreatePurchaseGrant($firstOwner, 'start-90', $firstAcceptedAt->copy()->addDay());
    inviteFriendCreatePurchaseGrant($secondOwner, 'start-365', $secondAcceptedAt->copy()->addDays(40));

    $firstInvitation = app(FriendInvitationService::class)->issue($firstOwner, $firstAcceptedAt)->invitation;
    $firstAccepted = app(FriendInvitationService::class)->accept($firstInvitation, $guest, $firstAcceptedAt);
    $firstGuestGrant = $firstAccepted->guestProductAccessGrant;

    $secondInvitation = app(FriendInvitationService::class)->issue($secondOwner, $secondAcceptedAt)->invitation;
    $secondAccepted = app(FriendInvitationService::class)->accept($secondInvitation, $guest, $secondAcceptedAt);

    expect($firstAccepted->refresh()->status)->toBe(FriendInvitation::STATUS_EXPIRED)
        ->and($firstGuestGrant?->refresh()->status)->toBe(ProductAccessGrant::STATUS_REVOKED)
        ->and($secondAccepted->status)->toBe(FriendInvitation::STATUS_ACCEPTED)
        ->and($secondAccepted->accepted_by_user_id)->toBe($guest->getKey());
});

test('ops command expires stale pending invitations and guest access', function () {
    $owner = User::factory()->create();
    $pendingOwner = User::factory()->create();
    $guest = User::factory()->create();
    inviteFriendCreatePurchaseGrant($owner, 'start-90', now()->addDays(10));
    inviteFriendCreatePurchaseGrant($pendingOwner, 'start-365', now()->addDays(10));

    $pending = app(FriendInvitationService::class)->issue($owner)->invitation;
    $accepted = app(FriendInvitationService::class)->accept($pending, $guest);
    $guestGrant = $accepted->guestProductAccessGrant;
    $stalePending = app(FriendInvitationService::class)->issue($pendingOwner)->invitation;

    $accepted->forceFill([
        'guest_access_expires_at' => now()->subMinute(),
    ])->save();
    $guestGrant?->forceFill([
        'expires_at' => now()->subMinute(),
    ])->save();
    $stalePending->forceFill([
        'expires_at' => now()->subMinute(),
    ])->save();

    Artisan::call('ops:expire-friend-invitations', ['--json' => true]);
    $result = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    expect($result)->toMatchArray([
        'pending_expired' => 1,
        'guest_access_expired' => 1,
    ])
        ->and($accepted->refresh()->status)->toBe(FriendInvitation::STATUS_EXPIRED)
        ->and($guestGrant?->refresh()->status)->toBe(ProductAccessGrant::STATUS_REVOKED)
        ->and($stalePending->refresh()->status)->toBe(FriendInvitation::STATUS_EXPIRED);
});

test('owner refund revokes pending invitations and shortens active guest access to seven days', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $guest = User::factory()->create();
    $refundAt = now()->addDay()->startOfSecond();
    $ownerGrant = inviteFriendCreatePurchaseGrant($owner, 'start-365', $refundAt->copy()->addDays(80));
    $order = $ownerGrant->purchaseOrder()->firstOrFail();
    $pending = app(FriendInvitationService::class)->issue($owner)->invitation;

    app(ProductRefundService::class)->refund($order, $owner, $refundAt, 'test-refund');

    expect($pending->refresh()->status)->toBe(FriendInvitation::STATUS_REVOKED)
        ->and($pending->revoked_reason)->toBe(FriendInvitation::REVOKED_REASON_REFUND)
        ->and($order->refresh()->status)->toBe(PurchaseOrder::STATUS_REFUNDED)
        ->and($order->refunded_at?->equalTo($refundAt))->toBeTrue()
        ->and($ownerGrant->refresh()->status)->toBe(ProductAccessGrant::STATUS_REVOKED);

    $newOwnerGrant = inviteFriendCreatePurchaseGrant($owner, 'start-365', $refundAt->copy()->addDays(90));
    $newOrder = $newOwnerGrant->purchaseOrder()->firstOrFail();
    $acceptedInvitation = app(FriendInvitationService::class)->issue($owner, $refundAt)->invitation;
    $accepted = app(FriendInvitationService::class)->accept($acceptedInvitation, $guest, $refundAt);
    $guestGrant = $accepted->guestProductAccessGrant;

    app(ProductRefundService::class)->refund($newOrder, $owner, $refundAt, 'test-refund');

    expect($accepted->refresh()->status)->toBe(FriendInvitation::STATUS_ACCEPTED)
        ->and($accepted->refund_processed_at?->equalTo($refundAt))->toBeTrue()
        ->and($accepted->guest_access_expires_at?->equalTo($refundAt->copy()->addDays(7)))->toBeTrue()
        ->and($guestGrant?->refresh()->expires_at?->equalTo($refundAt->copy()->addDays(7)))->toBeTrue()
        ->and($newOrder->refresh()->status)->toBe(PurchaseOrder::STATUS_REFUNDED)
        ->and($newOwnerGrant->refresh()->status)->toBe(ProductAccessGrant::STATUS_REVOKED);

    Notification::assertSentTo(
        $guest,
        FriendInvitationGuestAccessShortenedAfterRefund::class,
        fn (FriendInvitationGuestAccessShortenedAfterRefund $notification): bool => $notification
            ->accessExpiresAt()
            ->equalTo($refundAt->copy()->addDays(7)),
    );
});

test('guest purchase converts invitation access and frees owner slot', function () {
    config()->set('payments.sandbox_enabled', true);

    $owner = User::factory()->create();
    inviteFriendCreatePurchaseGrant($owner, 'start-365', now()->addDays(120));
    $guest = User::factory()->create();
    $invitation = app(FriendInvitationService::class)->issue($owner)->invitation;
    $accepted = app(FriendInvitationService::class)->accept($invitation, $guest);
    $guestGrant = $accepted->guestProductAccessGrant;

    expect(fn () => app(FriendInvitationService::class)->issue($owner->refresh()))
        ->toThrow(ValidationException::class);

    $plan = ProductPlan::query()->where('code', 'start-90')->firstOrFail();

    $this
        ->actingAs($guest)
        ->post(route('checkout.store', ['plan' => $plan->code]))
        ->assertRedirect();

    $order = PurchaseOrder::query()
        ->where('user_id', $guest->getKey())
        ->where('product_plan_id', $plan->getKey())
        ->where('status', PurchaseOrder::STATUS_PENDING)
        ->firstOrFail();

    app(ProductCheckoutService::class)->markPaid($order, $guest, 'sandbox-guest-conversion');

    $purchaseDecision = app(ProductAccessResolver::class)->forUser($guest->refresh());

    expect($accepted->refresh()->status)->toBe(FriendInvitation::STATUS_CONVERTED)
        ->and($accepted->converted_purchase_order_id)->toBe($order->getKey())
        ->and($guestGrant?->refresh()->status)->toBe(ProductAccessGrant::STATUS_REVOKED)
        ->and($guestGrant?->revoked_at)->not->toBeNull()
        ->and($purchaseDecision->allowed)->toBeTrue()
        ->and($purchaseDecision->source)->toBe(ProductAccessGrant::SOURCE_PURCHASE);

    $newInvitation = app(FriendInvitationService::class)->issue($owner->refresh())->invitation;

    expect($newInvitation->status)->toBe(FriendInvitation::STATUS_PENDING);
});
