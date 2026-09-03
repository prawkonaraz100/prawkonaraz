<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\SocialAccountNeedsCategoryException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ReturningUserCookie;
use App\Support\SocialAccountService;
use App\Support\SocialAuthProviderClient;
use App\Support\SocialProviderUser;
use App\Support\UserIpHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SocialAuthController extends Controller
{
    public function redirect(
        Request $request,
        string $provider,
        SocialAuthProviderClient $providerClient,
    ): RedirectResponse {
        $providerClient->assertSupported($provider);

        $state = Str::random(48);
        $intent = $request->query('intent') === 'link' && $request->user() instanceof User
            ? 'link'
            : 'login';

        $request->session()->put("social_auth.$state", [
            'provider' => $provider,
            'intent' => $intent,
            'user_id' => $request->user()?->getKey(),
            'target_category_id' => $request->integer('target_category_id') ?: null,
            'preferred_learning_track' => $request->query('preferred_learning_track') === UserProfile::LEARNING_TRACK_CLASSIC
                ? UserProfile::LEARNING_TRACK_CLASSIC
                : null,
        ]);

        try {
            return redirect()->away($providerClient->redirectUrl($provider, $state));
        } catch (ValidationException $exception) {
            $request->session()->forget("social_auth.$state");

            return $this->redirectAfterSocialFailure($request, $exception, [
                'intent' => $intent,
                'target_category_id' => $request->integer('target_category_id') ?: null,
            ]);
        }
    }

    public function callback(
        Request $request,
        string $provider,
        SocialAuthProviderClient $providerClient,
        SocialAccountService $socialAccountService,
        UserIpHistoryService $userIpHistoryService,
        ReturningUserCookie $returningUserCookie,
    ): RedirectResponse {
        $providerClient->assertSupported($provider);
        $pending = null;

        try {
            $pending = $this->pullPendingState($request, $provider);
            $providerUser = $providerClient->user($provider, $request);

            if (($pending['intent'] ?? null) === 'link') {
                return $this->linkAuthenticatedUser($request, $provider, $pending, $providerUser, $socialAccountService);
            }

            $user = $socialAccountService->resolveForLogin(
                $provider,
                $providerUser,
                $pending['target_category_id'] ?? null,
                is_string($pending['preferred_learning_track'] ?? null) ? $pending['preferred_learning_track'] : null,
            );
        } catch (SocialAccountNeedsCategoryException) {
            return to_route('register')
                ->withErrors([
                    'target_category_id' => 'Wybierz kategorię prawa jazdy przed rejestracją przez Google lub Facebook.',
                ]);
        } catch (ValidationException $exception) {
            return $this->redirectAfterSocialFailure($request, $exception, $pending);
        }

        if ($user->isBanned()) {
            return $this->redirectAfterSocialFailure(
                $request,
                ValidationException::withMessages([
                    'provider' => 'To konto zostało zablokowane.',
                ]),
                $pending,
            );
        }

        Auth::login($user);
        $request->session()->regenerate();
        $userIpHistoryService->record($user, $request, 'social_login', force: true);
        $returningUserCookie->queue($request);

        return redirect(route('dashboard', absolute: false));
    }

    /**
     * @param  array<string, mixed>  $pending
     */
    protected function linkAuthenticatedUser(
        Request $request,
        string $provider,
        array $pending,
        SocialProviderUser $providerUser,
        SocialAccountService $socialAccountService,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user instanceof User, 403);
        abort_unless((int) ($pending['user_id'] ?? 0) === (int) $user->getKey(), 403);

        $socialAccountService->linkForAuthenticatedUser($user, $provider, $providerUser);

        return to_route('profile.edit')
            ->with('status', 'Metoda logowania została podpięta.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function pullPendingState(Request $request, string $provider): array
    {
        $state = (string) $request->query('state', '');
        $pending = $state !== ''
            ? $request->session()->pull("social_auth.$state")
            : null;

        if (! is_array($pending) || ($pending['provider'] ?? null) !== $provider) {
            throw ValidationException::withMessages([
                'provider' => 'Sesja logowania społecznościowego wygasła. Spróbuj ponownie.',
            ]);
        }

        return $pending;
    }

    /**
     * @param  array<string, mixed>|null  $pending
     */
    protected function redirectAfterSocialFailure(
        Request $request,
        ValidationException $exception,
        ?array $pending,
    ): RedirectResponse {
        return redirect($this->socialFailureTarget($request, $pending))
            ->withErrors($exception->errors());
    }

    /**
     * @param  array<string, mixed>|null  $pending
     */
    protected function socialFailureTarget(Request $request, ?array $pending): string
    {
        if (($pending['intent'] ?? null) === 'link' && $request->user() instanceof User) {
            return route('profile.edit', absolute: false);
        }

        if (($pending['target_category_id'] ?? null) !== null) {
            return route('register', absolute: false);
        }

        return route('login', absolute: false);
    }
}
