<?php

namespace App\Support;

use App\Exceptions\SocialAccountNeedsCategoryException;
use App\Models\LicenseCategory;
use App\Models\User;
use App\Models\UserSocialAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SocialAccountService
{
    public function resolveForLogin(
        string $provider,
        SocialProviderUser $providerUser,
        ?int $targetCategoryId = null,
        ?string $preferredLearningTrack = null,
    ): User {
        $this->ensureProviderUserIsUsable($providerUser);
        $emailTrusted = $this->providerEmailIsTrusted($provider, $providerUser);

        $linkedAccount = UserSocialAccount::query()
            ->with('user')
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUser->id)
            ->first();

        if ($linkedAccount !== null) {
            $this->updateLinkedAccount($linkedAccount, $providerUser);

            return $linkedAccount->user;
        }

        $email = $providerUser->normalizedEmail();
        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            if (! $emailTrusted) {
                throw ValidationException::withMessages([
                    'provider' => sprintf(
                        'Nie możemy automatycznie połączyć konta przez %s. Zaloguj się e-mailem i hasłem, a potem podepnij tę metodę w profilu.',
                        $this->providerLabel($provider),
                    ),
                ]);
            }

            $this->ensureExistingAccountCanUseSocialLogin($user, $targetCategoryId, $preferredLearningTrack);
            $this->linkProviderToUser($user, $provider, $providerUser, actor: $user);

            return $user;
        }

        if ($targetCategoryId === null) {
            throw new SocialAccountNeedsCategoryException('Social account registration requires a target category.');
        }

        $targetCategory = $this->targetCategory($targetCategoryId);

        $user = DB::transaction(function () use ($provider, $providerUser, $targetCategory, $preferredLearningTrack, $emailTrusted): User {
            $user = User::query()->create([
                'name' => $providerUser->name ?: $providerUser->normalizedEmail(),
                'email' => $providerUser->normalizedEmail(),
                'password' => Str::password(40),
                'password_login_enabled' => false,
            ]);

            if ($emailTrusted) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            app(UserProfileService::class)->update($user, [
                'target_category_id' => $targetCategory->getKey(),
                'preferred_learning_track' => $preferredLearningTrack,
                'onboarding_step' => 'target_category_locked',
            ]);

            $this->linkProviderToUser($user, $provider, $providerUser, actor: $user);

            app(AuditLogService::class)->record(
                'social.account_registered',
                'user',
                (string) $user->getKey(),
                $user,
                [
                    'provider' => $provider,
                    'target_category_id' => $targetCategory->getKey(),
                    'target_category_code' => $targetCategory->code,
                ],
            );

            return $user;
        });

        if (! $emailTrusted) {
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }

    public function linkForAuthenticatedUser(User $user, string $provider, SocialProviderUser $providerUser): UserSocialAccount
    {
        $this->ensureProviderUserIsUsable($providerUser);

        if ($providerUser->normalizedEmail() !== strtolower((string) $user->email)) {
            throw ValidationException::withMessages([
                'provider' => 'To konto społecznościowe ma inny e-mail niż Twoje konto w serwisie.',
            ]);
        }

        return $this->linkProviderToUser($user, $provider, $providerUser, actor: $user);
    }

    public function resolveExistingForLogin(string $provider, SocialProviderUser $providerUser): User
    {
        $this->ensureProviderUserIsUsable($providerUser);
        $emailTrusted = $this->providerEmailIsTrusted($provider, $providerUser);

        $linkedAccount = UserSocialAccount::query()
            ->with('user')
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUser->id)
            ->first();

        if ($linkedAccount !== null) {
            $this->updateLinkedAccount($linkedAccount, $providerUser);

            return $linkedAccount->user;
        }

        $email = $providerUser->normalizedEmail();
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            throw new SocialAccountNeedsCategoryException('Social account registration requires a target category.');
        }

        if (! $emailTrusted) {
            throw ValidationException::withMessages([
                'provider' => sprintf(
                    'Nie możemy automatycznie połączyć konta przez %s. Zaloguj się e-mailem i hasłem, a potem podepnij tę metodę w profilu.',
                    $this->providerLabel($provider),
                ),
            ]);
        }

        $this->ensureExistingAccountCanUseSocialLogin($user, null);
        $this->linkProviderToUser($user, $provider, $providerUser, actor: $user);

        return $user;
    }

    public function unlink(User $user, string $provider): void
    {
        $account = $user->socialAccounts()
            ->where('provider', $provider)
            ->first();

        if ($account === null) {
            throw ValidationException::withMessages([
                'provider' => 'Ta metoda logowania nie jest podpięta do konta.',
            ]);
        }

        if (! $user->password_login_enabled && $user->socialAccounts()->count() <= 1) {
            throw ValidationException::withMessages([
                'provider' => 'Nie możesz odpiąć ostatniej metody logowania. Najpierw ustaw hasło do konta.',
            ]);
        }

        $account->delete();

        app(AuditLogService::class)->record(
            'social.account_unlinked',
            'user',
            (string) $user->getKey(),
            $user,
            ['provider' => $provider],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function connectionsFor(User $user): array
    {
        $accounts = $user->socialAccounts()
            ->get()
            ->keyBy('provider');
        $providerLabels = collect([
            UserSocialAccount::PROVIDER_GOOGLE => 'Google',
            UserSocialAccount::PROVIDER_FACEBOOK => 'Facebook',
        ]);

        return [
            'password_login_enabled' => (bool) $user->password_login_enabled,
            'providers' => $providerLabels
                ->filter(fn (string $label, string $provider): bool => $accounts->has($provider)
                    || (filled(config("services.$provider.client_id"))
                        && filled(config("services.$provider.client_secret"))))
                ->map(fn (string $label, string $provider): array => [
                    'provider' => $provider,
                    'label' => $label,
                    'connected' => $accounts->has($provider),
                    'email' => $accounts->get($provider)?->email,
                    'linked_at' => $accounts->get($provider)?->linked_at?->toIso8601String(),
                ])->values()->all(),
        ];
    }

    protected function ensureProviderUserIsUsable(SocialProviderUser $providerUser): void
    {
        if ($providerUser->id === '') {
            throw ValidationException::withMessages([
                'provider' => 'Platforma społecznościowa nie zwróciła stabilnego identyfikatora konta.',
            ]);
        }

        if ($providerUser->normalizedEmail() === null) {
            throw ValidationException::withMessages([
                'provider' => 'Platforma społecznościowa nie zwróciła adresu e-mail. Użyj standardowego logowania.',
            ]);
        }

        if ($providerUser->emailVerified === false) {
            throw ValidationException::withMessages([
                'provider' => 'Adres e-mail z platformy społecznościowej nie jest potwierdzony.',
            ]);
        }
    }

    protected function providerEmailIsTrusted(string $provider, SocialProviderUser $providerUser): bool
    {
        return $provider === UserSocialAccount::PROVIDER_GOOGLE
            && $providerUser->emailVerified === true;
    }

    protected function providerLabel(string $provider): string
    {
        return match ($provider) {
            UserSocialAccount::PROVIDER_GOOGLE => 'Google',
            UserSocialAccount::PROVIDER_FACEBOOK => 'Facebook',
            default => 'platformę społecznościową',
        };
    }

    protected function ensureExistingAccountCanUseSocialLogin(
        User $user,
        ?int $targetCategoryId,
        ?string $preferredLearningTrack = null,
    ): void {
        $user->loadMissing('profile:user_id,target_category_id');

        if ($user->canUseAllStudyCategories() || $user->profile?->target_category_id !== null) {
            return;
        }

        if ($targetCategoryId === null) {
            throw new SocialAccountNeedsCategoryException('Existing social account login requires a target category.');
        }

        $targetCategory = $this->targetCategory($targetCategoryId);

        app(UserProfileService::class)->update($user, [
            'target_category_id' => $targetCategory->getKey(),
            'preferred_learning_track' => $preferredLearningTrack,
            'onboarding_step' => 'target_category_locked',
        ]);
    }

    protected function linkProviderToUser(
        User $user,
        string $provider,
        SocialProviderUser $providerUser,
        ?User $actor = null,
    ): UserSocialAccount {
        $linkedToOtherUser = UserSocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUser->id)
            ->where('user_id', '!=', $user->getKey())
            ->exists();

        if ($linkedToOtherUser) {
            throw ValidationException::withMessages([
                'provider' => 'To konto społecznościowe jest już podpięte do innego użytkownika.',
            ]);
        }

        $account = UserSocialAccount::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'provider' => $provider,
            ],
            [
                'provider_user_id' => $providerUser->id,
                'email' => $providerUser->normalizedEmail(),
                'display_name' => $providerUser->name,
                'avatar_url' => $providerUser->avatarUrl,
                'linked_at' => now(),
            ],
        );

        app(AuditLogService::class)->record(
            'social.account_linked',
            'user',
            (string) $user->getKey(),
            $actor,
            ['provider' => $provider],
        );

        return $account;
    }

    protected function updateLinkedAccount(UserSocialAccount $account, SocialProviderUser $providerUser): void
    {
        $account->forceFill([
            'email' => $providerUser->normalizedEmail(),
            'display_name' => $providerUser->name,
            'avatar_url' => $providerUser->avatarUrl,
            'linked_at' => $account->linked_at ?? now(),
        ])->save();
    }

    protected function targetCategory(int $targetCategoryId): LicenseCategory
    {
        $targetCategory = app(StudyContextService::class)
            ->activeCategories()
            ->firstWhere('id', $targetCategoryId);

        if (! $targetCategory instanceof LicenseCategory) {
            throw ValidationException::withMessages([
                'target_category_id' => 'Wybierz dostępną kategorię prawa jazdy.',
            ]);
        }

        return $targetCategory;
    }
}
