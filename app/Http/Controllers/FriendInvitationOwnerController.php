<?php

namespace App\Http\Controllers;

use App\Models\FriendInvitation;
use App\Support\FriendInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FriendInvitationOwnerController extends Controller
{
    public function store(Request $request, FriendInvitationService $friendInvitationService): RedirectResponse
    {
        $result = $friendInvitationService->issue($request->user());
        $invitation = $result->invitation;

        return back()
            ->with('status', 'friend-invitation-created')
            ->with('friend_invitation_created', [
                'public_id' => $invitation->public_id,
                'link' => route('friend-invitations.show-token', ['token' => $result->token]),
                'code' => $result->code,
                'expires_at' => $invitation->expires_at?->toIso8601String(),
            ]);
    }

    public function destroy(
        Request $request,
        FriendInvitation $friendInvitation,
        FriendInvitationService $friendInvitationService,
    ): RedirectResponse {
        $friendInvitationService->revokePending($friendInvitation, $request->user());

        return back()->with('status', 'friend-invitation-revoked');
    }
}
