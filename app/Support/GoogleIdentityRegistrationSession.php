<?php

namespace App\Support;

use App\Models\UserSocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

class GoogleIdentityRegistrationSession
{
    public const SESSION_KEY = 'google_identity_registration';

    private const EXPIRES_IN_MINUTES = 15;

    public function start(Request $request, SocialProviderUser $providerUser): void
    {
        $request->session()->put(self::SESSION_KEY, [
            'provider' => UserSocialAccount::PROVIDER_GOOGLE,
            'provider_user_id' => $providerUser->id,
            'email' => $providerUser->normalizedEmail(),
            'name' => $providerUser->name,
            'avatar_url' => $providerUser->avatarUrl,
            'email_verified' => $providerUser->emailVerified,
            'expires_at' => now()->addMinutes(self::EXPIRES_IN_MINUTES)->toIso8601String(),
        ]);
    }

    /**
     * @return array{pending: bool, email: string, name: string|null, avatarUrl: string|null, expiresAt: string}|null
     */
    public function payload(Request $request): ?array
    {
        $context = $this->context($request);

        if ($context === null) {
            return null;
        }

        return [
            'pending' => true,
            'email' => $context['email'],
            'name' => $context['name'],
            'avatarUrl' => $context['avatar_url'],
            'expiresAt' => $context['expires_at'],
        ];
    }

    public function providerUser(Request $request): ?SocialProviderUser
    {
        $context = $this->context($request);

        if ($context === null) {
            return null;
        }

        return new SocialProviderUser(
            id: $context['provider_user_id'],
            email: $context['email'],
            name: $context['name'],
            avatarUrl: $context['avatar_url'],
            emailVerified: $context['email_verified'],
        );
    }

    public function forget(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    /**
     * @return array{provider_user_id: string, email: string, name: string|null, avatar_url: string|null, email_verified: bool|null, expires_at: string}|null
     */
    private function context(Request $request): ?array
    {
        $context = $this->normalize($request->session()->get(self::SESSION_KEY));

        if ($context === null) {
            $this->forget($request);
        }

        return $context;
    }

    /**
     * @return array{provider_user_id: string, email: string, name: string|null, avatar_url: string|null, email_verified: bool|null, expires_at: string}|null
     */
    private function normalize(mixed $context): ?array
    {
        if (! is_array($context) || ($context['provider'] ?? null) !== UserSocialAccount::PROVIDER_GOOGLE) {
            return null;
        }

        try {
            $expiresAt = Carbon::parse((string) ($context['expires_at'] ?? ''));
        } catch (Throwable) {
            return null;
        }

        if ($expiresAt->isPast()) {
            return null;
        }

        $providerUserId = trim((string) ($context['provider_user_id'] ?? ''));
        $email = strtolower(trim((string) ($context['email'] ?? '')));

        if ($providerUserId === '' || $email === '') {
            return null;
        }

        return [
            'provider_user_id' => $providerUserId,
            'email' => $email,
            'name' => filled($context['name'] ?? null) ? (string) $context['name'] : null,
            'avatar_url' => filled($context['avatar_url'] ?? null) ? (string) $context['avatar_url'] : null,
            'email_verified' => array_key_exists('email_verified', $context)
                ? (is_bool($context['email_verified']) ? $context['email_verified'] : null)
                : null,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}
