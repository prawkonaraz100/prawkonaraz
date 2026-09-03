<?php

namespace App\Notifications;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ModeratorAccountStartCredentials extends Notification
{
    use Queueable;

    public function __construct(
        protected string $startPassword,
        protected string $targetCategoryCode,
        protected ?CarbonInterface $accessExpiresAt,
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Dane startowe do konta w PrawkoNaRaz.pl')
            ->greeting('Dzień dobry!')
            ->line('Moderator utworzył dla Ciebie konto w PrawkoNaRaz.pl.')
            ->line("Login: {$notifiable->email}")
            ->line("Hasło startowe: {$this->startPassword}")
            ->line("Kategoria nauki: {$this->targetCategoryCode}");

        if ($this->accessExpiresAt !== null) {
            $message->line('Dostęp jest ważny do: '.$this->accessExpiresAt
                ->copy()
                ->setTimezone(config('app.timezone'))
                ->format('d.m.Y H:i'));
        }

        return $message
            ->line('Kliknij przycisk poniżej, aby potwierdzić adres e-mail. Jeśli nie jesteś zalogowany/a, najpierw zaloguj się danymi z tej wiadomości.')
            ->line('Strona logowania: '.route('login'))
            ->action('Potwierdź e-mail i rozpocznij', $this->verificationUrl($notifiable))
            ->line('Po pierwszym wejściu ustawisz własne hasło.')
            ->line('Jeśli nie spodziewasz się tej wiadomości, skontaktuj się z moderatorem.');
    }

    protected function verificationUrl(User $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1((string) $notifiable->getEmailForVerification()),
            ],
        );
    }
}
