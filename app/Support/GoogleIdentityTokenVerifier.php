<?php

namespace App\Support;

use Google\Client as GoogleClient;
use Illuminate\Validation\ValidationException;

class GoogleIdentityTokenVerifier
{
    public function userFromCredential(string $credential): SocialProviderUser
    {
        $clientId = (string) config('services.google.client_id', '');

        if (blank($clientId)) {
            throw ValidationException::withMessages([
                'provider' => 'Logowanie przez Google nie jest jeszcze skonfigurowane.',
            ]);
        }

        $payload = $this->verify($credential, $clientId);

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
     * @return array<string, mixed>
     */
    protected function verify(string $credential, string $clientId): array
    {
        $client = new GoogleClient(['client_id' => $clientId]);
        $payload = $client->verifyIdToken($credential);

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'credential' => 'Nie udało się potwierdzić logowania przez Google. Spróbuj ponownie albo użyj logowania e-mailem.',
            ]);
        }

        return $payload;
    }
}
