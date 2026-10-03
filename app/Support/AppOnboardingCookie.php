<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class AppOnboardingCookie
{
    public const NAME = 'prawkonaraz_onboarding_seen';

    public const VALUE = '1';

    private const LIFETIME_MINUTES = 60 * 24 * 365;

    public function wasSeen(Request $request): bool
    {
        return $request->cookie(self::NAME) === self::VALUE
            || $request->cookie(ReturningUserCookie::NAME) === ReturningUserCookie::VALUE;
    }

    public function queue(Request $request): void
    {
        $configuredSecure = config('session.secure');
        $secure = app()->environment('production')
            || ($configuredSecure !== null ? (bool) $configuredSecure : $request->isSecure());

        Cookie::queue(Cookie::make(
            self::NAME,
            self::VALUE,
            self::LIFETIME_MINUTES,
            config('session.path', '/'),
            config('session.domain') ?: null,
            $secure,
            false,
            false,
            'lax',
        ));
    }
}
