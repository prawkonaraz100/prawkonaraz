<?php

namespace App\Support;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Lekkie sygnały dla strumienia rankingu (etap 1e), trzymane w tym samym magazynie co obecność (Redis).
 *
 * - Wersja użytkownika: zmienia się po każdym zatwierdzonym zdarzeniu meczu i zmianie w kolejce.
 *   Strumień porównuje ją co kilkaset ms (jeden GET) i liczy pełny stan tylko po zmianie.
 * - Puls zegara: `ranked:clock` zapisuje go co tick. Strumień przechodzi w tryb lekki
 *   tylko przy świeżym pulsie; gdy zegar stoi, wraca do pełnego liczenia co tick.
 */
class RankedRealtimeSignal
{
    protected const USER_VERSION_TTL_SECONDS = 3600;

    /**
     * Zmienia wersję użytkowników po zatwierdzeniu bieżącej transakcji. Gdyby zmienić ją wcześniej,
     * strumień mógłby przeczytać nową wersję, nie zobaczyć jeszcze niezatwierdzonego zdarzenia
     * i dostarczyć je dopiero przy wymuszonym odświeżeniu.
     *
     * @param  iterable<int|string>  $userIds
     */
    public function bumpUsersAfterCommit(iterable $userIds): void
    {
        $ids = collect($userIds)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

        if ($ids === []) {
            return;
        }

        DB::afterCommit(function () use ($ids): void {
            $this->bumpUsers($ids);
        });
    }

    /**
     * @param  iterable<int|string>  $userIds
     */
    public function bumpUsers(iterable $userIds): void
    {
        $version = Str::random(12);

        foreach ($userIds as $userId) {
            try {
                $this->store()->put($this->userKey((int) $userId), $version, self::USER_VERSION_TTL_SECONDS);
            } catch (\Throwable $exception) {
                // Brak sygnału oznacza tylko wolniejsze dostarczenie (wymuszone odświeżenie), nie błąd żądania.
                report($exception);
            }
        }
    }

    public function userVersion(int $userId): ?string
    {
        try {
            $value = $this->store()->get($this->userKey($userId));
        } catch (\Throwable) {
            return null;
        }

        return is_scalar($value) ? (string) $value : null;
    }

    public function recordClockPulse(?float $at = null): void
    {
        $this->store()->put($this->clockPulseKey(), (string) ($at ?? microtime(true)), 60);
    }

    public function clockAlive(?float $now = null): bool
    {
        if (! (bool) config('ranked.clock_enabled', false)) {
            return false;
        }

        try {
            $pulse = $this->store()->get($this->clockPulseKey());
        } catch (\Throwable) {
            return false;
        }

        if (! is_numeric($pulse)) {
            return false;
        }

        return (($now ?? microtime(true)) - (float) $pulse) <= max((float) config('ranked.clock_stale_seconds', 5), 1.0);
    }

    /**
     * Dzierżawa zegara: najwyżej jedna instancja `ranked:clock` liczy przejścia naraz.
     */
    public function acquireClockLease(string $ownerId, int $ttlSeconds): bool
    {
        $store = $this->store();
        $key = $this->clockLeaseKey();

        if ($store->add($key, $ownerId, $ttlSeconds)) {
            return true;
        }

        if ($store->get($key) === $ownerId) {
            $store->put($key, $ownerId, $ttlSeconds);

            return true;
        }

        return false;
    }

    public function releaseClockLease(string $ownerId): void
    {
        if ($this->store()->get($this->clockLeaseKey()) === $ownerId) {
            $this->store()->forget($this->clockLeaseKey());
        }
    }

    public function requestClockRestart(?float $at = null): void
    {
        $this->store()->forever($this->clockRestartKey(), (string) ($at ?? microtime(true)));
    }

    public function clockRestartRequestedAfter(float $startedAt): bool
    {
        $value = $this->store()->get($this->clockRestartKey());

        return is_numeric($value) && (float) $value > $startedAt;
    }

    public function userKey(int $userId): string
    {
        return $this->prefix().':rt:user:'.$userId;
    }

    protected function clockPulseKey(): string
    {
        return $this->prefix().':clock:pulse';
    }

    protected function clockLeaseKey(): string
    {
        return $this->prefix().':clock:lease';
    }

    protected function clockRestartKey(): string
    {
        return $this->prefix().':clock:restart';
    }

    protected function prefix(): string
    {
        // Ten sam przedrostek co obecność („ranked:presence”), ale bez części „presence”.
        $presencePrefix = trim((string) config('ranked.presence_key_prefix', 'ranked:presence'), ':');

        return str_ends_with($presencePrefix, ':presence')
            ? substr($presencePrefix, 0, -strlen(':presence'))
            : $presencePrefix;
    }

    protected function store(): Repository
    {
        return Cache::store((string) config('ranked.presence_store', config('cache.default')));
    }
}
