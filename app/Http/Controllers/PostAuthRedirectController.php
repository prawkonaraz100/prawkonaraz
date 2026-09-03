<?php

namespace App\Http\Controllers;

use App\Support\PjmFreeAccessResolver;
use App\Support\ProductAccessResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostAuthRedirectController extends Controller
{
    public function __invoke(
        Request $request,
        ProductAccessResolver $productAccessResolver,
        PjmFreeAccessResolver $pjmFreeAccessResolver,
    ): RedirectResponse {
        if ($request->user()->isUnclaimedTemporaryAccount()) {
            return to_route('account.claim.edit');
        }

        if ($request->user()->mustChangePassword()) {
            return to_route('password.force.edit');
        }

        if (! $request->user()->hasVerifiedEmail()) {
            return to_route('verification.notice');
        }

        if ($request->session()->has('friend_invitation.pending_id')) {
            return to_route('friend-invitations.pending.show');
        }

        if ($request->user()->isModerator()) {
            return to_route('moderator.accounts.index');
        }

        $decision = $productAccessResolver->forUser($request->user());

        if ($decision->allowed) {
            return to_route('session.index');
        }

        if ($pjmFreeAccessResolver->forUser($request->user())->allowed) {
            return to_route('session.index');
        }

        return to_route('access.activate');
    }
}
