<?php

namespace App\Console\Commands;

use App\Support\RankedMatchService;
use Illuminate\Console\Command;

/**
 * Sprzątanie trybu rankingowego bez czekania na odczyt (W3): kończy mecze po upływie czasu,
 * rozstrzyga walkowery po zerwanym połączeniu, odtwarza zaległe odpowiedzi nagrań
 * i wygasza stare wpisy w kolejce. Mecz na żywo nadal prowadzą odczyty (overview, stream, pong).
 */
class SweepRankedMatchesCommand extends Command
{
    protected $signature = 'ranked:sweep {--limit=200 : Maksymalna liczba aktywnych meczów w jednym przebiegu}';

    protected $description = 'Rozstrzyga zaległe przejścia stanu aktywnych meczów rankingowych i wygasza kolejkę.';

    public function handle(RankedMatchService $rankedMatchService): int
    {
        $result = $rankedMatchService->sweepActiveMatches(max((int) $this->option('limit'), 1));

        $this->info(sprintf(
            'Sprawdzone mecze: %d, zakończone lub przerwane: %d.',
            $result['checked'],
            $result['resolved'],
        ));

        return self::SUCCESS;
    }
}
