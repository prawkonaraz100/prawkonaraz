<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ConfirmAccountDeletion extends Notification
{
    use Queueable;

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
            ->subject('Potwierdzenie usunięcia konta')
            ->greeting('Cześć!')
            ->line('Otrzymaliśmy prośbę o usunięcie Twojego konta w prawkonaraz.pl.')
            ->line('Kliknij przycisk poniżej, żeby przejść do ostatecznego potwierdzenia. Link wygaśnie za 30 minut.')
            ->action('Potwierdź usunięcie konta', $this->confirmationUrl($notifiable))
            ->line('Jeśli to nie Ty prosisz o usunięcie konta, zignoruj tę wiadomość.');
    }

    public function confirmationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'profile.deletion.confirm',
            now()->addMinutes(30),
            [
                'user' => $user->getKey(),
                'hash' => sha1((string) $user->email),
            ],
        );
    }
}
