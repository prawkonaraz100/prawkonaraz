<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpsertUserProductProfileRequest;
use App\Models\LicenseCategory;
use App\Models\User;
use App\Notifications\ConfirmAccountDeletion;
use App\Notifications\ConfirmEmailChange;
use App\Support\FriendInvitationProfilePresenter;
use App\Support\SocialAccountService;
use App\Support\StudyContextService;
use App\Support\UserProfileService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(
        Request $request,
        UserProfileService $userProfileService,
        StudyContextService $studyContextService,
        SocialAccountService $socialAccountService,
        FriendInvitationProfilePresenter $friendInvitationProfilePresenter,
    ): Response {
        $profile = $userProfileService->profileFor($request->user())->load('targetCategory');
        $generatedInvitation = $request->session()->get('friend_invitation_created');

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'categories' => $studyContextService->selectableCategoriesForProfile($request->user())
                ->map(fn (LicenseCategory $category) => [
                    'id' => $category->getKey(),
                    'code' => $category->code,
                    'name' => $category->name,
                    'short_name' => $studyContextService->shortCategoryName($category),
                ])
                ->values(),
            'productProfile' => [
                'display_name' => $profile->display_name,
                'target_category_id' => $profile->target_category_id,
                'exam_date' => $profile->exam_date?->toDateString(),
                'study_streak' => $profile->study_streak,
                'last_study_date' => $profile->last_study_date?->toDateString(),
                'tier' => $profile->tier,
                'onboarding_step' => $profile->onboarding_step,
                'visual_explanations_enabled' => $profile->visual_explanations_enabled,
                'visual_explanations_mode' => $profile->visual_explanations_mode ?: 'after_incorrect',
                'auto_remove_incorrect_questions_on_correct' => $profile->auto_remove_incorrect_questions_on_correct,
            ],
            'canChangeTargetCategory' => $studyContextService->canChangeTargetCategory($request->user()),
            'categoryLockMessage' => $studyContextService->canChangeTargetCategory($request->user())
                ? null
                : 'Kategoria nauki jest przypisana na stałe. Jeśli musisz ją zmienić, skontaktuj się z administratorem.',
            'socialConnections' => $socialAccountService->connectionsFor($request->user()),
            'friendInvitations' => $friendInvitationProfilePresenter->forOwner(
                $request->user(),
                is_array($generatedInvitation) ? $generatedInvitation : null,
            ),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $emailChanged = strcasecmp((string) $validated['email'], (string) $user->email) !== 0;

        if ($emailChanged && $user->password_login_enabled) {
            $request->validate([
                'current_password' => ['required', 'current_password'],
            ]);
        }

        if ($emailChanged && ! $user->password_login_enabled) {
            $user->forceFill([
                'name' => $validated['name'],
            ])->save();

            $user->notify(new ConfirmEmailChange($validated['email']));

            return Redirect::route('profile.edit')->with('status', 'email-change-confirmation-sent');
        }

        $user->fill($validated);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged && $user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();

            return Redirect::route('verification.notice')->with('status', 'verification-link-sent');
        }

        return Redirect::route('profile.edit');
    }

    public function confirmEmailChange(Request $request, User $user, string $hash): View
    {
        $newEmail = $this->validatedEmailChangeAddress($request, $user, $hash);

        return view('profile.confirm-email-change', [
            'user' => $user,
            'newEmail' => $newEmail,
            'action' => $request->fullUrl(),
        ]);
    }

    public function updateEmailViaSignedLink(Request $request, User $user, string $hash): RedirectResponse
    {
        $newEmail = $this->validatedEmailChangeAddress($request, $user, $hash);

        $user->forceFill([
            'email' => $newEmail,
            'email_verified_at' => null,
        ])->save();

        if ($user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();
        }

        if (Auth::id() === $user->getKey()) {
            return Redirect::route('verification.notice')->with('status', 'email-change-confirmed');
        }

        return Redirect::route('login')->with('status', 'Adres e-mail został zmieniony. Sprawdź nową skrzynkę i potwierdź adres.');
    }

    public function updateProductProfile(
        UpsertUserProductProfileRequest $request,
        UserProfileService $userProfileService,
    ): RedirectResponse {
        $userProfileService->update($request->user(), $request->validated());

        $returnTo = $request->input('return_to', '');

        if (is_string($returnTo)
            && Str::startsWith($returnTo, '/')
            && ! Str::startsWith($returnTo, '//')
            && ! str_contains($returnTo, '\\')
            && ! preg_match('/[\x00-\x1F\x7F]/', $returnTo)) {
            return Redirect::to($returnTo);
        }

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        return $this->deleteUserAccount($request, $request->user());
    }

    public function sendDeletionConfirmation(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->password_login_enabled) {
            throw ValidationException::withMessages([
                'email' => 'To konto ma ustawione hasło. Potwierdź usunięcie hasłem.',
            ]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        if (strtolower((string) $validated['email']) !== strtolower((string) $user->email)) {
            throw ValidationException::withMessages([
                'email' => 'Wpisz adres e-mail przypisany do tego konta.',
            ]);
        }

        $user->notify(new ConfirmAccountDeletion);

        return back()->with('status', 'account-deletion-link-sent');
    }

    public function confirmDeletion(Request $request, User $user, string $hash): View
    {
        $this->ensureDeletionHashMatches($user, $hash);

        return view('profile.confirm-delete', [
            'user' => $user,
            'action' => $request->fullUrl(),
        ]);
    }

    public function destroyViaSignedLink(Request $request, User $user, string $hash): RedirectResponse
    {
        $this->ensureDeletionHashMatches($user, $hash);

        return $this->deleteUserAccount($request, $user);
    }

    protected function ensureDeletionHashMatches(User $user, string $hash): void
    {
        abort_unless(hash_equals(sha1((string) $user->email), $hash), 403);
    }

    protected function validatedEmailChangeAddress(Request $request, User $user, string $hash): string
    {
        abort_unless(hash_equals(sha1((string) $user->email), $hash), 403);

        try {
            $newEmail = Crypt::decryptString((string) $request->query('email', ''));
        } catch (\Throwable) {
            abort(403);
        }

        $validator = validator(
            ['email' => $newEmail],
            [
                'email' => [
                    'required',
                    'string',
                    'lowercase',
                    'email',
                    'max:255',
                    Rule::unique(User::class)->ignore($user->getKey()),
                ],
            ],
        );

        abort_if($validator->fails(), 409, 'Ten adres e-mail nie jest już dostępny.');

        return (string) $validator->validated()['email'];
    }

    protected function deleteUserAccount(Request $request, User $user): RedirectResponse
    {
        if (Auth::id() === $user->getKey()) {
            Auth::logout();
        }

        DB::table('sessions')->where('user_id', $user->getKey())->delete();
        $user->delete();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return Redirect::to('/');
    }
}
