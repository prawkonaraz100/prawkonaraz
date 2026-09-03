<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileAvatarRequest;
use App\Support\UserAvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileAvatarController extends Controller
{
    public function store(ProfileAvatarRequest $request, UserAvatarService $userAvatarService): RedirectResponse
    {
        $userAvatarService->storeUploadedAvatar(
            $request->user(),
            $request->file('avatar'),
        );

        return back()->with('status', 'profile-avatar-updated');
    }

    public function destroy(Request $request, UserAvatarService $userAvatarService): RedirectResponse
    {
        $userAvatarService->deleteUploadedAvatar($request->user());

        return back()->with('status', 'profile-avatar-deleted');
    }
}
