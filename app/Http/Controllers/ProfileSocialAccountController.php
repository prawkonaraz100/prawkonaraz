<?php

namespace App\Http\Controllers;

use App\Support\SocialAccountService;
use App\Support\SocialAuthProviderClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileSocialAccountController extends Controller
{
    public function destroy(
        Request $request,
        string $provider,
        SocialAuthProviderClient $providerClient,
        SocialAccountService $socialAccountService,
    ): RedirectResponse {
        $providerClient->assertSupported($provider);

        $socialAccountService->unlink($request->user(), $provider);

        return back()->with('status', 'Metoda logowania została odpięta.');
    }
}
