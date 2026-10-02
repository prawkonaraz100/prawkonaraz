<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class VerificationEmailDelivery
{
    public function registered(User $user): bool
    {
        return $this->attempt(fn () => event(new Registered($user)));
    }

    public function send(User $user): bool
    {
        return $this->attempt(fn () => $user->sendEmailVerificationNotification());
    }

    private function attempt(callable $send): bool
    {
        try {
            $send();

            return true;
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return false;
        }
    }
}
