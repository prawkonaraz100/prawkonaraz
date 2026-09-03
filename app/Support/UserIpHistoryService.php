<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserIpHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UserIpHistoryService
{
    public function __construct(
        protected IpGeolocationService $ipGeolocationService,
    ) {}

    public function record(User $user, Request $request, string $source, bool $force = false): void
    {
        $ipAddress = trim((string) $request->ip());

        if ($ipAddress === '') {
            return;
        }

        $session = $request->hasSession() ? $request->session() : null;
        $sessionId = $session?->getId();

        if (! $force && $session !== null && $sessionId !== null) {
            $throttleKey = sprintf('_ip_history.%s.%s', $source, sha1($ipAddress));
            $lastLoggedAt = $session->get($throttleKey);

            if (is_string($lastLoggedAt) && $lastLoggedAt !== '') {
                $lastLoggedAtCarbon = Carbon::parse($lastLoggedAt);

                if ($lastLoggedAtCarbon->gt(now()->subMinutes(15))) {
                    return;
                }
            }
        }

        $query = UserIpHistory::query()
            ->where('user_id', $user->getKey())
            ->where('source', $source)
            ->where('ip_address', Str::limit($ipAddress, 45, ''));

        if ($sessionId !== null) {
            $query->where('session_id', Str::limit($sessionId, 255, ''));
        } else {
            $query->whereNull('session_id');
        }

        $history = $query->latest('last_seen_at')->first();

        if ($history instanceof UserIpHistory) {
            $geolocation = (! filled($history->country_name) && ! filled($history->city_name))
                ? $this->ipGeolocationService->lookup($ipAddress)
                : null;

            $history->forceFill([
                'country_code' => $geolocation['country_code'] ?? $history->country_code,
                'country_name' => $geolocation['country_name'] ?? $history->country_name,
                'city_name' => $geolocation['city_name'] ?? $history->city_name,
                'path' => $this->requestPath($request),
                'request_id' => $this->requestId($request),
                'user_agent' => $this->userAgent($request),
                'last_seen_at' => now(),
                'hit_count' => $history->hit_count + 1,
            ])->save();
        } else {
            $geolocation = $this->ipGeolocationService->lookup($ipAddress);

            UserIpHistory::query()->create([
                'user_id' => $user->getKey(),
                'source' => $source,
                'ip_address' => Str::limit($ipAddress, 45, ''),
                'country_code' => $geolocation['country_code'] ?? null,
                'country_name' => $geolocation['country_name'] ?? null,
                'city_name' => $geolocation['city_name'] ?? null,
                'user_agent' => $this->userAgent($request),
                'path' => $this->requestPath($request),
                'session_id' => $sessionId !== null ? Str::limit($sessionId, 255, '') : null,
                'request_id' => $this->requestId($request),
                'hit_count' => 1,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);
        }

        if ($session !== null && $sessionId !== null) {
            $session->put(sprintf('_ip_history.%s.%s', $source, sha1($ipAddress)), now()->toIso8601String());
        }
    }

    protected function requestPath(Request $request): ?string
    {
        $path = trim($request->path(), '/');

        return $path !== '' ? Str::limit($path, 255, '') : null;
    }

    protected function requestId(Request $request): ?string
    {
        $requestId = $request->attributes->get('request_id');

        return $requestId !== null ? Str::limit((string) $requestId, 120, '') : null;
    }

    protected function userAgent(Request $request): ?string
    {
        $userAgent = $request->userAgent();

        return filled($userAgent) ? Str::limit((string) $userAgent, 1000, '') : null;
    }
}
