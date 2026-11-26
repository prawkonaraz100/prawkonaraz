<?php

namespace App\Support;

use App\Models\User;
use Closure;

/**
 * Pętla jednego połączenia SSE rankingu.
 *
 * Tryb pełny (dawny): co sleep_ms liczy cały stan gracza (overview, zdarzenia, obecność).
 * Tryb lekki (etap 1e, gdy `ranked:clock` działa): co sse_poll_ms czyta tylko wersję użytkownika
 * z Redis, a pełny stan liczy, gdy wersja się zmieni albo minie sse_full_refresh_seconds
 * (wtedy też odświeża obecność i wysyła heartbeat). Przejścia czasowe meczów liczy zegar.
 * Gdy zegar przestanie wysyłać puls, pętla w locie wraca do odświeżania co sleep_ms.
 */
class RankedRealtimeStreamLoop
{
    /** @var Closure(): float */
    protected Closure $clock;

    /** @var Closure(int): void */
    protected Closure $sleeper;

    /** @var Closure(): bool */
    protected Closure $aborted;

    public function __construct(
        protected RankedRealtimeEventStreamPublisher $publisher,
        protected RankedRealtimeSignal $signal,
    ) {
        $this->clock = fn (): float => microtime(true);
        $this->sleeper = function (int $milliseconds): void {
            if ($milliseconds > 0) {
                usleep($milliseconds * 1000);
            }
        };
        $this->aborted = fn (): bool => connection_aborted() === 1;
    }

    /**
     * Do testów: podmiana czasu, czekania i wykrywania zerwanego połączenia.
     *
     * @param  Closure(): float  $clock
     * @param  Closure(int): void  $sleeper
     * @param  Closure(): bool|null  $aborted
     */
    public function withRuntime(Closure $clock, Closure $sleeper, ?Closure $aborted = null): static
    {
        $this->clock = $clock;
        $this->sleeper = $sleeper;
        $this->aborted = $aborted ?? fn (): bool => false;

        return $this;
    }

    /**
     * @param  Closure(string, array<string, mixed>): void  $emit
     * @param  Closure(): void  $keepalive
     * @return array{mode: string, full_publishes: int, polls: int}
     */
    public function run(
        User $user,
        Closure $emit,
        Closure $keepalive,
        int $maxTicks,
        int $sleepMs,
        ?float $lightweightLifetimeSeconds = null,
    ): array {
        $state = new RankedRealtimeConnectionState;

        if ($sleepMs > 0 && $this->signal->clockAlive(($this->clock)())) {
            return $this->runLightweight($user, $state, $emit, $keepalive, $maxTicks, $sleepMs, $lightweightLifetimeSeconds);
        }

        return $this->runFull($user, $state, $emit, $keepalive, $maxTicks, $sleepMs);
    }

    /**
     * @return array{mode: string, full_publishes: int, polls: int}
     */
    protected function runFull(
        User $user,
        RankedRealtimeConnectionState $state,
        Closure $emit,
        Closure $keepalive,
        int $maxTicks,
        int $sleepMs,
    ): array {
        $publishes = 0;

        for ($tick = 0; $tick < $maxTicks; $tick++) {
            if (($this->aborted)()) {
                break;
            }

            $this->publish($user, $state, $emit);
            $publishes++;

            if ($tick < $maxTicks - 1) {
                $keepalive();
                ($this->sleeper)($sleepMs);
            }
        }

        return ['mode' => 'full', 'full_publishes' => $publishes, 'polls' => $publishes];
    }

    /**
     * @return array{mode: string, full_publishes: int, polls: int}
     */
    protected function runLightweight(
        User $user,
        RankedRealtimeConnectionState $state,
        Closure $emit,
        Closure $keepalive,
        int $maxTicks,
        int $sleepMs,
        ?float $lifetimeSeconds = null,
    ): array {
        $pollMs = max((int) config('ranked.sse_poll_ms', 250), 50);
        $fullRefreshSeconds = max((float) config('ranked.sse_full_refresh_seconds', 10), 1.0);
        $legacyRefreshSeconds = $sleepMs / 1000;

        $startedAt = ($this->clock)();
        // Domyślnie ten sam czas życia co w trybie pełnym (max_ticks × sleep_ms); kontroler może
        // go wydłużyć, gdy klient nie podał max_ticks (mniej ponownych połączeń).
        $deadline = $startedAt + ($lifetimeSeconds ?? ($maxTicks * $sleepMs) / 1000);

        $lastFullAt = null;
        $lastVersion = null;
        $lastKeepaliveAt = $startedAt;
        $lastClockCheckAt = $startedAt;
        $clockAlive = true;
        $publishes = 0;
        $polls = 0;

        while (true) {
            if (($this->aborted)()) {
                break;
            }

            $now = ($this->clock)();
            $polls++;

            if ($now - $lastClockCheckAt >= 1.0) {
                $clockAlive = $this->signal->clockAlive($now);
                $lastClockCheckAt = $now;
            }

            // Wersję czytamy przed liczeniem stanu: zmiana w trakcie liczenia wywoła kolejne liczenie.
            $version = $this->signal->userVersion((int) $user->getKey());
            $refreshEvery = $clockAlive ? $fullRefreshSeconds : $legacyRefreshSeconds;

            if ($lastFullAt === null || $version !== $lastVersion || $now - $lastFullAt >= $refreshEvery) {
                $lastVersion = $version;
                $this->publish($user, $state, $emit);
                $publishes++;
                $lastFullAt = ($this->clock)();
            }

            $now = ($this->clock)();

            if ($now >= $deadline) {
                break;
            }

            if ($now - $lastKeepaliveAt >= $legacyRefreshSeconds) {
                $keepalive();
                $lastKeepaliveAt = $now;
            }

            ($this->sleeper)(min($pollMs, (int) max(ceil(($deadline - $now) * 1000), 1)));
        }

        return ['mode' => 'lightweight', 'full_publishes' => $publishes, 'polls' => $polls];
    }

    protected function publish(User $user, RankedRealtimeConnectionState $state, Closure $emit): void
    {
        foreach ($this->publisher->publish($user, $state) as $message) {
            $emit($message['event'], $message['payload']);
        }
    }
}
