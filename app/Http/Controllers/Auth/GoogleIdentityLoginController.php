<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\SocialAccountNeedsCategoryException;
use App\Http\Controllers\Controller;
use App\Models\UserSocialAccount;
use App\Support\GoogleIdentityRegistrationSession;
use App\Support\GoogleIdentityTokenVerifier;
use App\Support\ReturningUserCookie;
use App\Support\SocialAccountService;
use App\Support\SocialProviderUser;
use App\Support\UserIpHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleIdentityLoginController extends Controller
{
    public function store(
        Request $request,
        GoogleIdentityTokenVerifier $tokenVerifier,
        SocialAccountService $socialAccountService,
        UserIpHistoryService $userIpHistoryService,
        ReturningUserCookie $returningUserCookie,
        GoogleIdentityRegistrationSession $registrationSession,
    ): JsonResponse {
        if (! config('services.google.identity_enabled') || blank(config('services.google.client_id'))) {
            return response()->json([
                'message' => 'Logowanie przez Google nie jest jeszcze skonfigurowane.',
            ], 503);
        }

        $validated = $request->validate([
            'credential' => ['required', 'string', 'max:12000'],
        ]);

        $providerUser = null;

        try {
            $providerUser = $tokenVerifier->userFromCredential((string) $validated['credential']);
            $user = $socialAccountService->resolveExistingForLogin(
                UserSocialAccount::PROVIDER_GOOGLE,
                $providerUser,
            );
        } catch (SocialAccountNeedsCategoryException) {
            abort_unless($providerUser instanceof SocialProviderUser, 422);

            $registrationSession->start($request, $providerUser);

            return response()->json([
                'registration_required' => true,
                'redirect' => route('register', ['google_identity' => '1'], absolute: false),
            ]);
        }

        if ($user->isBanned()) {
            return response()->json([
                'message' => 'To konto zostało zablokowane.',
            ], 403);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $userIpHistoryService->record($user, $request, 'google_identity_login', force: true);
        $returningUserCookie->queue($request);

        return response()->json([
            'redirect' => route('dashboard', absolute: false),
        ]);
    }
}
