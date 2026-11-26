<?php

namespace App\Console\Commands;

use App\Support\RankedRealtimeSignal;
use Illuminate\Console\Command;

/**
 * Prosi działający `ranked:clock` o zakończenie po bieżącym ticku (jak `queue:restart`).
 * Wywoływane przy wdrożeniu: systemd uruchamia zegar ponownie już z nowym kodem.
 */
class RankedClockRestartCommand extends Command
{
    protected $signature = 'ranked:clock-restart';

    protected $description = 'Prosi zegar rankingu o restart (po wdrożeniu nowego kodu).';

    public function handle(RankedRealtimeSignal $signal): int
    {
        $signal->requestClockRestart();
        $this->info('Wysłano prośbę o restart ranked:clock.');

        return self::SUCCESS;
    }
}
