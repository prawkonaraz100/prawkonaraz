<?php

namespace App\Http\Controllers;

use App\Models\FriendInvitation;
use App\Models\User;
use App\Support\FriendInvitationEligibilityService;
use App\Support\FriendInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FriendInvitationClaimController extends Controller
{
    private const SESSION_KEY = 'friend_invitation.pending_id';

    public function __construct(
        protected FriendInvitationEligibilityService $eligibility,
    ) {}

    public function codeForm(): Response
    {
        if (! $this->eligibility->isAvailable()) {
            return $this->renderInvitation(null, 'access_open');
        }

        return Inertia::render('FriendInvitations/Code', [
            'status' => session('status'),
        ]);
    }

    public function submitCode(Request $request, FriendInvitationService $friendInvitationService): RedirectResponse
    {
        if (! $this->eligibility->isAvailable()) {
            $request->session()->forget(self::SESSION_KEY);

            return to_route('friend-invitations.code.create');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $invitation = $friendInvitationService->findPendingByCode((string) $validated['code']);

        if (! $invitation instanceof FriendInvitation) {
            $request->session()->forget(self::SESSION_KEY);

            throw ValidationException::withMessages([
                'code' => 'Ten kod jest nieprawidłowy albo wygasł.',
            ]);
        }

        $this->storePendingInvitation($request, $invitation);

        return to_route('friend-invitations.pending.show');
    }

    public function showToken(
        Request $request,
        string $token,
        FriendInvitationService $friendInvitationService,
    ): Response {
        if (! $this->eligibility->isAvailable()) {
            $request->session()->forget(self::SESSION_KEY);

            return $this->renderInvitation(null, 'access_open');
        }

        $invitation = $friendInvitationService->findPendingByToken($token);

        if (! $invitation instanceof FriendInvitation) {
            $request->session()->forget(self::SESSION_KEY);

            return $this->renderInvitation(null, 'inactive');
        }

        $this->storePendingInvitation($request, $invitation);

        return $this->renderInvitation($invitation);
    }

    public function showPending(Request $request): Response|RedirectResponse
    {
        if (! $this->eligibility->isAvailable()) {
            $request->session()->forget(self::SESSION_KEY);

            return $this->renderInvitation(null, 'access_open');
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return to_route('login');
        }

        if (! $user->hasVerifiedEmail()) {
            return to_route('verification.notice');
        }

        $invitation = $this->pendingInvitation($request);

        if (! $invitation instanceof FriendInvitation) {
            $request->session()->forget(self::SESSION_KEY);

            return $this->renderInvitation(null, 'missing_session');
        }

        return $this->renderInvitation($invitation);
    }

    public function accept(
        Request $request,
        FriendInvitationService $friendInvitationService,
    ): RedirectResponse {
        if (! $this->eligibility->isAvailable()) {
            $request->session()->forget(self::SESSION_KEY);

            return to_route('session.index')
                ->with('status', 'Dostęp do platformy jest obecnie otwarty. Kod zaproszenia nie jest potrzebny.');
        }

        $invitation = $this->pendingInvitation($request);

        if (! $invitation instanceof FriendInvitation) {
            return to_route('friend-invitations.code.create')
                ->withErrors(['invitation' => 'Nie znaleźliśmy aktywnego zaproszenia. Wklej kod ponownie.']);
        }

        $friendInvitationService->accept($invitation, $request->user());
        $request->session()->forget(self::SESSION_KEY);

        return to_route('session.index')
            ->with('status', 'Dostęp z zaproszenia został aktywowany.');
    }

    protected function storePendingInvitation(Request $request, FriendInvitation $invitation): void
    {
        $request->session()->put(self::SESSION_KEY, $invitation->getKey());
    }

    protected function pendingInvitation(Request $request): ?FriendInvitation
    {
        $id = $request->session()->get(self::SESSION_KEY);

        if ($id === null) {
            return null;
        }

        $invitation = FriendInvitation::query()
            ->with(['owner', 'ownerProductAccessGrant'])
            ->whereKey($id)
            ->where('status', FriendInvitation::STATUS_PENDING)
            ->first();

        $now = now();

        if ($invitation instanceof FriendInvitation && $invitation->expires_at->lessThanOrEqualTo($now)) {
            if ($invitation->owner instanceof User) {
                app(FriendInvitationService::class)->expireStaleForOwner($invitation->owner, $now);
            }

            return null;
        }

        return $invitation;
    }

    protected function renderInvitation(?FriendInvitation $invitation, ?string $inactiveReason = null): Response
    {
        $active = $invitation instanceof FriendInvitation;

        return Inertia::render('FriendInvitations/Show', [
            'invitation' => $active ? [
                'active' => true,
                'inviter_name' => $invitation->owner?->name,
                'access_expires_at' => $invitation->ownerProductAccessGrant?->expires_at?->toIso8601String(),
                'invitation_expires_at' => $invitation->expires_at?->toIso8601String(),
            ] : [
                'active' => false,
                'inactive_reason' => $inactiveReason,
            ],
            'status' => session('status'),
            'loginUrl' => route('login', absolute: false),
            'registerUrl' => route('register', absolute: false),
            'codeUrl' => route('friend-invitations.code.create', absolute: false),
        ]);
    }
}
