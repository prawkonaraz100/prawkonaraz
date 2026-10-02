<?php

namespace App\Support;

use Illuminate\Notifications\Messages\MailMessage;

class VerificationEmailMessage
{
    public function build(string $url): MailMessage
    {
        $expiresInMinutes = (int) config('auth.verification.expire', 60);
        $expiryLabel = $expiresInMinutes === 60 ? '1 godzinie' : $expiresInMinutes.' minutach';

        return (new MailMessage)
            ->subject('Potwierdź adres e-mail')
            ->line('Dziękujemy za rejestrację w PrawkoNaRaz.')
            ->line('Kliknij przycisk poniżej, aby aktywować konto i rozpocząć naukę.')
            ->action('Potwierdź adres e-mail', $url)
            ->line('Jeśli to nie Ty, zignoruj tę wiadomość.')
            ->view([
                'html' => 'emails.auth.verify-email',
                'text' => 'emails.auth.verify-email-text',
            ], [
                'verificationUrl' => $url,
                'expiryLabel' => $expiryLabel,
                'year' => now()->year,
                'homeUrl' => url('/'),
            ]);
    }
}
