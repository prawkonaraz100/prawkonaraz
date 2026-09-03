<?php

namespace App\Support;

use App\Models\UserSocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class SocialAuthProviderClient
{
    /**
     * @return array<int, string>
     */
    public function supportedProviders(): array
    {
        return [
            UserSocialAccount::PROVIDER_GOOGLE,
            UserSocialAccount::PROVIDER_FACEBOOK,
        ];
    }

    public function assertSupported(string $provider): void
    {
        abort_unless(in_array($provider, $this->supportedProviders(), true), 404);
    }

    public function redirectUrl(string $provider, string $state): string
    {
        $this->assertSupported($provider);
        $config = $this->providerConfig($provider);

        if (blank($config['client_id']) || blank($config['client_secret'])) {
            throw ValidationException::withMessages([
                'provider' => 'Logowanie przez '.$this->providerLabel($provider).' nie jest jeszcze skonfigurowane.',
            ]);
        }

        $query = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $this->redirectUri($provider, $config),
            'response_type' => 'code',
            'scope' => $config['scope'],
            'state' => $state,
        ];

        if ($provider === UserSocialAccount::PROVIDER_GOOGLE) {
            $query['access_type'] = 'online';
            $query['prompt'] = 'select_account';
        }

        return $config['auth_url'].'?'.Arr::query($query);
    }

    public function user(string $provider, Request $request): SocialProviderUser
    {
        $this->assertSupported($provider);

        if ($request->filled('error')) {
            throw ValidationException::withMessages([
                'provider' => 'Logowanie przez '.$this->providerLabel($provider).' zostało przerwane. Możesz spróbować ponownie.',
            ]);
        }

        $code = (string) $request->query('code', '');

        if ($code === '') {
            throw ValidationException::withMessages([
                'provider' => 'Nie udało się dokończyć logowania przez '.$this->providerLabel($provider).'. Spróbuj ponownie.',
            ]);
        }

        $config = $this->providerConfig($provider);
        $tokenResponse = Http::asForm()
            ->post($config['token_url'], [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri($provider, $config),
            ]);

        if (! $tokenResponse->successful()) {
            throw ValidationException::withMessages([
                'provider' => 'Nie udało się potwierdzić logowania przez '.$this->providerLabel($provider).'. Spróbuj ponownie albo użyj logowania e-mailem.',
            ]);
        }

        $accessToken = (string) $tokenResponse->json('access_token', '');

        if ($accessToken === '') {
            throw ValidationException::withMessages([
                'provider' => $this->providerLabel($provider).' nie zwrócił danych potrzebnych do logowania. Spróbuj ponownie.',
            ]);
        }

        $profileResponse = $provider === UserSocialAccount::PROVIDER_FACEBOOK
            ? Http::withToken($accessToken)->get($config['user_url'], [
                'fields' => 'id,name,email,picture',
            ])
            : Http::withToken($accessToken)->get($config['user_url']);

        if (! $profileResponse->successful()) {
            throw ValidationException::withMessages([
                'provider' => 'Nie udało się pobrać profilu z '.$this->providerLabel($provider).'. Spróbuj ponownie.',
            ]);
        }

        $payload = $profileResponse->json();

        return $provider === UserSocialAccount::PROVIDER_FACEBOOK
            ? $this->facebookUser($payload)
            : $this->googleUser($payload);
    }

    /**
     * @return array<string, mixed>
     */
    protected function providerConfig(string $provider): array
    {
        $defaults = match ($provider) {
            UserSocialAccount::PROVIDER_GOOGLE => [
                'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token_url' => 'https://oauth2.googleapis.com/token',
                'user_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
                'scope' => 'openid profile email',
            ],
            UserSocialAccount::PROVIDER_FACEBOOK => [
                'auth_url' => 'https://www.facebook.com/v19.0/dialog/oauth',
                'token_url' => 'https://graph.facebook.com/v19.0/oauth/access_token',
                'user_url' => 'https://graph.facebook.com/me',
                'scope' => 'email,public_profile',
            ],
            default => [],
        };

        return [
            ...$defaults,
            ...config("services.$provider", []),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function redirectUri(string $provider, array $config): string
    {
        return filled($config['redirect'] ?? null)
            ? (string) $config['redirect']
            : route('social.callback', ['provider' => $provider]);
    }

    protected function providerLabel(string $provider): string
    {
        return match ($provider) {
            UserSocialAccount::PROVIDER_GOOGLE => 'Google',
            UserSocialAccount::PROVIDER_FACEBOOK => 'Facebook',
            default => 'platformę społecznościową',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function googleUser(array $payload): SocialProviderUser
    {
        return new SocialProviderUser(
            id: (string) ($payload['sub'] ?? ''),
            email: $payload['email'] ?? null,
            name: $payload['name'] ?? null,
            avatarUrl: $payload['picture'] ?? null,
            emailVerified: array_key_exists('email_verified', $payload)
                ? (bool) $payload['email_verified']
                : null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function facebookUser(array $payload): SocialProviderUser
    {
        return new SocialProviderUser(
            id: (string) ($payload['id'] ?? ''),
            email: $payload['email'] ?? null,
            name: $payload['name'] ?? null,
            avatarUrl: data_get($payload, 'picture.data.url'),
            emailVerified: null,
        );
    }
}
