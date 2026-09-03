<?php

namespace App\Notifications;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FriendInvitationGuestAccessShortenedAfterRefund extends Notification
{
    use Queueable;

    public function __construct(
        protected CarbonInterface $accessExpiresAt,
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
        $expiresAt = $this->accessExpiresAt->copy()->setTimezone(config('app.timezone'))->format('d.m.Y H:i');

        return (new MailMessage)
            ->subject('Twój dostęp gościa wygasa za 7 dni')
            ->greeting('Dzień dobry!')
            ->line('Niestety, osoba zapraszająca Cię dokonała zwrotu pieniędzy za swój plan.')
            ->line("Z tego powodu Twój dostęp jako gościa wygasa dokładnie {$expiresAt}.")
            ->line('Do tego czasu możesz nadal korzystać z nauki Premium.')
            ->action('Przejdź do nauki', route('session.index'))
            ->line('Dziękujemy, że skorzystałeś/aś z możliwości wspólnej nauki.');
    }

    public function accessExpiresAt(): CarbonInterface
    {
        return $this->accessExpiresAt;
    }
}
