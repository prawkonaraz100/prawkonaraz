<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\VerificationEmailDeliveryFailed;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSocialAccount;
use App\Support\GoogleIdentityRegistrationSession;
use App\Support\ReturningUserCookie;
use App\Support\SocialAccountService;
use App\Support\SocialProviderUser;
use App\Support\StudyContextService;
use App\Support\UserIpHistoryService;
use App\Support\UserProfileService;
use App\Support\VerificationEmailDelivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        $studyContextService = app(StudyContextService::class);

        return Inertia::render('Auth/Register', [
            'categories' => $studyContextService->activeCategories()
                ->map(fn ($category) => [
                    'id' => $category->getKey(),
                    'code' => $category->code,
                    'name' => $category->name,
                    'short_name' => $studyContextService->shortCategoryName($category),
                ])
                ->values(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(
        Request $request,
        StudyContextService $studyContextService,
        UserProfileService $userProfileService,
        UserIpHistoryService $userIpHistoryService,
        ReturningUserCookie $returningUserCookie,
        GoogleIdentityRegistrationSession $googleIdentityRegistrationSession,
        SocialAccountService $socialAccountService,
        VerificationEmailDelivery $verificationEmailDelivery,
    ): RedirectResponse {
        $googleProviderUser = $googleIdentityRegistrationSession->providerUser($request);

        if ($googleProviderUser !== null) {
            return $this->storeGoogleIdentityRegistration(
                $request,
                $socialAccountService,
                $userIpHistoryService,
                $returningUserCookie,
                $googleIdentityRegistrationSession,
                $googleProviderUser,
            );
        }

        if (is_string($request->input('email'))) {
            $request->merge(['email' => Str::lower(trim($request->input('email')))]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'target_category_id' => ['required', 'integer'],
            'preferred_learning_track' => ['nullable', 'string', Rule::in($this->availableLearningTracks())],
        ]);

        $targetCategory = $studyContextService->activeCategories()
            ->firstWhere('id', (int) $validated['target_category_id']);

        if (! $targetCategory) {
            throw ValidationException::withMessages([
                'target_category_id' => 'Wybierz dostępną kategorię prawa jazdy.',
            ]);
        }

        $user = DB::transaction(function () use ($validated, $targetCategory, $userProfileService): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'password_login_enabled' => true,
            ]);

            $userProfileService->update($user, [
                'target_category_id' => $targetCategory->getKey(),
                'preferred_learning_track' => $validated['preferred_learning_track'] ?? null,
                'onboarding_step' => 'target_category_locked',
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        $userIpHistoryService->record($user, $request, 'rejestracja', force: true);
        $returningUserCookie->queue($request);

        $sent = $verificationEmailDelivery->registered($user);

        return redirect()->route('verification.notice')
            ->with('status', $sent ? 'verification-link-sent' : 'verification-link-failed');
    }

    protected function storeGoogleIdentityRegistration(
        Request $request,
        SocialAccountService $socialAccountService,
        UserIpHistoryService $userIpHistoryService,
        ReturningUserCookie $returningUserCookie,
        GoogleIdentityRegistrationSession $googleIdentityRegistrationSession,
        SocialProviderUser $providerUser,
    ): RedirectResponse {
        $validated = $request->validate([
            'target_category_id' => ['required', 'integer'],
            'preferred_learning_track' => ['nullable', 'string', Rule::in($this->availableLearningTracks())],
        ]);

        $deliveryFailed = false;
        try {
            $user = $socialAccountService->resolveForLogin(
                UserSocialAccount::PROVIDER_GOOGLE,
                $providerUser,
                (int) $validated['target_category_id'],
                $validated['preferred_learning_track'] ?? null,
            );
        } catch (VerificationEmailDeliveryFailed $exception) {
            $user = $exception->user;
            $deliveryFailed = true;
        }

        Auth::login($user);
        $request->session()->regenerate();

        $userIpHistoryService->record($user, $request, 'google_identity_registration', force: true);
        $returningUserCookie->queue($request);
        $googleIdentityRegistrationSession->forget($request);

        return $deliveryFailed
            ? to_route('verification.notice')->with('status', 'verification-link-failed')
            : redirect(route('dashboard', absolute: false));
    }

    /**
     * @return list<string>
     */
    private function availableLearningTracks(): array
    {
        return config('study.pjm_module_enabled', false)
            ? [UserProfile::LEARNING_TRACK_CLASSIC, UserProfile::LEARNING_TRACK_PJM]
            : [UserProfile::LEARNING_TRACK_CLASSIC];
    }
}
