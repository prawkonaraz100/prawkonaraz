<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RankedWebSocketTicketService
{
    public function issueForUser(Authenticatable $user): string
    {
        $ticket = Str::random(64);

        $this->cache()->put(
            $this->cacheKey($ticket),
            [
                'user_id' => $user->getAuthIdentifier(),
            ],
            now()->addSeconds($this->ttlSeconds()),
        );

        return $ticket;
    }

    public function issueWebSocketUrlForUser(Authenticatable $user): ?string
    {
        $baseUrl = config('ranked.websocket_url');

        if (! is_string($baseUrl) || $baseUrl === '') {
            return null;
        }

        $ticket = $this->issueForUser($user);
        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl.$separator.'ticket='.rawurlencode($ticket);
    }

    public function resolveUserForTicket(?string $ticket): ?User
    {
        if (! is_string($ticket) || $ticket === '') {
            return null;
        }

        /** @var array{user_id?: mixed}|null $payload */
        $payload = $this->cache()->get($this->cacheKey($ticket));
        $userId = $payload['user_id'] ?? null;

        if (! is_numeric($userId)) {
            return null;
        }

        return User::query()->find((int) $userId);
    }

    protected function cache(): Repository
    {
        $store = config('ranked.websocket_ticket_store');

        return is_string($store) && $store !== ''
            ? Cache::store($store)
            : Cache::store();
    }

    protected function cacheKey(string $ticket): string
    {
        return sprintf(
            '%s:%s',
            (string) config('ranked.websocket_ticket_key_prefix', 'ranked:websocket-ticket'),
            $ticket,
        );
    }

    protected function ttlSeconds(): int
    {
        return max(30, (int) config('ranked.websocket_ticket_ttl_seconds', 180));
    }
}
