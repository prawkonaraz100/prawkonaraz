<?php

namespace App\Console\Commands;

use App\Support\TrafficSignLearningMetricsService;
use Illuminate\Console\Command;

class TrafficSignLearningStatsCommand extends Command
{
    protected $signature = 'traffic-signs:learning-stats
        {--days=7 : Limit report to the last N days}
        {--json : Output JSON for monitoring jobs}';

    protected $description = 'Show traffic sign learning module metrics.';

    public function handle(TrafficSignLearningMetricsService $metricsService): int
    {
        $days = max((int) $this->option('days'), 0);
        $summary = $metricsService->summary($days > 0 ? $days : null);

        if ((bool) $this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info('Raport nauki znakow drogowych');
        $this->line('Okres: '.($days > 0 ? "ostatnie {$days} dni" : 'caly zakres'));
        $this->line('Rozpoczete sesje: '.$summary['started_sessions']);
        $this->line('Ukonczone sesje: '.$summary['completed_sessions']);
        $this->line('Ukonczenie: '.$summary['completion_rate_percent'].'%');
        $this->line('Sredni wynik: '.($summary['average_score_percent'] ?? '-').'%');
        $this->line('Sredni czas odpowiedzi: '.($summary['average_response_time_ms'] ?? '-').' ms');

        if ($summary['mode_breakdown'] !== []) {
            $this->newLine();
            $this->table(
                ['Tryb', 'Starty', 'Ukonczone', 'Sredni wynik'],
                collect($summary['mode_breakdown'])
                    ->map(fn (array $row): array => [
                        $row['mode'],
                        $row['started_sessions'],
                        $row['completed_sessions'],
                        ($row['average_score_percent'] ?? '-').'%',
                    ])
                    ->all(),
            );
        }

        if ($summary['top_confusions'] !== []) {
            $this->newLine();
            $this->table(
                ['Mylony znak', 'Nazwa', 'Liczba'],
                collect($summary['top_confusions'])
                    ->map(fn (array $row): array => [
                        $row['code'] ?? '-',
                        $row['name'] ?? '-',
                        $row['count'],
                    ])
                    ->all(),
            );
        }

        if ($summary['weakest_categories'] !== []) {
            $this->newLine();
            $this->table(
                ['Kategoria', 'Odpowiedzi', 'Poprawne', 'Skutecznosc'],
                collect($summary['weakest_categories'])
                    ->map(fn (array $row): array => [
                        $row['name'] ?? $row['slug'] ?? '-',
                        $row['answers_count'],
                        $row['correct_count'],
                        $row['accuracy_percent'].'%',
                    ])
                    ->all(),
            );
        }

        return self::SUCCESS;
    }
}
