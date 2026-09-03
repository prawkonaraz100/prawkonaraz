<?php

namespace App\Support;

use App\Models\RankedMatch;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class RankedMatchPresenceStore
{
    public function touch(RankedMatch|string $match, int $userId, Carbon $seenAt, ?int $ttlSeconds = null): void
    {
        $this->store()->put(
            $this->keyFor($match, $userId),
            $seenAt->toIso8601String(),
            now()->addSeconds($ttlSeconds ?? $this->ttlSeconds()),
        );
    }

    /**
     * @param  iterable<int>  $userIds
     */
    public function touchMany(RankedMatch|string $match, iterable $userIds, Carbon $seenAt, ?int $ttlSeconds = null): void
    {
        foreach ($userIds as $userId) {
            $this->touch($match, (int) $userId, $seenAt, $ttlSeconds);
        }
    }

    public function lastSeenAt(RankedMatch|string $match, int $userId): ?Carbon
    {
        $value = $this->store()->get($this->keyFor($match, $userId));

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function forget(RankedMatch|string $match, int $userId): void
    {
        $this->store()->forget($this->keyFor($match, $userId));
    }

    /**
     * @param  iterable<int>  $userIds
     */
    public function forgetMany(RankedMatch|string $match, iterable $userIds): void
    {
        foreach ($userIds as $userId) {
            $this->forget($match, (int) $userId);
        }
    }

    public function keyFor(RankedMatch|string $match, int $userId): string
    {
        $matchPublicId = $match instanceof RankedMatch ? $match->public_id : $match;
        $prefix = trim((string) config('ranked.presence_key_prefix', 'ranked:presence'), ':');

        return sprintf('%s:match:%s:user:%d', $prefix, $matchPublicId, $userId);
    }

    protected function ttlSeconds(): int
    {
        return max((int) config('ranked.presence_ttl_seconds', 90), 1);
    }

    protected function store(): Repository
    {
        return Cache::store((string) config('ranked.presence_store', config('cache.default')));
    }
}
