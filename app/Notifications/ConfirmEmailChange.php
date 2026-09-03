<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;

class ConfirmEmailChange extends Notification
{
    use Queueable;

    public function __construct(
        protected string $newEmail,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Potwierdź zmianę adresu e-mail')
            ->greeting('Cześć!')
            ->line('Otrzymaliśmy prośbę o zmianę adresu e-mail dla Twojego konta w prawkonaraz.pl.')
            ->line("Obecny adres: {$notifiable->email}")
            ->line("Nowy adres: {$this->newEmail}")
            ->line('Kliknij przycisk poniżej, żeby potwierdzić zmianę. Link wygaśnie za 30 minut.')
            ->action('Potwierdź zmianę e-maila', $this->confirmationUrl($notifiable))
            ->line('Po potwierdzeniu wyślemy osobny link weryfikacyjny na nowy adres e-mail.')
            ->line('Jeśli to nie Ty prosisz o zmianę adresu, zignoruj tę wiadomość.');
    }

    public function confirmationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'profile.email-change.confirm',
            now()->addMinutes(30),
            [
                'user' => $user->getKey(),
                'hash' => sha1((string) $user->email),
                'email' => Crypt::encryptString($this->newEmail),
            ],
        );
    }
}
