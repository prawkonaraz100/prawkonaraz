<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ReturningUserCookie
{
    public const NAME = 'prawkonaraz_returning_user';

    public const VALUE = '1';

    private const LIFETIME_MINUTES = 60 * 24 * 180;

    public function queue(Request $request): void
    {
        Cookie::queue(Cookie::make(
            self::NAME,
            self::VALUE,
            self::LIFETIME_MINUTES,
            config('session.path', '/'),
            config('session.domain') ?: null,
            $this->secure($request),
            false,
            false,
            'lax',
        ));
    }

    private function secure(Request $request): bool
    {
        if (app()->environment('production')) {
            return true;
        }

        $configured = config('session.secure');

        if ($configured !== null) {
            return (bool) $configured;
        }

        return $request->isSecure() || app()->environment('production');
    }
}
