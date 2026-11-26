<?php

namespace App\Console\Commands;

use App\Support\RankedMatchService;
use App\Support\RankedRealtimeSignal;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Wspólny zegar trybu rankingowego (etap 1e). Jeden proces (usługa systemd) co sekundę
 * prowadzi przejścia czasowe wszystkich aktywnych meczów, zamiast robić to w strumieniu
 * każdego gracza osobno. Strumienie przechodzą w tryb lekki tylko przy świeżym pulsie zegara.
 *
 * Bezpieczeństwo:
 * - dzierżawa w Redis: druga instancja czeka i nic nie liczy,
 * - kończy się po --max-seconds, przy przekroczeniu pamięci albo po `ranked:clock-restart`
 *   (systemd uruchamia go ponownie z nowym kodem),
 * - błąd jednego ticku jest logowany, a pętla działa dalej.
 */
class RankedClockCommand extends Command
{
    protected $signature = 'ranked:clock
        {--interval-ms= : Odstęp między tickami (domyślnie ranked.clock_interval_ms)}
        {--max-seconds=3600 : Po tylu sekundach proces kończy się (systemd go wznowi)}
        {--memory=128 : Limit pamięci w MB, po którym proces kończy się}
        {--once : Jeden tick i koniec (diagnostyka)}';

    protected $description = 'Wspólny zegar meczów rankingowych: przejścia czasowe, nagrania, walkowery, łączenie kolejki.';

    protected bool $running = true;

    public function handle(RankedMatchService $rankedMatchService, RankedRealtimeSignal $signal): int
    {
        if (! (bool) config('ranked.clock_enabled', false) && ! $this->option('once')) {
            $this->warn('RANKED_CLOCK_ENABLED=false: zegar nie startuje. Strumienie liczą stan po staremu.');

            // Usługa nie powinna restartować się w pętli, gdy flaga jest wyłączona.
            if (! app()->runningUnitTests()) {
                sleep(30);
            }

            return self::SUCCESS;
        }

        $intervalMs = max((int) ($this->option('interval-ms') ?: config('ranked.clock_interval_ms', 1000)), 200);
        $maxSeconds = max((int) $this->option('max-seconds'), 10);
        $memoryLimitBytes = max((int) $this->option('memory'), 32) * 1024 * 1024;
        $ownerId = gethostname().':'.getmypid().':'.Str::random(6);
        $leaseTtl = max((int) ceil($intervalMs / 1000) * 5, 5);
        $startedAt = microtime(true);
        $tick = 0;
        $standbyNoticeShown = false;

        DB::connection()->disableQueryLog();
        $this->registerSignalHandlers();

        if ($this->option('once')) {
            $result = $rankedMatchService->clockTick();
            $signal->recordClockPulse();
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info(sprintf('ranked:clock start (co %d ms, właściciel %s).', $intervalMs, $ownerId));

        while ($this->running) {
            $tickStartedAt = microtime(true);

            if ($tickStartedAt - $startedAt >= $maxSeconds) {
                $this->info('ranked:clock: koniec czasu procesu, systemd uruchomi nowy.');
                break;
            }

            if (memory_get_usage(true) >= $memoryLimitBytes) {
                $this->warn('ranked:clock: przekroczony limit pamięci, kończę.');
                break;
            }

            if ($signal->clockRestartRequestedAfter($startedAt)) {
                $this->info('ranked:clock: prośba o restart (nowe wdrożenie), kończę.');
                break;
            }

            try {
                if (! $signal->acquireClockLease($ownerId, $leaseTtl)) {
                    if (! $standbyNoticeShown) {
                        $this->warn('ranked:clock: działa inna instancja, czekam w gotowości.');
                        $standbyNoticeShown = true;
                    }

                    $this->sleepMs(2000);

                    continue;
                }

                $standbyNoticeShown = false;
                // Wygaszanie starych wpisów kolejki wystarczy co 5 ticków.
                $rankedMatchService->clockTick(expireQueue: $tick % 5 === 0);
                $signal->recordClockPulse();
                $tick++;
            } catch (QueryException $exception) {
                Log::warning('ranked.clock.query_failed', ['message' => $exception->getMessage()]);
                $this->reconnectDatabase();
                $this->sleepMs(1000);
            } catch (\Throwable $exception) {
                report($exception);
                $this->sleepMs(1000);
            }

            $elapsedMs = (int) ((microtime(true) - $tickStartedAt) * 1000);
            $this->sleepMs(max($intervalMs - $elapsedMs, 0));
        }

        $signal->releaseClockLease($ownerId);

        return self::SUCCESS;
    }

    protected function registerSignalHandlers(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);

        foreach ([SIGTERM, SIGINT, SIGQUIT] as $signalNumber) {
            pcntl_signal($signalNumber, function (): void {
                $this->running = false;
            });
        }
    }

    protected function sleepMs(int $milliseconds): void
    {
        // Krótkie odcinki, żeby SIGTERM kończył proces szybko.
        while ($milliseconds > 0 && $this->running) {
            $chunk = min($milliseconds, 200);
            usleep($chunk * 1000);
            $milliseconds -= $chunk;
        }
    }

    protected function reconnectDatabase(): void
    {
        try {
            DB::reconnect();
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
